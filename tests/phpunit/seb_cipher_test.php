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
 * PHPUnit Tests for the SEB cipher.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\seb_cipher;

defined('MOODLE_INTERNAL') || die();

class seb_cipher_testcase extends advanced_testcase {

    /**
     * Test that an encrypted SEB config file with a password is decrypted correctly.
     *
     * Both the encrypted and unencrypted files were created with the SEB config tool, Windows v2.3. Only change was
     * to use the encrypted salt in unencrypted file. Rest of settings unchanged.
     */
    public function test_file_decrypted() {
        $encryptedcontents = file_get_contents(__DIR__ . '/sample_data/encrypted.seb');
        $decryptedcontents = seb_cipher::decrypt($encryptedcontents, 'test');
        $unencryptedcontents = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $this->assertEquals($unencryptedcontents, $decryptedcontents);
    }

    /**
     * Test that an unencrypted SEB config file is encrypted correctly with a password.
     *
     * Both the encrypted and unencrypted files were created with the SEB config tool, Windows v2.3. Only change was
     * to use the encrypted salt in unencrypted file. Rest of settings unchanged.
     */
    public function test_file_encrypted() {
        $unencryptedcontents = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $encryptedcontents = seb_cipher::encrypt($unencryptedcontents, 'test');

        // As the binary will be unique, we need to unencrypt it again to test it.
        $decryptedcontents = seb_cipher::decrypt($encryptedcontents, 'test');

        // Massage the data back to expected format.
        // These replacements are for converting XML to PList representation.
        $replacements = [
            "<dict></dict>" => "<dict />",
            "<data></data>" => "<data />",
            "<true/>" => "<true />",
            "<false/>" => "<false />",
            "\n" => "\r\n",  // Convert UNIX to DOS line endings.
        ];

        foreach ($replacements as $key => $replacement) {
            $decryptedcontents = str_replace($key, $replacement, $decryptedcontents);
        }

        $this->assertEquals($unencryptedcontents, $decryptedcontents);
    }
}
