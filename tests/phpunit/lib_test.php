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

use quizaccess_seb\tests\phpunit\quizaccess_seb_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/base.php');
require_once("$CFG->dirroot/mod/quiz/accessrule/seb/lib.php");

/**
 * Tests for plugin lib.
 *
 * @copyright  2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_seb_lib_testcase extends quizaccess_seb_testcase {

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

        $this->expectException(dml_exception::class);
        $this->expectExceptionMessage("Can't find data record in database. (SELECT cm.*, m.name, md.name AS modname \n"
            . "              FROM {course_modules} cm\n"
            . "                   JOIN {modules} md ON md.id = cm.module\n"
            . "                   JOIN {quiz} m ON m.id = cm.instance\n"
            . "                   \n"
            . "             WHERE cm.id = :cmid AND md.name = :modulename\n"
            . "                   \n"
            . "[array (\n"
            . "  'cmid' => '999',\n"
            . "  'modulename' => 'quiz',\n"
            .')])');
        quizaccess_seb_get_config('999');
    }

    /**
     * Test that the user must be enrolled to download a file.
     */
    public function test_file_not_served_when_user_not_enrolled_in_course() {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->create_test_quiz($course, \quizaccess_seb\settings_provider::USE_SEB_CONFIG_MANUALLY);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Log user in.

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Unsupported redirect detected, script execution terminated');
        quizaccess_seb_get_config($quiz->cmid);
    }

    /**
     * Test that if SEB quiz settings can't be found, a file will not be served.
     */
    public function test_file_not_served_if_config_not_found_for_cmid() {
        global $DB;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->create_test_quiz($course);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user); // Log user in.

        $this->assertTrue($DB->delete_records('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]));

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("No SEB config could be found for quiz with cmid: $quiz->cmid");
        quizaccess_seb_get_config($quiz->cmid);
    }

    /**
     * That that if config is empty for a quiz, a file will not be served.
     */
    public function test_file_not_served_if_config_empty() {
        global $DB;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->create_test_quiz($course, \quizaccess_seb\settings_provider::USE_SEB_CONFIG_MANUALLY);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user); // Log user in.

        // Update database with empty config.
        $settingsrecord = $DB->get_record('quizaccess_seb_quizsettings', ['quizid' => $quiz->id], '*');
        $settingsrecord->config = '';
        $this->assertTrue($DB->update_record('quizaccess_seb_quizsettings', $settingsrecord));

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("No SEB config could be found for quiz with cmid: $quiz->cmid");
        quizaccess_seb_get_config($quiz->cmid);
    }

    /**
     * Test file is served successfully.
     */
    public function test_config_found() {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->create_test_quiz($course, \quizaccess_seb\settings_provider::USE_SEB_CONFIG_MANUALLY);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user); // Log user in.

        $config = quizaccess_seb_get_config($quiz->cmid);

        $url = new moodle_url("/mod/quiz/view.php", ['id' => $quiz->cmid]);

        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key>"
                . "<false/><key>showReloadButton</key><true/><key>showTime</key><true/><key>showInputLanguage</key>"
                . "<true/><key>allowQuit</key><true/><key>quitURLConfirm</key><true/><key>audioControlEnabled</key>"
                . "<false/><key>audioMute</key><false/><key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key>"
                . "<true/><key>URLFilterEnable</key><false/><key>URLFilterEnableContentFilter</key><false/>"
                . "<key>URLFilterRules</key><array/><key>startURL</key><string>$url</string>"
                . "<key>sendBrowserExamKey</key><true/><key>examSessionClearCookiesOnStart</key><false/>"
                . "</dict></plist>\n", $config);
    }
}
