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
 * Tests for plugin lib.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/mod/quiz/accessrule/seb/lib.php");

class quizaccess_seb_lib_testcase extends advanced_testcase {

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that the course module must exist to download a file.
     */
    public function test_file_not_served_with_invalid_cmid() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user); // Log user in.

        $this->assertFalse(serve_quiz_config_xml('999'));
        $this->assertDebuggingCalled("Can't find data record in database. (SELECT cm.*, m.name, md.name AS modname \n"
                . "              FROM {course_modules} cm\n"
                . "                   JOIN {modules} md ON md.id = cm.module\n"
                . "                   JOIN {quiz} m ON m.id = cm.instance\n"
                . "                   \n"
                . "             WHERE cm.id = :cmid AND md.name = :modulename\n"
                . "                   \n"
                . "[array (\n"
                . "  'cmid' => '999',\n"
                . "  'modulename' => 'quiz',\n"
                .')])', DEBUG_DEVELOPER);
    }

    /**
     * Test that the user must be enrolled to download a file.
     */
    public function test_file_not_served_when_user_not_enrolled_in_course() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $this->setUser($user); // Log user in.

        $this->assertFalse(serve_quiz_config_xml($quiz->cmid));
        $this->assertDebuggingCalled('Unsupported redirect detected, script execution terminated', DEBUG_DEVELOPER);
    }

    /**
     * Test that if SEB quiz settings can't be found, a file will not be served.
     */
    public function test_file_not_served_if_config_not_found_for_cmid() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user); // Log user in.

        $this->assertTrue($DB->delete_records('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));

        $this->assertFalse(serve_quiz_config_xml($quiz->cmid));
        $this->assertDebuggingCalled('quizaccess_seb - Could not find SEB config for quiz with cmid: ' . $quiz->cmid,
            DEBUG_DEVELOPER);
    }

    /**
     * That that if config is empty for a quiz, a file will not be served.
     */
    public function test_file_not_served_if_config_empty() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user); // Log user in.

        // Update database with empty config.
        $settingsrecord = $DB->get_record('quizaccess_seb_quizsettings', ['quizid' => $quiz->id], '*');
        $settingsrecord->config = '';
        $this->assertTrue($DB->update_record('quizaccess_seb_quizsettings', $settingsrecord));

        $this->assertFalse(serve_quiz_config_xml($quiz->cmid));
        $this->assertDebuggingCalled('quizaccess_seb - Could not find SEB config for quiz with cmid: ' . $quiz->cmid,
            DEBUG_DEVELOPER);
    }

    /**
     * Test file is served successfully.
     *
     * To prevent headers throwing error, run this test in a separate process.
     *
     * @runInSeparateProcess
     */
    public function test_file_served() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user); // Log user in.

        $this->expectOutputString("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/>"
                . "<key>showReloadButton</key><true/><key>showTime</key><true/><key>showInputLanguage</key><true/>"
                . "<key>allowQuit</key><true/><key>quitURLConfirm</key><true/><key>audioControlEnabled</key><false/>"
                . "<key>audioMute</key><false/><key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/>"
                . "<key>URLFilterEnable</key><false/><key>URLFilterEnableContentFilter</key><false/>"
                . "<key>URLFilterRules</key><array/></dict></plist>\n");
        $this->assertTrue(serve_quiz_config_xml($quiz->cmid));
    }
}
