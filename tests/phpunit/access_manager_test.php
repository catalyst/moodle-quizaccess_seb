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
 * PHPUnit tests for the access manager.
 *
 * @package    quizacces_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\access_manager;

defined('MOODLE_INTERNAL') || die();

class quizacces_seb_access_manager_testcase extends advanced_testcase {

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that SEB access check is required.
     */
    public function test_seb_check_required() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Set quiz setting to require seb.
        $quizsetting = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        $quizsetting->set('requiresafeexambrowser', 0);
        $quizsetting->save();

        $accessmanager = new access_manager(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course));
        $this->assertFalse($accessmanager->seb_required());
    }

    /**
     * Test that SEB access check is not required.
     */
    public function test_seb_check_not_required() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Set quiz setting to require seb.
        $quizsetting = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        $quizsetting->set('requiresafeexambrowser', 1);
        $quizsetting->save();

        $accessmanager = new access_manager(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course));
        $this->assertTrue($accessmanager->seb_required());
    }

    /**
     * Test that user has capability to bypass SEB check.
     */
    public function test_user_can_bypass_seb_check() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Set the bypass SEB check capability to $USER.
        $this->assign_user_capability('quizaccess/seb:bypassseb', context_module::instance($quiz->cmid)->id);

        $accessmanager = new access_manager(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course));
        $this->assertTrue($accessmanager->can_bypass_seb());
    }

    /**
     * Test user does not have capability to bypass SEB check.
     */
    public function test_user_cannot_bypass_seb_check() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $accessmanager = new access_manager(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course));
        $this->assertFalse($accessmanager->can_bypass_seb());
    }

    /**
     * Test that the quiz Config Key matches the incoming request header.
     */
    public function test_access_keys_validate_with_config_key() {
        global $FULLME;
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $accessmanager = new access_manager(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course));

        $configkey = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id])->get('configkey');

        // Set up dummy request.
        $FULLME = 'https://example.com/moodle/mod/quiz/attempt.php?attemptid=123&page=4';
        $expectedhash = hash('sha256', $FULLME . $configkey);
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH'] = $expectedhash;

        $this->assertTrue($accessmanager->validate_access_keys());
    }

    /**
     * Test that the quiz Config Key does not match the incoming request header.
     */
    public function test_access_keys_fail_to_validate_with_config_key() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $accessmanager = new access_manager(new quiz($quiz, get_coursemodule_from_id('quiz', $quiz->cmid), $course));

        $this->assertFalse($accessmanager->validate_access_keys());
    }

    /**
     * Assign a capability to $USER
     * The function creates a student $USER if $USER->id is empty
     *
     * @param string $capability Capability name.
     * @param int $contextid Context ID.
     * @param int $roleid Role ID.
     * @return int The role id - mainly returned for creation, so calling function can reuse it.
     *
     * @throws coding_exception
     */
    private function assign_user_capability($capability, $contextid, $roleid = null) {
        global $USER;

        // Create a new student $USER if $USER doesn't exist.
        if (empty($USER->id)) {
            $user  = self::getDataGenerator()->create_user();
            self::setUser($user);
        }

        if (empty($roleid)) {
            $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        }

        assign_capability($capability, CAP_ALLOW, $roleid, $contextid);

        role_assign($roleid, $USER->id, $contextid);

        accesslib_clear_all_caches_for_unit_testing();

        return $roleid;
    }
}
