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

        $quizsettings = new quiz_settings(0, $settings);
        $this->assertEmpty($quizsettings->get('config'));
        $quizsettings->create();
        $config = $quizsettings->get('config');
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">
<plist version=\"1.0\"><dict><key>showTaskBar</key><false/><key>allowWlan</key><false/><key>showReloadButton</key><false/>"
                . "<key>showTime</key><false/><key>showInputLanguage</key><false/><key>allowQuit</key><false/>"
                . "<key>quitURLConfirm</key><false/><key>audioControlEnabled</key><false/><key>audioMute</key><false/>"
                . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><false/><key>URLFilterEnable</key><false/>"
                . "<key>URLFilterEnableContentFilter</key><false/><key>hashedQuitPassword</key>"
                . "<string>9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08</string><key>URLFilterRules</key>"
                . "<array><dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key>"
                . "<string>test.com</string><key>regex</key><false/></dict></array></dict></plist>",
                trim($config));
    }

    /**
     * Test that config key is generated immediately prior to saving quiz settings.
     */
    public function test_config_key_is_created_from_quiz_settings() {
        $settings = $this->get_test_settings();

        $quizsettings = new quiz_settings(0, $settings);
        $this->assertEmpty($quizsettings->get('configkey'));
        $quizsettings->create();
        $configkey = $quizsettings->get('configkey');
        $this->assertEquals("f90eb44935b05da0fc4b1bc7399e7f07f97d29df8c7ae2c22a8c98070b33fe88",
                $configkey);
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
            'linkquitseb' => '0',
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
}
