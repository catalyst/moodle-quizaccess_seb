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
 * PHPUnit Tests for config_key class.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\config_key;

defined('MOODLE_INTERNAL') || die();

class quizaccess_seb_config_key_testcase extends advanced_testcase {

    /**
     * Test that the Config Key hash is generated.
     *
     * The file was generated using SEB 2.1.4 (Mac OS), and the expected Config Key extracted from the config tool.
     */
    public function test_config_key_hash_generated() {
        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted_mac_001.seb');
        $hash = config_key::generate($xml)->get_hash();
        $this->assertEquals('4fa9af8ec8759eb7c680752ef4ee5eaf1a860628608fccae2715d519849f9292', $hash);
    }

    /**
     * Test that trying to generate the hash key with bad xml will result in an error.
     */
    public function test_config_key_not_generated_with_bad_xml() {
        set_error_handler(function($errno, $errstr, $errfile, $errline ){
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageRegExp("/.*DOMDocument::loadXML().*/");
        config_key::generate("<?xml This is some bad xml for sure.");
    }

    /**
     * Check that the Config Key hash is not altered if the originatorVersion is present in the XML or not.
     */
    public function test_presence_of_originator_version_does_not_effect_hash() {
        $xmlwithoriginatorversion = file_get_contents(__DIR__ . '/sample_data/simpleunencrypted.seb');
        $xmlwithoutoriginatorversion = file_get_contents(__DIR__ . '/sample_data/simpleunencryptedwithoutoriginator.seb');
        $hashwithorigver = config_key::generate($xmlwithoriginatorversion)->get_hash();
        $hashwithoutorigver = config_key::generate($xmlwithoutoriginatorversion)->get_hash();
        $this->assertEquals($hashwithorigver, $hashwithoutorigver);
    }
}
