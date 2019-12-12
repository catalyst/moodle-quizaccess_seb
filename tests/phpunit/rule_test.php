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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/mod/quiz/accessrule/seb/rule.php"); // Include plugin rule class.
require_once($CFG->dirroot . "/mod/quiz/mod_form.php"); // Include plugin rule class.

class quizaccess_seb_rule_testcase extends advanced_testcase {

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
        $errors = quizaccess_seb::validate_settings_form_fields([], ['seb_quizid' => 1], [],
            $form);
        $this->assertEmpty($errors);
    }

    /**
     * Test errors are found with invalid data.
     */
    public function test_validate_settings_with_invalid_data() {
        $form = $this->createMock('mod_quiz_mod_form');
        // Validate settings with a dummy form.
        $errors = quizaccess_seb::validate_settings_form_fields([], [], [], $form);
        $this->assertEquals(['seb_quizid' => 'Required field'], $errors);
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
}
