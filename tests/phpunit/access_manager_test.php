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
use quizaccess_seb\quiz_settings;
use quizaccess_seb\settings_provider;
use quizaccess_seb\tests\phpunit\quizaccess_seb_testcase;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/base.php');

class quizacces_seb_access_manager_testcase extends quizaccess_seb_testcase {

    /** @var stdClass $course Test course to contain quiz. */
    private $course;

    /** @var stdClass $quiz A test quiz. */
    private $quiz;

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * Test access_manager private property quizsettings is null.
     */
    public function test_access_manager_quizsettings_null() {
        $this->quiz = $this->create_test_quiz($this->course);

        $accessmanager = new access_manager(new quiz($this->quiz,
            get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));

        $this->assertFalse($accessmanager->seb_required());

        $reflection = new \ReflectionClass('\quizaccess_seb\access_manager');
        $property = $reflection->getProperty('quizsettings');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($accessmanager));
    }

    /**
     * Test that SEB is not required.
     */
    public function test_seb_required_false() {
        $this->quiz = $this->create_test_quiz($this->course);

        $accessmanager = new access_manager(new quiz($this->quiz,
                get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $this->assertFalse($accessmanager->seb_required());
    }

    /**
     * Test that SEB is required.
     */
    public function test_seb_required_true() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $accessmanager = new access_manager(new quiz($this->quiz,
                get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $this->assertTrue($accessmanager->seb_required());
    }

    /**
     * Test that user has capability to bypass SEB check.
     */
    public function test_user_can_bypass_seb_check() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        // Set the bypass SEB check capability to $USER.
        $this->assign_user_capability('quizaccess/seb:bypassseb', context_module::instance($this->quiz->cmid)->id);

        $accessmanager = new access_manager(new quiz($this->quiz,
                get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $this->assertTrue($accessmanager->can_bypass_seb());
    }

    /**
     * Test user does not have capability to bypass SEB check.
     */
    public function test_user_cannot_bypass_seb_check() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $accessmanager = new access_manager(new quiz($this->quiz,
                get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $this->assertFalse($accessmanager->can_bypass_seb());
    }

    /**
     * Test that the quiz Config Key matches the incoming request header.
     */
    public function test_access_keys_validate_with_config_key() {
        global $FULLME;
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $accessmanager = new access_manager(new quiz($this->quiz,
                get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));

        $configkey = quiz_settings::get_record(['quizid' => $this->quiz->id])->get('configkey');

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
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $accessmanager = new access_manager(new quiz($this->quiz,
                get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));

        $this->assertFalse($accessmanager->validate_access_keys());
    }

    /**
     * Test that config key is not checked when using client configuration with SEB.
     */
    public function test_config_key_not_checked_if_client_requirement_is_selected() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);
        $accessmanager = new access_manager(new quiz($this->quiz,
                get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $this->assertTrue($accessmanager->validate_access_keys());
    }

    /**
     * Test that if there are no browser exam keys for quiz, check is skipped.
     */
    public function test_no_browser_exam_keys_cause_check_to_be_skipped() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);

        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('allowedbrowserexamkeys', '');
        $settings->save();
        $accessmanager = new access_manager(new quiz($this->quiz,
            get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $this->assertTrue($accessmanager->validate_access_keys());
    }

    /**
     * Test that access fails if there is no hash in header.
     */
    public function test_access_keys_fail_if_browser_exam_key_header_does_not_exist() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);

        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('allowedbrowserexamkeys', hash('sha256', 'one') . "\n" . hash('sha256', 'two'));
        $settings->save();
        $accessmanager = new access_manager(new quiz($this->quiz,
            get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $this->assertFalse($accessmanager->validate_access_keys());
    }

    /**
     * Test that access fails if browser exam key doesn't match hash in header.
     */
    public function test_access_keys_fail_if_browser_exam_key_header_does_not_match_provided_hash() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);

        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('allowedbrowserexamkeys', hash('sha256', 'one') . "\n" . hash('sha256', 'two'));
        $settings->save();
        $accessmanager = new access_manager(new quiz($this->quiz,
            get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'] = hash('sha256', 'notwhatyouwereexpectinghuh');
        $this->assertFalse($accessmanager->validate_access_keys());
    }

    /**
     * Test that browser exam key matches hash in header.
     */
    public function test_browser_exam_keys_match_header_hash() {
        global $FULLME;

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);
        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $browserexamkey = hash('sha256', 'browserexamkey');
        $settings->set('allowedbrowserexamkeys', $browserexamkey); // Add a hashed BEK.
        $settings->save();
        $accessmanager = new access_manager(new quiz($this->quiz,
            get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course));

        // Set up dummy request.
        $FULLME = 'https://example.com/moodle/mod/quiz/attempt.php?attemptid=123&page=4';
        $expectedhash = hash('sha256', $FULLME . $browserexamkey);
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'] = $expectedhash;
        $this->assertTrue($accessmanager->validate_access_keys());
    }
}
