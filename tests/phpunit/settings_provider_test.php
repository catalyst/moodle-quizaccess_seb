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
 * PHPUnit tests for settings_provider.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\quiz_settings;
use quizaccess_seb\settings_provider;

defined('MOODLE_INTERNAL') || die();

class quizaccess_seb_settings_provider_testcase extends advanced_testcase {

    /**
     * Test that settings types to be added to quiz settings, are part of quiz_settings persistent class.
     */
    public function test_setting_types_are_part_of_quiz_settings_table() {
        $dbsettings = (array) (new quiz_settings())->to_record();
        $settingtypes = settings_provider::get_quiz_element_types();
        $settingtypes = (array) $this->strip_all_prefixes((object) $settingtypes);

        // Get all elements to be added to form, that are not in the persistent quiz_settings class.
        $diffelements = array_diff_key($settingtypes, $dbsettings);

        // Check expected differences.
        $this->assertTrue(array_key_exists('seb', $diffelements)); // Table header.
        $this->assertTrue(array_key_exists('sebconfigtemplate', $diffelements)); // Used to compute templateid.

        // Unset expected fields.
        unset($diffelements['seb']);
        unset($diffelements['sebconfigtemplate']);

        $this->assertEmpty($diffelements);
    }

    /**
     * Test that setting defaults only refer to settings defined in setting types.
     */
    public function test_setting_defaults_are_part_of_file_types() {
        $settingtypes = settings_provider::get_quiz_element_types();
        $settingdefaults = settings_provider::get_quiz_defaults();

        // Get all defaults that have no matching element in settings types.
        $diffelements = array_diff_key($settingdefaults, $settingtypes);

        $this->assertEmpty($diffelements);
    }

    /**
     * Test that setting hideif rules only refer to settings defined in setting types, including the conditions.
     */
    public function test_setting_hideifs_are_part_of_file_types() {
        $settingtypes = settings_provider::get_quiz_element_types();
        $settinghideifs = settings_provider::get_quiz_hideifs();

        // Get all defaults that have no matching element in settings types.
        $diffelements = array_diff_key($settinghideifs, $settingtypes);

        // Check no diff for elements to hide.
        $this->assertEmpty($diffelements);

        // Check each element's to hide conditions that each condition refers to element in settings types.
        foreach ($settinghideifs as $conditions) {
            foreach ($conditions as $condition) {
                $this->assertTrue(array_key_exists($condition->get_element(), $settingtypes));
            }
        }
    }

    /**
     * Strip the seb_ prefix from each setting key.
     *
     * @param \stdClass $settings Object containing settings.
     * @return \stdClass The modified settings object.
     */
    private function strip_all_prefixes(\stdClass $settings) : \stdClass {
        $newsettings = new \stdClass();
        foreach ($settings as $name => $setting) {
            $newname = preg_replace("/^seb_/", "", $name);
            $newsettings->$newname = $setting; // Add new key.
        }
        return $newsettings;
    }
}
