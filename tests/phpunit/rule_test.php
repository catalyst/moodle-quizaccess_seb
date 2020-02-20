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

use quizaccess_seb\quiz_settings;
use quizaccess_seb\settings_provider;
use quizaccess_seb\tests\phpunit\quizaccess_seb_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/mod/quiz/accessrule/seb/rule.php"); // Include plugin rule class.
require_once($CFG->dirroot . "/mod/quiz/mod_form.php"); // Include plugin rule class.
require_once(__DIR__ . '/base.php');

class quizaccess_seb_rule_testcase extends quizaccess_seb_testcase {

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
     * A helper method to make the rule form the currently created quiz and  course.
     *
     * @return \quiz_access_rule_base|null
     */
    private function make_rule() {
        return quizaccess_seb::make(
            new quiz($this->quiz, get_coursemodule_from_id('quiz', $this->quiz->cmid), $this->course),
            0,
            true
        );
    }

    /**
     * Test no errors are found with valid data.
     *
     * @param string $setting
     * @param string $data
     *
     * @dataProvider valid_form_data_provider
     */
    public function test_validate_settings_with_valid_data(string $setting, string $data) {
        $this->setAdminUser();
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $form = $this->createMock('mod_quiz_mod_form');
        $form->method('get_context')->willReturn(context_module::instance($this->quiz->cmid));

        // Validate settings with a dummy form.
        $errors = quizaccess_seb::validate_settings_form_fields([], [
            'instance' => $this->quiz->id,
            'coursemodule' => $this->quiz->cmid,
            $setting => $data
        ], [], $form);
        $this->assertEmpty($errors);
    }

    /**
     * Test errors are found with invalid data.
     *
     * @param string $setting
     * @param string $data
     *
     * @dataProvider invalid_form_data_provider
     */
    public function test_validate_settings_with_invalid_data(string $setting, string $data) {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $form = $this->createMock('mod_quiz_mod_form');
        $form->method('get_context')->willReturn(context_module::instance($this->quiz->cmid));

        // Validate settings with a dummy form and quiz instance.
        $errors = quizaccess_seb::validate_settings_form_fields([], [
            'instance' => $this->quiz->id,
            'coursemodule' => $this->quiz->cmid,
            $setting => $data
        ], [], $form);
        $this->assertEquals([$setting => 'Data submitted is invalid'], $errors);
    }

    /**
     * Test settings validation is not run if settings are locked.
     */
    public function test_settings_validation_is_not_run_if_settings_are_locked() {
        $user = $this->getDataGenerator()->create_user();
        $this->quiz = $this->create_test_quiz($this->course);
        $this->attempt_quiz($this->quiz, $user);

        $this->setAdminUser();

        $form = $this->createMock('mod_quiz_mod_form');
        $form->method('get_context')->willReturn(context_module::instance($this->quiz->cmid));

        // Validate settings with a dummy form and quiz instance.
        $errors = quizaccess_seb::validate_settings_form_fields([], [
            'instance' => $this->quiz->id,
            'coursemodule' => $this->quiz->cmid, 'seb_requiresafeexambrowser' => 'Uh oh!'
        ], [], $form);
        $this->assertEmpty($errors);
    }

    /**
     * Test bypassing validation if user don't have permissions to manage seb settings.
     */
    public function test_validate_settings_is_not_run_if_a_user_do_not_have_permissions_to_manage_seb_settings() {
        // Set the user who can't change seb settings. So validation should be bypassed.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $form = $this->createMock('mod_quiz_mod_form');
        $form->method('get_context')->willReturn(context_module::instance($this->quiz->cmid));

        // Validate settings with a dummy form and quiz instance.
        $errors = quizaccess_seb::validate_settings_form_fields([], [
            'instance' => $this->quiz->id,
            'coursemodule' => $this->quiz->cmid, 'seb_requiresafeexambrowser' => 'Uh oh!'
        ], [], $form);
        $this->assertEmpty($errors);
    }

    /**
     * Test settings are saved to DB.
     */
    public function test_create_settings_with_existing_quiz() {
        global $DB;

        $this->setAdminUser();
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_NO);

