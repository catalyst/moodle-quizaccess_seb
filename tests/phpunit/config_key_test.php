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
     */
    public function test_config_key_hash_generated() {
        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $hash = config_key::generate($xml)->get_hash();
        $this->assertEquals('9683df435f3a226d9d8b206846910ff1045c18b13e518474541f40461a61afee', $hash);
    }
}
