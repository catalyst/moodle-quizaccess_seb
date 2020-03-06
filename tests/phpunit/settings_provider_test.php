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

use quizaccess_seb\tests\phpunit\quizaccess_seb_testcase;
use quizaccess_seb\quiz_settings;
use quizaccess_seb\settings_provider;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/base.php');

/**
 * PHPUnit tests for settings_provider.
 *
 * @copyright  2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_seb_settings_provider_testcase extends quizaccess_seb_testcase {

    /**
     * Mocked quiz form instance.
     * @var \mod_quiz_mod_form
     */
    protected $mockedquizform;

    /**
     * Test moodle form.
     * @var \MoodleQuickForm
     */
    protected $mockedform;

    /**
     * Context for testing.
     * @var \context
     */
    protected $context;

    /**
     * Test user.
     * @var \stdClass
     */
    protected $user;

    /**
     * Test role ID.
     * @var int
     */
    protected $roleid;

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * Helper method to set up form mocks.
     */
    protected function set_up_form_mocks() {
        if (empty($this->context)) {
            $this->context = context_module::instance($this->quiz->cmid);
        }

        $this->mockedquizform = $this->createMock('mod_quiz_mod_form');
        $this->mockedquizform->method('get_context')->willReturn($this->context);
        $this->mockedquizform->method('get_instance')->willReturn($this->quiz->id);
        $this->mockedform = new \MoodleQuickForm('test', 'post', '');
        $this->mockedform->addElement('static', 'security');
    }

    /**
     * Helper method to set up user and role for testing.
     */
    protected function set_up_user_and_role() {
        $this->user = $this->getDataGenerator()->create_user();

        $this->setUser($this->user);
        $this->roleid = $this->getDataGenerator()->create_role();

        $this->getDataGenerator()->role_assign($this->roleid, $this->user->id, $this->context->id);
    }

    /**
     * Capability data for testing.
     *
     * @return array
     */
    public function settings_capability_data_provider() {
        $data = [];

        foreach (settings_provider::get_seb_config_elements() as $name => $type) {
            $cap = settings_provider::build_setting_capability_name($name);
            $data[] = [$cap];
        }

        return $data;
    }

    /**
     * Test that settings types to be added to quiz settings, are part of quiz_settings persistent class.
     */
    public function test_setting_elements_are_part_of_quiz_settings_table() {
        $dbsettings = (array) (new quiz_settings())->to_record();
        $settingelements = settings_provider::get_seb_config_elements();
        $settingelements = (array) $this->strip_all_prefixes((object) $settingelements);

        // Get all elements to be added to form, that are not in the persistent quiz_settings class.
        $diffelements = array_diff_key($settingelements, $dbsettings);

        $this->assertEmpty($diffelements);
    }

    /**
     * Test that setting defaults only refer to settings defined in setting types.
     */
    public function test_setting_defaults_are_part_of_file_types() {
        $settingelements = settings_provider::get_seb_config_elements();
        $settingdefaults = settings_provider::get_seb_config_element_defaults();

        // Get all defaults that have no matching element in settings types.
        $diffelements = array_diff_key($settingdefaults, $settingelements);

        $this->assertEmpty($diffelements);
    }

    /**
     * Test that setting types only refer to settings defined in setting types.
     */
    public function test_setting_types_are_part_of_file_types() {
        $settingelements = settings_provider::get_seb_config_elements();
        $settingtypes = settings_provider::get_seb_config_element_types();

        // Get all defaults that have no matching element in settings types.
        $diffelements = array_diff_key($settingtypes, $settingelements);

        $this->assertEmpty($diffelements);
    }

    /**
     * Test that setting hideif rules only refer to settings defined in setting types, including the conditions.
     */
    public function test_setting_hideifs_are_part_of_file_types() {
        $settingelements = settings_provider::get_seb_config_elements();
        $settinghideifs = settings_provider::get_quiz_hideifs();

        // Add known additional elements.
        $settingelements['seb_templateid'] = '';
        $settingelements['filemanager_sebconfigfile'] = '';
        $settingelements['seb_suppresssebdownloadlink'] = '';
        $settingelements['seb_allowedbrowserexamkeys'] = '';

        // Get all defaults that have no matching element in settings types.
        $diffelements = array_diff_key($settinghideifs, $settingelements);

        // Check no diff for elements to hide.
        $this->assertEmpty($diffelements);

        // Check each element's to hide conditions that each condition refers to element in settings types.
        foreach ($settinghideifs as $conditions) {
            foreach ($conditions as $condition) {
                $this->assertTrue(array_key_exists($condition->get_element(), $settingelements));
            }
        }
    }

    /**
     * Test that exception thrown if we try to build capability name from the incorrect setting name.
     */
    public function test_build_setting_capability_name_incorrect_setting() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Incorrect SEB quiz setting broken');

        $broken = settings_provider::build_setting_capability_name('broken');
    }

    /**
     * Test we can build capability name from the the setting name.
     */
    public function test_build_setting_capability_name_correct_setting() {
        foreach (settings_provider::get_seb_config_elements() as $name => $type) {
            $expected = 'quizaccess/seb:manage_' . $name;
            $actual = settings_provider::build_setting_capability_name($name);

            $this->assertSame($expected, $actual);
        }
    }

    /**
     * Test SEB usage options.
     *
     * @param string $settingcapability Setting capability to check manual option against.
     *
     * @dataProvider settings_capability_data_provider
     */
    public function test_get_requiresafeexambrowser_options($settingcapability) {
        $this->setAdminUser();

        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);

        $options = settings_provider::get_requiresafeexambrowser_options($this->context);

        $this->assertCount(4, $options);
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_NO, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CONFIG_MANUALLY, $options));
        $this->assertFalse(array_key_exists(settings_provider::USE_SEB_TEMPLATE, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_UPLOAD_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CLIENT_CONFIG, $options));

        // Create a template.
        $this->create_template();

        // The template options should be visible now.
        $options = settings_provider::get_requiresafeexambrowser_options($this->context);
        $this->assertCount(5, $options);
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_TEMPLATE, $options));

        // A new user does not have the capability to use the file manager and template.
        $this->set_up_user_and_role();

        $options = settings_provider::get_requiresafeexambrowser_options($this->context);

        $this->assertCount(2, $options);
        $this->assertFalse(array_key_exists(settings_provider::USE_SEB_CONFIG_MANUALLY, $options));
        $this->assertFalse(array_key_exists(settings_provider::USE_SEB_TEMPLATE, $options));
        $this->assertFalse(array_key_exists(settings_provider::USE_SEB_UPLOAD_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CLIENT_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_NO, $options));

        assign_capability($settingcapability, CAP_ALLOW, $this->roleid, $this->context->id);
        $options = settings_provider::get_requiresafeexambrowser_options($this->context);
        $this->assertCount(3, $options);
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CONFIG_MANUALLY, $options));
        $this->assertFalse(array_key_exists(settings_provider::USE_SEB_TEMPLATE, $options));
        $this->assertFalse(array_key_exists(settings_provider::USE_SEB_UPLOAD_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CLIENT_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_NO, $options));

        assign_capability('quizaccess/seb:manage_seb_templateid', CAP_ALLOW, $this->roleid, $this->context->id);
        $options = settings_provider::get_requiresafeexambrowser_options($this->context);
        $this->assertCount(4, $options);
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CONFIG_MANUALLY, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_TEMPLATE, $options));
        $this->assertFalse(array_key_exists(settings_provider::USE_SEB_UPLOAD_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CLIENT_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_NO, $options));

        assign_capability('quizaccess/seb:manage_filemanager_sebconfigfile', CAP_ALLOW, $this->roleid, $this->context->id);
        $options = settings_provider::get_requiresafeexambrowser_options($this->context);
        $this->assertCount(5, $options);
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CONFIG_MANUALLY, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_TEMPLATE, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_UPLOAD_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_CLIENT_CONFIG, $options));
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_NO, $options));
    }

    /**
     * Test SEB usage options with conflicting permissions.
     */
    public function test_get_requiresafeexambrowser_options_with_conflicting_permissions() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        $template = $this->create_template();

        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('templateid', $template->get('id'));
        $settings->set('requiresafeexambrowser', settings_provider::USE_SEB_TEMPLATE);
        $settings->save();

        $this->set_up_user_and_role();

        $options = settings_provider::get_requiresafeexambrowser_options($this->context);

        // If there is nay conflict we return full list of options.
        $this->assertCount(5, $options);
        $this->assertTrue(array_key_exists(settings_provider::USE_SEB_TEMPLATE, $options));
    }

    /**
     * Test that SEB options and templates are frozen if conflicting permissions.
     */
    public function test_form_elements_are_frozen_if_conflicting_permissions() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        // Setup conflicting permissions.
        $template = $this->create_template();
        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('templateid', $template->get('id'));
        $settings->set('requiresafeexambrowser', settings_provider::USE_SEB_TEMPLATE);
        $settings->save();

        $this->set_up_user_and_role();

        assign_capability('quizaccess/seb:manage_seb_requiresafeexambrowser', CAP_ALLOW, $this->roleid, $this->context->id);
        assign_capability('quizaccess/seb:manage_seb_suppresssebdownloadlink', CAP_ALLOW, $this->roleid, $this->context->id);
        assign_capability('quizaccess/seb:manage_seb_allowedbrowserexamkeys', CAP_ALLOW, $this->roleid, $this->context->id);

        $this->set_up_form_mocks();

        settings_provider::add_seb_settings_fields($this->mockedquizform, $this->mockedform);

        $this->assertTrue($this->mockedform->isElementFrozen('seb_requiresafeexambrowser'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_templateid'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_suppresssebdownloadlink'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_allowedbrowserexamkeys'));
    }

    /**
     * Test that All settings are frozen if quiz was attempted and use seb with manual settings.
     */
    public function test_form_elements_are_locked_when_quiz_attempted_manual() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        $user = $this->getDataGenerator()->create_user();
        $this->attempt_quiz($this->quiz, $user);

        $this->setAdminUser();
        $this->set_up_form_mocks();

        settings_provider::add_seb_settings_fields($this->mockedquizform, $this->mockedform);

        $this->assertTrue($this->mockedform->isElementFrozen('seb_requiresafeexambrowser'));
        $this->assertTrue($this->mockedform->elementExists('filemanager_sebconfigfile'));
        $this->assertFalse($this->mockedform->elementExists('seb_templateid'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_suppresssebdownloadlink'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_allowedbrowserexamkeys'));

        foreach (settings_provider::get_seb_config_elements() as $name => $type) {
            $this->assertTrue($this->mockedform->isElementFrozen($name));
        }
    }

    /**
     * Test that All settings are frozen if a quiz was attempted and use template.
     */
    public function test_form_elements_are_locked_when_quiz_attempted_template() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        $template = $this->create_template();

        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('templateid', $template->get('id'));
        $settings->set('requiresafeexambrowser', settings_provider::USE_SEB_TEMPLATE);
        $settings->save();

        $user = $this->getDataGenerator()->create_user();
        $this->attempt_quiz($this->quiz, $user);

        $this->setAdminUser();
        $this->set_up_form_mocks();

        settings_provider::add_seb_settings_fields($this->mockedquizform, $this->mockedform);

        $this->assertTrue($this->mockedform->isElementFrozen('seb_requiresafeexambrowser'));
        $this->assertTrue($this->mockedform->elementExists('filemanager_sebconfigfile'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_templateid'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_suppresssebdownloadlink'));
        $this->assertTrue($this->mockedform->isElementFrozen('seb_allowedbrowserexamkeys'));

        foreach (settings_provider::get_seb_config_elements() as $name => $type) {
            $this->assertTrue($this->mockedform->isElementFrozen($name));
        }
    }

    /**
     * Test Suppress Safe Exam Browser download button setting in the form.
     */
    public function test_suppresssebdownloadlink_in_form() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        $this->set_up_user_and_role();

        assign_capability('quizaccess/seb:manage_seb_requiresafeexambrowser', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->set_up_form_mocks();

        // Shouldn't be in the form if no permissions.
        settings_provider::add_seb_settings_fields($this->mockedquizform, $this->mockedform);
        $this->assertFalse($this->mockedform->elementExists('seb_suppresssebdownloadlink'));

        // Should be in the form if we grant require permissions.
        assign_capability('quizaccess/seb:manage_seb_suppresssebdownloadlink', CAP_ALLOW, $this->roleid, $this->context->id);

        settings_provider::add_seb_settings_fields($this->mockedquizform, $this->mockedform);
        $this->assertTrue($this->mockedform->elementExists('seb_suppresssebdownloadlink'));
    }

    /**
     * Test Allowed Browser Exam Keys setting in the form.
     */
    public function test_allowedbrowserexamkeys_in_form() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CLIENT_CONFIG);
        $this->context = context_module::instance($this->quiz->cmid);

        $this->set_up_user_and_role();

        assign_capability('quizaccess/seb:manage_seb_requiresafeexambrowser', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->set_up_form_mocks();

        // Shouldn't be in the form if no permissions.
        settings_provider::add_seb_settings_fields($this->mockedquizform, $this->mockedform);
        $this->assertFalse($this->mockedform->elementExists('seb_allowedbrowserexamkeys'));

        // Should be in the form if we grant require permissions.
        assign_capability('quizaccess/seb:manage_seb_allowedbrowserexamkeys', CAP_ALLOW, $this->roleid, $this->context->id);
        settings_provider::add_seb_settings_fields($this->mockedquizform, $this->mockedform);
        $this->assertTrue($this->mockedform->elementExists('seb_allowedbrowserexamkeys'));
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
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->set_up_user_and_role();

        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');

        $draftitemid = $this->create_test_draftarea_file($xml);

        settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $this->quiz->cmid);

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');

        $this->assertCount(2, $files);
    }

    /**
     * Test deleting the $this->quiz->cmid itemid from the file area.
     */
    public function test_delete_uploaded_config_file() {
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->set_up_user_and_role();

        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $draftitemid = $this->create_test_draftarea_file($xml);

        settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $this->quiz->cmid);

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');
        $this->assertCount(2, $files);

        settings_provider::delete_uploaded_config_file($this->quiz->cmid);

        $files = $fs->get_area_files($this->context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');
        // The '.' directory.
        $this->assertCount(1, $files);
    }

    /**
     * Test getting the file from the context module id file area.
     */
    public function test_get_module_context_sebconfig_file() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        $this->set_up_user_and_role();

        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $draftitemid = $this->create_test_draftarea_file($xml);

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'quizaccess_seb', 'filemanager_sebconfigfile');
        $this->assertCount(0, $files);

        settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $this->quiz->cmid);

        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('requiresafeexambrowser', settings_provider::USE_SEB_UPLOAD_CONFIG);
        $settings->save();

        $file = settings_provider::get_module_context_sebconfig_file($this->quiz->cmid);

        $this->assertSame($file->get_content(), $xml);
    }

    /**
     * Test file manager options.
     */
    public function test_get_filemanager_options() {
        $expected = [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.seb']
        ];
        $this->assertSame($expected, settings_provider::get_filemanager_options());
    }

    /**
     * Test that users can or can not configure seb settings.
     */
    public function test_can_configure_seb() {
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->setAdminUser();

        $this->assertTrue(settings_provider::can_configure_seb($this->context));

        $this->set_up_user_and_role();

        $this->assertFalse(settings_provider::can_configure_seb($this->context));

        assign_capability('quizaccess/seb:manage_seb_requiresafeexambrowser', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertTrue(settings_provider::can_configure_seb($this->context));
    }

    /**
     * Test that users can or can not use seb template.
     */
    public function test_can_use_seb_template() {
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->setAdminUser();

        $this->assertTrue(settings_provider::can_use_seb_template($this->context));

        $this->set_up_user_and_role();

        $this->assertFalse(settings_provider::can_use_seb_template($this->context));

        assign_capability('quizaccess/seb:manage_seb_templateid', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertTrue(settings_provider::can_use_seb_template($this->context));
    }

    /**
     * Test that users can or can not upload seb config file.
     */
    public function test_can_upload_seb_file() {
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->setAdminUser();

        $this->assertTrue(settings_provider::can_upload_seb_file($this->context));

        $this->set_up_user_and_role();

        $this->assertFalse(settings_provider::can_upload_seb_file($this->context));

        assign_capability('quizaccess/seb:manage_filemanager_sebconfigfile', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertTrue(settings_provider::can_upload_seb_file($this->context));
    }

    /**
     * Test that users can or can not change Suppress Safe Exam Browser download button setting.
     */
    public function test_can_change_seb_suppresssebdownloadlink() {
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->setAdminUser();
        $this->assertTrue(settings_provider::can_change_seb_suppresssebdownloadlink($this->context));

        $this->set_up_user_and_role();

        $this->assertFalse(settings_provider::can_change_seb_suppresssebdownloadlink($this->context));

        assign_capability('quizaccess/seb:manage_seb_suppresssebdownloadlink', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertTrue(settings_provider::can_change_seb_suppresssebdownloadlink($this->context));
    }

    /**
     * Test that users can or can not change Allowed Browser Exam Keys setting.
     */
    public function test_can_change_seb_allowedbrowserexamkeys() {
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->setAdminUser();
        $this->assertTrue(settings_provider::can_change_seb_allowedbrowserexamkeys($this->context));

        $this->set_up_user_and_role();

        $this->assertFalse(settings_provider::can_change_seb_allowedbrowserexamkeys($this->context));

        assign_capability('quizaccess/seb:manage_seb_allowedbrowserexamkeys', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertTrue(settings_provider::can_change_seb_allowedbrowserexamkeys($this->context));
    }

    /**
     * Test that users can or can not Configure SEb manually
     *
     * @param string $settingcapability Setting capability to check manual option against.
     *
     * @dataProvider settings_capability_data_provider
     */
    public function test_can_configure_manually($settingcapability) {
        $this->quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->quiz->cmid);
        $this->setAdminUser();

        $this->assertTrue(settings_provider::can_configure_manually($this->context));

        $this->set_up_user_and_role();

        $this->assertFalse(settings_provider::can_configure_manually($this->context));

        assign_capability($settingcapability, CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertTrue(settings_provider::can_configure_manually($this->context));
    }

    /**
     * Test that we can check if the seb settings are locked.
     */
    public function test_is_seb_settings_locked() {
        $this->quiz = $this->create_test_quiz($this->course);
        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(settings_provider::is_seb_settings_locked($this->quiz->id));

        $this->attempt_quiz($this->quiz, $user);
        $this->assertTrue(settings_provider::is_seb_settings_locked($this->quiz->id));
    }

    /**
     * Test that we can check identify conflicting permissions if set to use template.
     */
    public function test_is_conflicting_permissions_for_manage_templates() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        // Create a template.
        $template = $this->create_template();
        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('templateid', $template->get('id'));
        $settings->set('requiresafeexambrowser', settings_provider::USE_SEB_TEMPLATE);
        $settings->save();

        $this->assertFalse(settings_provider::is_conflicting_permissions($this->context));

        $this->set_up_user_and_role();

        $this->assertTrue(settings_provider::is_conflicting_permissions($this->context));

        assign_capability('quizaccess/seb:manage_seb_templateid', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertFalse(settings_provider::is_conflicting_permissions($this->context));
    }

    /**
     * Test that we can check identify conflicting permissions if set to use own seb file.
     */
    public function test_is_conflicting_permissions_for_upload_seb_file() {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        // Save file.
        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $draftitemid = $this->create_test_draftarea_file($xml);
        settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $this->quiz->cmid);
        $settings = quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $settings->set('requiresafeexambrowser', settings_provider::USE_SEB_UPLOAD_CONFIG);
        $settings->save();

        $this->assertFalse(settings_provider::is_conflicting_permissions($this->context));

        $this->set_up_user_and_role();

        assign_capability('quizaccess/seb:manage_filemanager_sebconfigfile', CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertFalse(settings_provider::is_conflicting_permissions($this->context));
    }

    /**
     * Test that we can check identify conflicting permissions if set to use own configure manually.
     *
     * @param string $settingcapability Setting capability to check manual option against.
     *
     * @dataProvider settings_capability_data_provider
     */
    public function test_is_conflicting_permissions_for_configure_manually($settingcapability) {
        $this->setAdminUser();

        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->context = context_module::instance($this->quiz->cmid);

        $this->assertFalse(settings_provider::is_conflicting_permissions($this->context));

        $this->set_up_user_and_role();

        assign_capability($settingcapability, CAP_ALLOW, $this->roleid, $this->context->id);
        $this->assertFalse(settings_provider::is_conflicting_permissions($this->context));
    }

    /**
     * Test filter_by_prefix helper method.
     */
    public function test_filter_by_prefix() {
        $test = new stdClass();
        $test->one = 'one';
        $test->seb_two = 'two';
        $test->seb_seb_three = 'three';
        $test->four = 'four';

        $newsettings = (array)settings_provider::filter_by_prefix($test);
        $this->assertFalse(key_exists('one', $newsettings));
        $this->assertFalse(key_exists('four', $newsettings));

        $this->assertCount(2, $newsettings);
        $this->assertEquals('two', $newsettings['seb_two']);
        $this->assertEquals('three', $newsettings['seb_seb_three']);
    }

    /**
     * Test strip_all_prefixes helper method.
     */
    public function test_strip_all_prefixes() {
        $test = new stdClass();
        $test->one = 'one';
        $test->seb_two = 'two';
        $test->seb_seb_three = 'three';
        $test->four = 'four';

        $newsettings = (array)settings_provider::strip_all_prefixes($test);
        $this->assertFalse(key_exists('seb_two', $newsettings));
        $this->assertFalse(key_exists('seb_seb_three', $newsettings));

        $this->assertCount(4, $newsettings);
        $this->assertEquals('one', $newsettings['one']);
        $this->assertEquals('two', $newsettings['two']);
        $this->assertEquals('three', $newsettings['seb_three']);
        $this->assertEquals('four', $newsettings['four']);
    }

    /**
     * Test add_prefix helper method.
     */
    public function test_add_prefix() {
        $this->assertEquals('seb_one', settings_provider::add_prefix('one'));
        $this->assertEquals('seb_two', settings_provider::add_prefix('seb_two'));
        $this->assertEquals('seb_seb_three', settings_provider::add_prefix('seb_seb_three'));
        $this->assertEquals('seb_', settings_provider::add_prefix('seb_'));
        $this->assertEquals('seb_', settings_provider::add_prefix(''));
        $this->assertEquals('seb_one_seb', settings_provider::add_prefix('one_seb'));
    }

    /**
     * Test filter_plugin_settings helper method.
     */
    public function test_filter_plugin_settings() {
        $test = new stdClass();
        $test->one = 'one';
        $test->seb_two = 'two';
        $test->seb_seb_three = 'three';
        $test->four = 'four';

        $newsettings = (array)settings_provider::filter_plugin_settings($test);

        $this->assertFalse(key_exists('one', $newsettings));
        $this->assertFalse(key_exists('four', $newsettings));

        $this->assertCount(2, $newsettings);
        $this->assertEquals('two', $newsettings['two']);
        $this->assertEquals('three', $newsettings['seb_three']);
    }

}