        $this->assertFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $this->quiz->id]));
        $this->quiz->seb_requiresafeexambrowser = settings_provider::USE_SEB_CONFIG_MANUALLY;
        quizaccess_seb::save_settings($this->quiz);
        $this->assertNotFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $this->quiz->id]));
    }

    /**
     * Test settings are not saved to DB if settings are locked.
     */
    public function test_settings_are_not_saved_if_settings_are_locked() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $this->setAdminUser();
        $this->quiz = $this->create_test_quiz($this->course);
        $this->attempt_quiz($this->quiz, $user);

        $this->setAdminUser();
        $this->quiz->seb_requiresafeexambrowser = settings_provider::USE_SEB_CONFIG_MANUALLY;
        quizaccess_seb::save_settings($this->quiz);
        $this->assertFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $this->quiz->id]));
    }

    /**
     * Test nothing happens when deleted is called without settings saved.
     */
    public function test_delete_settings_without_existing_settings() {
        global $DB;
        $this->setAdminUser();

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
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        // Using a generator will create the quiz_settings record.
        $this->assertNotFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $this->quiz->id]));
        quizaccess_seb::delete_settings($this->quiz);
        $this->assertFalse($DB->record_exists('quizaccess_seb_quizsettings', ['quizid' => $this->quiz->id]));
    }

    /**
     * Test access prevented if access keys are invalid.
     */
    public function test_access_prevented_if_access_keys_invalid() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $rule = $this->make_rule();

        // Create an event sink, trigger event and retrieve event.
        $sink = $this->redirectEvents();

        // Check that correct error message is returned.
        $errormsg = $rule->prevent_access();
        $this->assertNotEmpty($errormsg);
        $this->assertContains("The config key or browser exam keys could not be validated. "
                . "Please ensure you are using the Safe Exam Browser with correct configuration file.", $errormsg);
        $this->assertContains("https://safeexambrowser.org/download_en.html", $errormsg);
        $this->assertContains("sebs://www.example.com/moodle/mod/quiz/accessrule/seb/config.php", $errormsg);
        $this->assertContains("https://www.example.com/moodle/mod/quiz/accessrule/seb/config.php", $errormsg);

        $events = $sink->get_events();
        $this->assertEquals(1, count($events));
        $event = reset($events);

        // Test that the event data is as expected.
        $this->assertInstanceOf('\quizaccess_seb\event\access_prevented', $event);
        $this->assertEquals('Invalid SEB config key', $event->other['reason']);
    }

    /**
     * Test access not prevented if config key matches header.
     */
    public function test_access_allowed_if_config_key_valid() {
        global $FULLME;

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        // Set quiz setting to require seb.
        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $configkey = $quizsettings->get('configkey');

        // Set up dummy request.
        $FULLME = 'https://example.com/moodle/mod/quiz/attempt.php?attemptid=123&page=4';
        $expectedhash = hash('sha256', $FULLME . $configkey);
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH'] = $expectedhash;

        $rule = $this->make_rule();
        // Check that correct error message is returned.
        $this->assertFalse($rule->prevent_access());
    }

    /**
     * Test access not prevented if browser exam keys match headers.
     */
    public function test_access_allowed_if_browser_exam_keys_valid() {
        global $FULLME;
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        // Set quiz setting to require seb and save BEK.
        $browserexamkey = hash('sha256', 'testkey');
        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('requiresafeexambrowser', settings_provider::USE_SEB_CLIENT_CONFIG); // Doesn't check config key.
        $quizsettings->set('allowedbrowserexamkeys', $browserexamkey);
        $quizsettings->save();

        // Set up dummy request.
        $FULLME = 'https://example.com/moodle/mod/quiz/attempt.php?attemptid=123&page=4';
        $expectedhash = hash('sha256', $FULLME . $browserexamkey);
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'] = $expectedhash;

        $rule = $this->make_rule();
        // Check that correct error message is returned.
        $this->assertFalse($rule->prevent_access());
    }

    /**
     * Test access prevented if browser exam keys do not match headers.
     */
    public function test_access_prevented_if_browser_exam_keys_are_invalid() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        // Set quiz setting to require seb and save BEK.
        $browserexamkey = hash('sha256', 'testkey');
        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('requiresafeexambrowser', settings_provider::USE_SEB_CLIENT_CONFIG); // Doesn't check config key.
        $quizsettings->set('allowedbrowserexamkeys', $browserexamkey);
        $quizsettings->save();

        // Set up dummy request.
        $_SERVER['HTTP_X_SAFEEXAMBROWSER_REQUESTHASH'] = 'Broken key';

        $rule = $this->make_rule();

        // Create an event sink, trigger event and retrieve event.
        $sink = $this->redirectEvents();

        // Check that correct error message is returned.
        $errormsg = $rule->prevent_access();
        $this->assertNotEmpty($errormsg);
        $this->assertContains("The config key or browser exam keys could not be validated. "
            . "Please ensure you are using the Safe Exam Browser with correct configuration file.", $errormsg);
        $this->assertContains("https://safeexambrowser.org/download_en.html", $errormsg);
        $this->assertContains("sebs://www.example.com/moodle/mod/quiz/accessrule/seb/config.php", $errormsg);
        $this->assertContains("https://www.example.com/moodle/mod/quiz/accessrule/seb/config.php", $errormsg);

        $events = $sink->get_events();
        $this->assertEquals(1, count($events));
        $event = reset($events);

        // Test that the event data is as expected.
        $this->assertInstanceOf('\quizaccess_seb\event\access_prevented', $event);
        $this->assertEquals('Invalid SEB browser key', $event->other['reason']);
    }

    /**
     * Test access allowed if using client configuration and SEB user agent header is valid.
     */
    public function test_access_allowed_if_using_client_config_basic_header_is_valid() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        // Set quiz setting to require seb.
        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('requiresafeexambrowser', settings_provider::USE_SEB_CLIENT_CONFIG); // Doesn't check config key.
        $quizsettings->save();

        // Set up basic dummy request.
        $_SERVER['HTTP_USER_AGENT'] = 'SEB_TEST_SITE';

        $rule = $this->make_rule();
        // Check that correct error message is returned.
        $this->assertFalse($rule->prevent_access());
    }

    /**
     * Test access prevented if using client configuration and SEB user agent header is invalid.
     */
    public function test_access_prevented_if_using_client_configuration_and_basic_head_is_invalid() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        // Set quiz setting to require seb.
        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('requiresafeexambrowser', settings_provider::USE_SEB_CLIENT_CONFIG); // Doesn't check config key.
        $quizsettings->save();

        // Set up basic dummy request.
        $_SERVER['HTTP_USER_AGENT'] = 'WRONG_TEST_SITE';

        // Create an event sink, trigger event and retrieve event.
        $sink = $this->redirectEvents();

        $rule = $this->make_rule();
        // Check that correct error message is returned.
        $this->assertContains(
            'This quiz has been configured to use the Safe Exam Browser with client configuration.',
            $rule->prevent_access()
        );

        $events = $sink->get_events();
        $this->assertEquals(1, count($events));
        $event = reset($events);

        // Test that the event data is as expected.
        $this->assertInstanceOf('\quizaccess_seb\event\access_prevented', $event);
        $this->assertEquals('No SEB browser is being used', $event->other['reason']);
    }

    /**
     * Test access not prevented if SEB not required.
     */
    public function test_access_allowed_if_seb_not_required() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        // Set quiz setting to not require seb.
        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('requiresafeexambrowser', settings_provider::USE_SEB_NO);
        $quizsettings->save();

        $rule = $this->make_rule();

        // The rule will not exist as the settings are not configured for SEB usage.
        $this->assertNull($rule);
    }

    /**
     * Test access not prevented if USER has bypass capability.
     */
    public function test_access_allowed_if_user_has_bypass_capability() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        // Set quiz setting to require seb.
        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('requiresafeexambrowser', settings_provider::USE_SEB_CONFIG_MANUALLY);
        $quizsettings->save();

        // Set the bypass SEB check capability to $USER.
        $this->assign_user_capability('quizaccess/seb:bypassseb', context_module::instance($this->quiz->cmid)->id);

        $rule = $this->make_rule();

        // Check that correct error message is returned.
        $this->assertFalse($rule->prevent_access());
    }

    /**
     * Test that quiz form cannot be saved if the global settings are set to require a password and no password is set.
     */
    public function test_mod_quiz_form_cannot_be_saved_if_global_settings_force_quiz_password_and_none_is_set() {
        // Set global settings to require quiz password but set password to be empty.
        set_config('quizpasswordrequired', '1', 'quizaccess_seb');
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $form = $this->createMock('mod_quiz_mod_form');
        $form->method('get_context')->willReturn(context_module::instance($this->quiz->cmid));

        // Validate settings with a dummy form.
        $errors = quizaccess_seb::validate_settings_form_fields([], [
            'instance' => $this->quiz->id,
            'coursemodule' => $this->quiz->cmid,
        ], [], $form);

        $this->assertContains(get_string('passwordnotset', 'quizaccess_seb'), $errors);
    }

    /**
     * Test that access to quiz is allowed if global setting is set to restrict quiz if no quiz password is set, and global quiz
     * password is set.
     */
    public function test_mod_quiz_form_can_be_saved_if_global_settings_force_quiz_password_and_is_set() {
        // Set global settings to require quiz password but set password to be empty.
        set_config('quizpasswordrequired', '1', 'quizaccess_seb');

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $form = $this->createMock('mod_quiz_mod_form');
        $form->method('get_context')->willReturn(context_module::instance($this->quiz->cmid));

        // Validate settings with a dummy form.
        $errors = quizaccess_seb::validate_settings_form_fields([], [
            'instance' => $this->quiz->id,
            'coursemodule' => $this->quiz->cmid,
            'quizpassword' => 'set'
        ], [], $form);
        $this->assertNotContains(get_string('passwordnotset', 'quizaccess_seb'), $errors);
    }

    /**
     * Test get_download_button_only, checks for empty config setting quizaccess_seb/downloadlink.
     */
    public function test_get_download_button_only() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $rule = $this->make_rule();
        $reflection = new \ReflectionClass('quizaccess_seb');
        $method = $reflection->getMethod('get_download_button_only');
        $method->setAccessible(true);

        // The current default contents.
        $this->assertContains('https://safeexambrowser.org/download_en.html', $method->invoke($rule));

        set_config('downloadlink', '', 'quizaccess_seb');

        // Will not return any button if the URL is empty.
        $this->assertSame('', $method->invoke($rule));
    }

    /**
     * Test display_quit_button. If attempt count is greater than 0
     */
    public function test_display_quit_button() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);

        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('linkquitseb', "http://test.quit.link");
        $quizsettings->save();

        $user = $this->getDataGenerator()->create_user();
        $this->attempt_quiz($this->quiz, $user);
        $this->setUser($user);

        // Create the rule.
        $rule = $this->make_rule();

        // Set-up the button to be called.
        $reflection = new \ReflectionClass('quizaccess_seb');
        $method = $reflection->getMethod('display_quit_button');
        $method->setAccessible(true);

        $button = $method->invoke($rule);
        $this->assertContains("http://test.quit.link", $button);
    }

    /**
     * Test description, checks for a valid SEB session and attempt count .
     */
    public function test_description() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);

        $quizsettings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('linkquitseb', "http://test.quit.link");
        $quizsettings->save();

        // Set up basic dummy request.
        $_SERVER['HTTP_USER_AGENT'] = 'SEB_TEST_SITE';

        $user = $this->getDataGenerator()->create_user();
        $this->attempt_quiz($this->quiz, $user);

        // Create the rule.
        $rule = $this->make_rule();

        $description = $rule->description();
        $this->assertCount(2, $description);
        $this->assertEquals($description[0], get_string('sebrequired', 'quizaccess_seb'));
        $this->assertEquals($description[1], '');

        // Set the user as display_quit_button() uses the global $USER.
        $this->setUser($user);
        $description = $rule->description();
        $this->assertCount(2, $description);
        $this->assertEquals($description[0], get_string('sebrequired', 'quizaccess_seb'));

        // The button is contained in the description when a quiz attempt is finished.
        $this->assertContains("http://test.quit.link", $description[1]);
    }

    /**
     * Provider to return valid form field data when saving settings.
     *
     * @return array
     */
    public function valid_form_data_provider() : array {
        return [
            'valid seb_requiresafeexambrowser' => ['seb_requiresafeexambrowser', 0],
            'valid seb_linkquitseb0' => ['seb_linkquitseb', 'http://safeexambrowser.org/macosx'],
            'valid seb_linkquitseb1' => ['seb_linkquitseb', 'safeexambrowser.org/macosx'],
            'valid seb_linkquitseb2' => ['seb_linkquitseb', 'www.safeexambrowser.org/macosx'],
            'valid seb_linkquitseb3' => ['seb_linkquitseb', 'any.type.of.url.looking.thing'],
            'valid seb_linkquitseb4' => ['seb_linkquitseb', 'http://any.type.of.url.looking.thing'],
        ];
    }

    /**
     * Provider to return invalid form field data when saving settings.
     *
     * @return array
     */
    public function invalid_form_data_provider() : array {
        return [
            'invalid seb_requiresafeexambrowser' => ['seb_requiresafeexambrowser', 'Uh oh!'],
            'invalid seb_linkquitseb0' => ['seb_linkquitseb', '\0'],
            'invalid seb_linkquitseb1' => ['seb_linkquitseb', 'invalid url'],
            'invalid seb_linkquitseb2' => ['seb_linkquitseb', 'http]://safeexambrowser.org/macosx'],
            'invalid seb_linkquitseb3' => ['seb_linkquitseb', '0'],
            'invalid seb_linkquitseb4' => ['seb_linkquitseb', 'seb://any.type.of.url.looking.thing'],
        ];
    }
}
