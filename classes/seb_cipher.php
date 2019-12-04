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
     * @param string $xml
     * @param string $password
     * @return string
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

        // Stripe the trailing newline.
        $data = trim($xml);

        // First pass compression of encrypted data.
        $data = gzencode($data);

        // Encrypt the data, return binary.
        $data = $cryptor->encrypt($data, $password, Cryptor::DEFAULT_SCHEMA_VERSION, false);

        // Add the magic header for password encrypted SEB files.
        $data = 'pswd' . $data;

        // Second pass compression.
        $data = gzencode($data);

        return $data;
    }

    /**
     * Decrypt a seb file into raw XML if not already.
     *
     * @param string $data
     * @param string $password Can be empty if the file is plain xml or if the seb file is plain text compressed (TODO: prefix: plnd).
     * @return string
     */
    public static function decrypt(string $data, string $password = '') : string {
        $cryptor = new Decryptor;

        // If the file starts with <?xml then we really shouldn't be decrypting it.
        if (strpos($data, '<?xml') === 0) {
            return $data;
        }

        // First pass decompress to remove gz header. Suppress warning so false is returned if decode fails.
        $data = @gzdecode($data);

        // Check to see if magic header is 'pswd' which tells us it's password encrypted.
        if (strpos($data, 'pswd') !== 0) {
            return '';
        }

        // Remove the header.
        $substr = substr($data, 4, strlen($data));

        // The implementation of RNCryptor requires the input to be base64 encoded.
        $b64data = base64_encode($substr);

        // Decrypt!
        $plaintext = $cryptor->decrypt($b64data, $password);

        // If the file does not start with <?xml then run the second gz pass.
        if (strpos($plaintext, '<?xml') !== 0) {
            $plaintext = @gzdecode($plaintext);
        }

        return trim($plaintext);
    }
}
