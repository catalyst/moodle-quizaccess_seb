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
 * PHPUnit tests for quiz_settings class.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\quiz_settings;
use quizaccess_seb\settings_provider;

defined('MOODLE_INTERNAL') || die();

class quizaccess_seb_quiz_settings_testcase extends advanced_testcase {

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that config is generated immediately prior to saving quiz settings.
     */
    public function test_config_is_created_from_quiz_settings() {
        $settings = $this->get_test_settings();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $quizsettings = new quiz_settings(0, $settings);
        $this->assertEmpty($quizsettings->get('config'));
        $quizsettings->create();
        $config = $quizsettings->get('config');
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">
<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key><true/>"
                . "<key>showTime</key><false/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
                . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><true/><key>audioMute</key><false/>"
                . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><true/>"
                . "<key>URLFilterEnableContentFilter</key><false/><key>hashedQuitPassword</key>"
                . "<string>9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08</string><key>URLFilterRules</key>"
                . "<array><dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key>"
                . "<string>test.com</string><key>regex</key><false/></dict></array></dict></plist>\n",
                $config);
    }

    public function test_config_is_updated_from_quiz_settings() {
        $settings = $this->get_test_settings();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $quizsettings = new quiz_settings(0, $settings);
        $this->assertEmpty($quizsettings->get('config'));
        $quizsettings->create();
        $config = $quizsettings->get('config');
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">
<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key><true/>"
            . "<key>showTime</key><false/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
            . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><true/><key>audioMute</key><false/>"
            . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><true/>"
            . "<key>URLFilterEnableContentFilter</key><false/><key>hashedQuitPassword</key>"
            . "<string>9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08</string><key>URLFilterRules</key>"
            . "<array><dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key>"
            . "<string>test.com</string><key>regex</key><false/></dict></array></dict></plist>\n",
            $config);
        $quizsettings->set('filterembeddedcontent', 1); // Alter the settings.
        $quizsettings->save();
        $config = $quizsettings->get('config');
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">
<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key><true/>"
            . "<key>showTime</key><false/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
            . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><true/><key>audioMute</key><false/>"
            . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><true/>"
            . "<key>URLFilterEnableContentFilter</key><true/><key>hashedQuitPassword</key>"
            . "<string>9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08</string><key>URLFilterRules</key>"
            . "<array><dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key>"
            . "<string>test.com</string><key>regex</key><false/></dict></array></dict></plist>\n",
            $config);
    }

    /**
     * Test that config key is generated immediately prior to saving quiz settings.
     */
    public function test_config_key_is_created_from_quiz_settings() {
        $settings = $this->get_test_settings();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $quizsettings = new quiz_settings(0, $settings);
        $this->assertEmpty($quizsettings->get('configkey'));
        $quizsettings->create();
        $configkey = $quizsettings->get('configkey');
        $this->assertEquals("e186cd8999af80662899a61b869c21c9dc4374d3a0c1e3f1f7c14ee306fbf829",
                $configkey);
    }

    /**
     * Test that config key is generated immediately prior to saving quiz settings.
     */
    public function test_config_key_is_updated_from_quiz_settings() {
        $settings = $this->get_test_settings();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $quizsettings = new quiz_settings(0, $settings);
        $this->assertEmpty($quizsettings->get('configkey'));
        $quizsettings->create();
        $configkey = $quizsettings->get('configkey');
        $this->assertEquals("e186cd8999af80662899a61b869c21c9dc4374d3a0c1e3f1f7c14ee306fbf829",
                $configkey);
        $quizsettings->set('filterembeddedcontent', 1); // Alter the settings.
        $quizsettings->save();
        $configkey = $quizsettings->get('configkey');
        $this->assertEquals("4b667ccbf38346fc044dbab52e527256e0c217da15c439000a71d7c5fe335efd",
            $configkey);
    }

    /**
     * Test that different URL filter expressions are turned into config XML.
     *
     * @param stdClass $settings Quiz settings
     * @param string $expectedxml SEB Config XML.
     *
     * @dataProvider filter_rules_provider
     */
    public function test_filter_rules_added_to_config(stdClass $settings, string $expectedxml) {
        $quizsettings = new quiz_settings(0, $settings);
        $this->assertEmpty($quizsettings->get('config'));
        $quizsettings->create();
        $config = $quizsettings->get('config');
        $this->assertEquals($expectedxml, $config);
    }

    /**
     * Test that browser keys are validated and retrieved as an array instead of string.
     */
    public function test_browser_exam_keys_are_retrieved_as_array() {
        $quizsettings = new quiz_settings();
        $quizsettings->set('allowedbrowserexamkeys', "one two,three\nfour");
        $retrievedkeys = $quizsettings->get('allowedbrowserexamkeys');
        $this->assertEquals(['one', 'two', 'three', 'four'], $retrievedkeys);
    }

    /**
     * @param $bek
     * @param $expectederrorstring
     *
     * @dataProvider bad_browser_exam_key_provider
     */
    public function test_browser_exam_keys_validation_errors($bek, $expectederrorstring) {
        $quizsettings = new quiz_settings();
        $quizsettings->set('allowedbrowserexamkeys', $bek);
        $quizsettings->validate();
        $errors = $quizsettings->get_errors();
        $this->assertContains($expectederrorstring, $errors);
    }

    public function test_config_file_uploaded_converted_to_config() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>hashedQuitPassword</key><string>hashedpassword</string>"
                . "<key>allowWlan</key><false/></dict></plist>\n";
        $itemid = $this->create_test_file($xml);
        $quizsettings = new quiz_settings(0, (object) [
            'quizid' => 1,
            'requiresafeexambrowser' => settings_provider::USE_SEB_UPLOAD_CONFIG,
        ]);
        $quizsettings->set('sebconfigfile', $itemid);
        $quizsettings->save();
        $config = $quizsettings->get('config');
        $this->assertEquals($xml, $config);
    }

    public function test_no_config_file_uploaded_doesnt_overwrite_config() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $quizsettings = new quiz_settings(0, (object) [
            'quizid' => 1,
            'requiresafeexambrowser' => settings_provider::USE_SEB_UPLOAD_CONFIG,
        ]);
        $quizsettings->set('showsebtaskbar', 0);
        $quizsettings->save();
        $originalconfig = $quizsettings->get('config');
        $quizsettings->set('sebconfigfile', 999);
        $quizsettings->save();
        $newconfig = $quizsettings->get('config');
        $this->assertEquals($originalconfig, $newconfig);
    }

    public function test_password_set_with_upload_overwrites_file_setting() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
            . "<plist version=\"1.0\"><dict><key>hashedQuitPassword</key><string>hashedpassword</string>"
            . "<key>allowWlan</key><false/></dict></plist>\n";
        $itemid = $this->create_test_file($xml);
        $quizsettings = new quiz_settings(0, (object) [
            'quizid' => 1,
            'requiresafeexambrowser' => settings_provider::USE_SEB_UPLOAD_CONFIG,
        ]);
        $quizsettings->set('sebconfigfile', $itemid);
        $newpassword = 'newpassword';
        $quizsettings->set('quitpassword', $newpassword);
        $quizsettings->save();
        $config = $quizsettings->get('config');
        $this->assertNotEquals($xml, $config);
        $plist = new \quizaccess_seb\property_list($config);
        $this->assertEquals(hash('sha256', $newpassword), $plist->get_element_value('hashedQuitPassword'));
    }

    public function test_no_password_set_with_upload_doesnt_overwrite_file_setting() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
            . "<plist version=\"1.0\"><dict><key>hashedQuitPassword</key><string>hashedpassword</string>"
            . "<key>allowWlan</key><false/></dict></plist>\n";
        $itemid = $this->create_test_file($xml);
        $quizsettings = new quiz_settings(0, (object) [
            'quizid' => 1,
            'requiresafeexambrowser' => settings_provider::USE_SEB_UPLOAD_CONFIG,
        ]);
        $quizsettings->set('sebconfigfile', $itemid);
        $quizsettings->set('quitpassword', '');
        $quizsettings->save();
        $config = $quizsettings->get('config');
        $this->assertEquals($xml, $config);
        $plist = new \quizaccess_seb\property_list($config);
        $this->assertEquals('hashedpassword', $plist->get_element_value('hashedQuitPassword'));
    }

    /**
     * Bad browser exam key data provider.
     *
     * @return array
     */
    public function bad_browser_exam_key_provider() : array {
        return [
            'Short string' => ['fdsf434r',
                    'A key should be a 64-character hex string.'],
            'Non hex string' => ['aadf6799aadf6789aadf6789aadf6789aadf6789aadf6789aadf6789aadf678!',
                    'A key should be a 64-character hex string.'],
            'Non unique' => ["aadf6799aadf6789aadf6789aadf6789aadf6789aadf6789aadf6789aadf6789"
                    . "\naadf6799aadf6789aadf6789aadf6789aadf6789aadf6789aadf6789aadf6789", 'The keys must all be different.'],
        ];
    }

    /**
     * Provide settings for different filter rules.
     *
     * @return array Test data.
     */
    public function filter_rules_provider() : array {
        return [
            'enabled simple expessions' => [
                (object) [
                    'requiresafeexambrowser' => settings_provider::USE_SEB_CONFIG_MANUALLY,
                    'quizid' => 1,
                    'expressionsallowed' => "test.com\nsecond.hello",
                    'regexallowed' => '',
                    'expressionsblocked' => '',
                    'regexblocked' => '',
                ],
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key>"
                . "<true/><key>showTime</key><true/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
                . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><false/><key>audioMute</key><false/>"
                . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><false/>"
                . "<key>URLFilterEnableContentFilter</key><false/><key>URLFilterRules</key><array>"
                . "<dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key><string>test.com</string>"
                . "<key>regex</key><false/></dict><dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key>"
                . "<string>second.hello</string><key>regex</key><false/></dict></array></dict></plist>\n",
            ],
            'blocked simple expessions' => [
                (object) [
                    'requiresafeexambrowser' => settings_provider::USE_SEB_CONFIG_MANUALLY,
                    'quizid' => 1,
                    'expressionsallowed' => '',
                    'regexallowed' => '',
                    'expressionsblocked' => "test.com\nsecond.hello",
                    'regexblocked' => '',
                ],
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key>"
                . "<true/><key>showTime</key><true/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
                . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><false/><key>audioMute</key><false/>"
                . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><false/>"
                . "<key>URLFilterEnableContentFilter</key><false/><key>URLFilterRules</key><array>"
                . "<dict><key>action</key><integer>0</integer><key>active</key><true/><key>expression</key><string>test.com</string>"
                . "<key>regex</key><false/></dict><dict><key>action</key><integer>0</integer><key>active</key><true/><key>expression</key>"
                . "<string>second.hello</string><key>regex</key><false/></dict></array></dict></plist>\n",
            ],
            'enabled regex expessions' => [
                (object) [
                    'requiresafeexambrowser' => settings_provider::USE_SEB_CONFIG_MANUALLY,
                    'quizid' => 1,
                    'expressionsallowed' => '',
                    'regexallowed' => "test.com\nsecond.hello",
                    'expressionsblocked' => '',
                    'regexblocked' => '',
                ],
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key>"
                . "<true/><key>showTime</key><true/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
                . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><false/><key>audioMute</key><false/>"
                . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><false/>"
                . "<key>URLFilterEnableContentFilter</key><false/><key>URLFilterRules</key><array>"
                . "<dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key><string>test.com</string>"
                . "<key>regex</key><true/></dict><dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key>"
                . "<string>second.hello</string><key>regex</key><true/></dict></array></dict></plist>\n",
            ],
            'blocked regex expessions' => [
                (object) [
                    'requiresafeexambrowser' => settings_provider::USE_SEB_CONFIG_MANUALLY,
                    'quizid' => 1,
                    'expressionsallowed' => '',
                    'regexallowed' => '',
                    'expressionsblocked' => '',
                    'regexblocked' => "test.com\nsecond.hello",
                ],
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key>"
                . "<true/><key>showTime</key><true/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
                . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><false/><key>audioMute</key><false/>"
                . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><false/>"
                . "<key>URLFilterEnableContentFilter</key><false/><key>URLFilterRules</key><array>"
                . "<dict><key>action</key><integer>0</integer><key>active</key><true/><key>expression</key><string>test.com</string>"
                . "<key>regex</key><true/></dict><dict><key>action</key><integer>0</integer><key>active</key><true/><key>expression</key>"
                . "<string>second.hello</string><key>regex</key><true/></dict></array></dict></plist>\n",
            ],
            'multiple simple expessions' => [
                (object) [
                    'requiresafeexambrowser' => settings_provider::USE_SEB_CONFIG_MANUALLY,
                    'quizid' => 1,
                    'expressionsallowed' => "*",
                    'regexallowed' => '',
                    'expressionsblocked' => '',
                    'regexblocked' => "test.com\nsecond.hello",
                ],
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n"
                . "<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key>"
                . "<true/><key>showTime</key><true/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
                . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><false/><key>audioMute</key><false/>"
                . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><false/>"
                . "<key>URLFilterEnableContentFilter</key><false/><key>URLFilterRules</key><array><dict><key>action</key>"
                . "<integer>1</integer><key>active</key><true/><key>expression</key><string>*</string><key>regex</key><false/></dict>"
                . "<dict><key>action</key><integer>0</integer><key>active</key><true/><key>expression</key><string>test.com</string>"
                . "<key>regex</key><true/></dict><dict><key>action</key><integer>0</integer><key>active</key><true/><key>expression</key>"
                . "<string>second.hello</string><key>regex</key><true/></dict></array></dict></plist>\n",
            ],
        ];
    }

    /**
     * Get a test object containing mock test settings.
     *
     * @return stdClass Settings.
     */
    private function get_test_settings() : stdClass {
        return (object) [
            'quizid' => 1,
            'requiresafeexambrowser' => '1',
            'sebconfigfile' => '373552893',
            'showsebtaskbar' => '1',
            'showwificontrol' => '0',
            'showreloadbutton' => '1',
            'showtime' => '0',
            'showkeyboardlayout' => '1',
            'allowuserquitseb' => '1',
            'quitpassword' => 'test',
            'linkquitseb' => '',
            'userconfirmquit' => '1',
            'enableaudiocontrol' => '1',
            'muteonstartup' => '0',
            'allowspellchecking' => '0',
            'allowreloadinexam' => '1',
            'activateurlfiltering' => '1',
            'filterembeddedcontent' => '0',
            'expressionsallowed' => 'test.com',
            'regexallowed' => '',
            'expressionsblocked' => '',
            'regexblocked' => '',
            'suppresssebdownloadlink' => '1',
        ];
    }

    /**
     * Create a file in the current user's draft file area.
     *
     * @param string $xml
     * @return int Item ID of file.
     *
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    private function create_test_file(string $xml) : int {
        global $USER;
        $itemid = 999;
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => 'test.xml'
        ];
        $fs->create_file_from_string($filerecord, $xml);
        return $itemid;
    }
}
