<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class cipher is for encrypting and decrypting SEB config files.
 *
 * @package    quizaccess_seb
 * @copyright  2018 Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

use RNCryptor\RNCryptor\Cryptor;
use RNCryptor\RNCryptor\Decryptor;
use RNCryptor\RNCryptor\Encryptor;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../vendor/autoload.php');

class seb_cipher {

    /**
     * Encrypt a seb file with a password ready for use as a seb exam link.
     *
     * Link to SEB documentation - https://safeexambrowser.org/developer/seb-file-format.html .
     *
     * Encryption process extracted from SEB documentation.
     *
     * 2. If a password is entered, encrypt the gzip compressed XML settings data with that password and prefix it with
     * “pswd” (store those four bytes in front of the encrypted data).
     * 3. If no password is entered, prefix the gzip compressed plain XML settings data with “plnd”.
     * 5. Compress with gzip and save the resulting (binary) data in the .seb file.
     *
     * Note steps 1. and 4. deal with public/private keys that are not handled here.
     *
     * @param string $xml File contents to be encrypted.
     * @param string $password Password to encrypt with 'pswd' format, or empty if 'plnd' format required.
     * @return string Encrypted data.
     *
     * @throws \Exception
     */
    public static function encrypt(string $xml, string $password) : string {
        $cryptor = new Encryptor;

        // These replacements are for converting PList to XML representation.
        $replacements = [
            '<array />'         => '<array></array>',
            '<dict />'          => '<dict></dict>',
            '<data />'          => '<data></data>',
            '<true />'          => '<true/>',
            '<false />'         => '<false/>',
            "\r\n"              => "\n",       // Convert DOS to UNIX line endings.
        ];

        foreach ($replacements as $key => $replacement) {
            $xml = str_replace($key, $replacement, $xml);
        }

        // Strip the trailing newline.
        $data = trim($xml);

        // 2a. First pass compression of encrypted data.
        $data = gzencode($data);

        if (empty($password)) {
            // 3. Add the magic header for compressed plain text unencrypted SEB files.
            $data = 'plnd' . $data;
        } else {
            // 2b. Encrypt the data, return binary.
            $data = $cryptor->encrypt($data, $password, Cryptor::DEFAULT_SCHEMA_VERSION, false);

            // 2c. Add the magic header for password encrypted SEB files.
            $data = 'pswd' . $data;
        }

        // 5. Second pass compression.
        $data = gzencode($data);

        return $data;
    }

    /**
     * Decrypt a seb file into raw XML if not already.
     *
     * Link to SEB documentation - https://safeexambrowser.org/developer/seb-file-format.html .
     *
     * Decryption process extracted from SEB documentation.
     *
     * 1. Load the whole .seb file into memory. Decompress with gzip (ungzip). Check for the first four bytes prefix.
     * 4. Check for the prefix of the data resulting from step 2 or 3. If it is “plnd”, then strip the 4 bytes prefix,
     * the remaining data is the XML settings data.
     * 5. If the prefix is “pswd”: Request the user to enter a password. Decrypt the data with this password. If
     * decryption fails with a “wrong password” error, ask again for the password (max. 5 times, then abort).
     * 6. If decryption was successful, decompress with gzip (ungzip). The resulting data is the XML settings data.
     *
     * Note steps 2. and 3. deal with public/private keys that are not handled here.
     *
     * @param string $data File contents to be decrypted.
     * @param string $password Can be empty if the file is plain xml or if the seb file is plain text compressed.
     * @return string
     */
    public static function decrypt(string $data, string $password = '') : string {
        $cryptor = new Decryptor;

        // If the file starts with <?xml then we really shouldn't be decrypting it.
        if (strpos($data, '<?xml') === 0) {
            return $data;
        }

        // 1a. First pass decompress to remove gz header. Suppress warning so false is returned if decode fails.
        $data = gzdecode($data);

        // 5a. Check to see if magic header is 'pswd' which tells us it's password encrypted.
        if (strpos($data, 'pswd') === 0) {
            // Remove the magic header - 'pswd'.
            $substr = substr($data, 4, strlen($data));

            // 5b. The implementation of RNCryptor requires the input to be base64 encoded.
            $b64data = base64_encode($substr);

            // 5c. Decrypt!
            $plaintext = $cryptor->decrypt($b64data, $password);

            // 4a. Check to see if magic header is 'plnd' which tells us it's unencrypted.
        } else if (strpos($data, 'plnd') === 0) {
            // 4b. Remove the magic header - 'plnd'.
            $plaintext = substr($data, 4, strlen($data));
        } else {
            return '';
        }

        // 6. If the file does not start with <?xml then run the second gz pass.
        if (strpos($plaintext, '<?xml') !== 0) {
            $plaintext = gzdecode($plaintext);
        }

        return trim($plaintext);
    }
}
