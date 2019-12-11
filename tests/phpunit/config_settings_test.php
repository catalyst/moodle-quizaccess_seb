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
 * PHPUnit tests for config_settings class.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\config_settings;

defined('MOODLE_INTERNAL') || die();

class config_settings_testcase extends advanced_testcase {

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that config_settings can be constructed with a quiz id if there are existing settings.
     */
    public function test_construct_with_existing_settings() {
        global $DB;
        $settings = $this->get_test_db_settings();
        $id = $DB->insert_record('quizaccess_seb_quizsettings', $settings);
        $this->assertNotFalse($id);
        $configsettings = config_settings::with_quizid($settings['quizid']);
        $this->assertTrue($configsettings->exists());
    }

    /**
     * Test that config_settings can be constructed with new settings.
     */
    public function test_construct_with_new_settings() {
        $settings = $this->get_test_form_settings();
        $configsettings = config_settings::with_form_data($settings);
        $this->assertTrue($configsettings->exists());
    }

    /**
     * Test that config_settings can't be constructed without any settings.
     */
    public function test_construct_with_no_settings() {
        $this->expectExceptionMessage(moodle_exception::class);
        $this->expectExceptionMessage('Config settings not provided all required settings.');
        config_settings::with_quizid('999');
    }

    /**
     * Test that config_settings can't be constructed without a $quiz->id.
     */
    public function test_construct_with_no_quiz_id() {
        $settings = $this->get_test_form_settings();
        unset($settings->id);

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('Cannot construct config_settings without $quiz->id');
        config_settings::with_form_data($settings);
    }

    /**
     * Test getter for config item id.
     */
    public function test_get_config_itemid_with_settings() {
        global $DB;
        $settings = $this->get_test_db_settings();
        $settings['sebconfigfile'] = 1234567;
        $id = $DB->insert_record('quizaccess_seb_quizsettings', $settings);
        $this->assertNotFalse($id);
        $configsettings = config_settings::with_quizid($settings['quizid']);
        $this->assertEquals(1234567, $configsettings->get_config_itemid());
    }

    /**
     * Test getter for config item id if setting doesn't exist.
     */
    public function test_get_config_itemid_with_no_settings() {
        global $DB;
        $settings = $this->get_test_db_settings();
        $settings['sebconfigfile'] = null;
        $id = $DB->insert_record('quizaccess_seb_quizsettings', $settings);
        $this->assertNotFalse($id);
        $configsettings = config_settings::with_quizid($settings['quizid']);
        $this->assertEmpty($configsettings->get_config_itemid());
    }

    /**
     * Test save settings with existing settings.
     */
    public function test_save_settings_with_existing_settings() {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Assert existing setting.
        $record = $DB->get_record('quizaccess_seb_quizsettings', ['quizid' => $quiz->id], '*');
        $this->assertEquals(0, $record->requiresafeexambrowser);

        // Setup and save new settings.
        $newsettings = $this->get_test_form_settings();
        $newsettings->id = $quiz->id;
        $newsettings->seb_requiresafeexambrowser = 1;
        $this->assertTrue(config_settings::with_form_data($newsettings)->save_settings());

        // Check DB updated.
        $record = $DB->get_record('quizaccess_seb_quizsettings', ['quizid' => $quiz->id], '*');
        $this->assertEquals(1, $record->requiresafeexambrowser);
    }

    /**
     * Test that existing settings can be deleted.
     */
    public function test_delete_settings_with_existing_settings() {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Need dummy settings to create object.
        $settings = $this->get_test_form_settings();
        $settings->id = $quiz->id;

        $this->assertTrue(config_settings::with_form_data($settings)->delete_settings());
        $this->assertFalse($DB->get_record('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));
    }

    /**
     * Mock data for inserting settings into DB.
     *
     * @return array
     */
    private function get_test_db_settings() : array {
        return [
            'quizid' => 1,
            'templateid' => 0,
            'requiresafeexambrowser' => 0,
            'sebconfigfile' => null,
            'showsebtaskbar' => 1,
            'showwificontrol' => 0,
            'showreloadbutton' => 1,
            'showtime' => 1,
            'showkeyboardlayout' => 1,
            'allowuserquitseb' => 1,
            'quitpassword' => '',
            'linkquitseb' => 0,
            'userconfirmquit' => 1,
            'enableaudiocontrol' => 0,
            'muteonstartup' => 0,
            'allowspellchecking' => 0,
            'allowreloadinexam' => 1,
            'activateurlfiltering' => 0,
            'filterembeddedcontent' => 0,
            'expressionsallowed' => '',
            'regexallowed' => '',
            'expressionsblocked' => '',
            'regexblocked' => '',
            'suppresssebdownloadlink' => 0,
        ];
    }

    /**
     * Mock data from the quiz settings form.
     *
     * @return stdClass Mock data similar to what $quiz contains in rule_access_base.php:save_settings().
     */
    private function get_test_form_settings() : stdClass {
        $settings = new stdClass();
        $settings->id = 1;
        $settings->seb_requiresafeexambrowser = 0;
        $settings->seb_sebconfigtemplate = 0;
        $settings->seb_sebconfigfile = null;
        $settings->seb_showsebtaskbar = 1;
        $settings->seb_showwificontrol = 0;
        $settings->seb_showreloadbutton = 1;
        $settings->seb_showtime = 1;
        $settings->seb_showkeyboardlayout = 1;
        $settings->seb_allowuserquitseb = 1;
        $settings->seb_quitpassword = '';
        $settings->seb_linkquitseb = 0;
        $settings->seb_userconfirmquit = 1;
        $settings->seb_enableaudiocontrol = 0;
        $settings->seb_muteonstartup = 0;
        $settings->seb_allowspellchecking = 0;
        $settings->seb_allowreloadinexam = 1;
        $settings->seb_activateurlfiltering = 0;
        $settings->seb_filterembeddedcontent = 0;
        $settings->seb_expressionsallowed = '';
        $settings->seb_regexallowed = '';
        $settings->seb_expressionsblocked = '';
        $settings->seb_regexblocked = '';
        $settings->seb_suppresssebdownloadlink = 0;
        return $settings;
    }
}
