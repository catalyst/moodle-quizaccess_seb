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
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

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
        unset($diffelements['filemanager_sebconfigfile']);

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
     * Test SEB usage options.
     */
    public function test_get_requiresafeexambrowser_options() {
        $this->assertCount(4, settings_provider::get_requiresafeexambrowser_options());
        $this->assertTrue(array_key_exists(0, settings_provider::get_requiresafeexambrowser_options()));
        $this->assertTrue(array_key_exists(1, settings_provider::get_requiresafeexambrowser_options()));
        $this->assertFalse(array_key_exists(2, settings_provider::get_requiresafeexambrowser_options()));
        $this->assertTrue(array_key_exists(3, settings_provider::get_requiresafeexambrowser_options()));
        $this->assertTrue(array_key_exists(4, settings_provider::get_requiresafeexambrowser_options()));
    }

    /**
     * Test the validation of a seb config file.
     */
    public function test_validate_draftarea_configfile_success() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
            . "<plist version=\"1.0\"><dict><key>hashedQuitPassword</key><string>hashedpassword</string>"
            . "<key>allowWlan</key><false/></dict></plist>\n";
        $itemid = $this->create_test_draftarea_file($xml);
        $errors = settings_provider::validate_draftarea_configfile($itemid);
        $this->assertEmpty($errors);
    }

    /**
     * Test the validation of a missing seb config file.
     */
    public function test_validate_draftarea_configfile_failure() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $xml = "This is not a config file.";
        $itemid = $this->create_test_draftarea_file($xml);
        $errors = settings_provider::validate_draftarea_configfile($itemid);
        $this->assertEquals($errors, new lang_string('fileparsefailed', 'quizaccess_seb'));
    }

    /**
     * Test obtaining the draftarea content.
     */
    public function test_get_current_user_draft_file() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $itemid = $this->create_test_draftarea_file($xml);
        $file = settings_provider::get_current_user_draft_file($itemid);
        $content = $file->get_content();

        $this->assertEquals($xml, $content);
    }

    /**
     * Test saving files from the user draft area into the quiz context area storage.
     */
    public function test_save_filemanager_sebconfigfile_draftarea() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($quiz->cmid);
        $this->setUser($user);

        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');

        $draftitemid = $this->create_test_draftarea_file($xml);

        settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $quiz->cmid);

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');

        $this->assertCount(2, $files);
    }

    /**
     * Test deleting the $quiz->cmid itemid from the file area.
     */
    public function test_delete_uploaded_config_file() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($quiz->cmid);
        $this->setUser($user);

        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $draftitemid = $this->create_test_draftarea_file($xml);

        settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $quiz->cmid);

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');
        $this->assertCount(2, $files);

        settings_provider::delete_uploaded_config_file($quiz->cmid);

        $files = $fs->get_area_files($context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');
        // The '.' directory.
        $this->assertCount(1, $files);
    }

    /**
     * Test getting the file from the context module id file area.
     */
    public function test_get_module_context_sebconfig_file() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($quiz->cmid);
        $this->setUser($user);

        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $draftitemid = $this->create_test_draftarea_file($xml);

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');
        $this->assertCount(0, $files);

        settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $quiz->cmid);

        $file = settings_provider::get_module_context_sebconfig_file($quiz->cmid);

        $this->assertSame($file->get_content(), $xml);
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

    /**
     * Creates a file in the user draft area.
     *
     * @param string $xml
     * @return int The user draftarea id
     */
    private function create_test_draftarea_file(string $xml) : int {
        global $USER;

        $itemid = 0;
        $usercontext = context_user::instance($USER->id);
        $filerecord = [
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => 'test.xml'
        ];

        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, $xml);

        $draftitemid = 0;
        file_prepare_draft_area($draftitemid, $usercontext->id, 'user', 'draft', 0);

        return $draftitemid;
    }
}
