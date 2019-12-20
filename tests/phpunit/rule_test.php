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
 * PHPUnit tests for plugin rule class.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\tests\phpunit\quizaccess_seb_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/mod/quiz/accessrule/seb/rule.php"); // Include plugin rule class.
require_once($CFG->dirroot . "/mod/quiz/mod_form.php"); // Include plugin rule class.
require_once(__DIR__ . '/base.php');

class quizaccess_seb_rule_testcase extends quizaccess_seb_testcase {

    /**
     * Ran before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test no errors are found with valid data.
     */
    public function test_validate_settings_with_valid_data() {
        $form = $this->createMock('mod_quiz_mod_form');
        // Validate settings with a dummy form.
        $errors = quizaccess_seb::validate_settings_form_fields([], ['instance' => 1], [],
            $form);
        $this->assertEmpty($errors);
    }

    /**
     * Test errors are found with invalid data.
     */
    public function test_validate_settings_with_invalid_data() {
        $form = $this->createMock('mod_quiz_mod_form');
        // Validate settings with a dummy form and quiz instance.
        $errors = quizaccess_seb::validate_settings_form_fields([],
                ['instance' => 1, 'seb_requiresafeexambrowser' => 'Uh oh!'], [], $form);
        $this->assertEquals(['seb_requiresafeexambrowser' => 'Data submitted is invalid'], $errors);
    }

    /**
     * Test settings are saved to DB.
     */
    public function test_save_settings() {
        global $DB;
        $quiz = new stdClass();
        $quiz->id = 1;
        $this->assertFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));
        quizaccess_seb::save_settings($quiz);
        $this->assertNotFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));
    }

    /**
     * Test nothing happens when deleted is called without settings saved.
     */
    public function test_delete_settings_without_existing_settings() {
        global $DB;
        $quiz = new stdClass();
        $quiz->id = 1;
        quizaccess_seb::delete_settings($quiz);
        $this->assertFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));
    }

    /**
     * Test settings are deleted from DB.
     */
    public function test_delete_settings_with_existing_settings() {
        global $DB;
        $quiz = new stdClass();
        $quiz->id = 1;
        $this->assertFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));
        quizaccess_seb::save_settings($quiz);
        $this->assertNotFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));
        quizaccess_seb::delete_settings($quiz);
        $this->assertFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));
    }

    /**
     * Test access prevented if access keys are invalid.
     */
    public function test_access_prevented_if_access_keys_invalid() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Set quiz setting to require seb.
        $quizsetting = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        $quizsetting->set('requiresafeexambrowser', 1);
        $quizsetting->save();

        $rule = new quizaccess_seb(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course), 0);
        // Check that correct error message is returned.
        $this->assertEquals("The config key or browser exam keys could not be validated. "
                . "Please ensure you are using the Safe Exam Browser with correct configuration file."
                . "<div>\n"
                . "    <a class=\"btn btn-secondary mx-1\" role=\"button\" href=\"https://safeexambrowser.org/download_en.html\">Download Safe Exam Browser</a>\n"
                . "    <a class=\"btn btn-secondary mx-1\" role=\"button\" "
                . "href=\"sebs://www.example.com/moodle/mod/quiz/accessrule/seb/config.php?cmid=$quiz->cmid\">Launch Safe Exam Browser</a>\n"
                . "    <a class=\"btn btn-secondary mx-1\" role=\"button\" "
                . "href=\"https://www.example.com/moodle/mod/quiz/accessrule/seb/config.php?cmid=$quiz->cmid\">Download Configuration</a>\n"
                . "</div>"
                , $rule->prevent_access());
    }

    /**
     * Test access not prevented if access keys match headers.
     */
    public function test_access_allowed_if_access_keys_valid() {
        global $FULLME;
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Set quiz setting to require seb.
        $quizsettings = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        $quizsettings->set('requiresafeexambrowser', 1);
        $quizsettings->save();

        $configkey = $quizsettings->get('configkey');

        // Set up dummy request.
        $FULLME = 'https://example.com/moodle/mod/quiz/attempt.php?attemptid=123&page=4';
        $expectedhash = hash('sha256', $FULLME . $configkey);
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH'] = $expectedhash;

        $rule = new quizaccess_seb(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course), 0);
        // Check that correct error message is returned.
        $this->assertFalse($rule->prevent_access());
    }

    /**
     * Test access not prevented if SEB not required.
     */
    public function test_access_allowed_if_seb_not_required() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Set quiz setting to not require seb.
        $quizsettings = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        $quizsettings->set('requiresafeexambrowser', 0);
        $quizsettings->save();

        $rule = new quizaccess_seb(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course), 0);
        // Check that correct error message is returned.
        $this->assertFalse($rule->prevent_access());
    }

    /**
     * Test access not prevented if USER has bypass capability.
     */
    public function test_access_allowed_if_user_has_bypass_capability() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Set quiz setting to require seb.
        $quizsettings = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        $quizsettings->set('requiresafeexambrowser', 1);
        $quizsettings->save();

        // Set the bypass SEB check capability to $USER.
        $this->assign_user_capability('quizaccess/seb:bypassseb', context_module::instance($quiz->cmid)->id);

        $rule = new quizaccess_seb(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course), 0);
        // Check that correct error message is returned.
        $this->assertFalse($rule->prevent_access());
    }
}
