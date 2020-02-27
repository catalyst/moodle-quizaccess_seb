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
 * PHPUnit tests for helper class.
 *
 * @package    quizaccess_seb
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit tests for helper class.
 *
 * @copyright  2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_seb_helper_testcase extends advanced_testcase {
    /**
     * Test that we can check valid seb string.
     */
    public function test_is_valid_seb_config() {
        $validseb = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">
<plist version=\"1.0\"><dict><key>showTaskBar</key><true/><key>allowWlan</key><false/><key>showReloadButton</key><true/>"
            . "<key>showTime</key><false/><key>showInputLanguage</key><true/><key>allowQuit</key><true/>"
            . "<key>quitURLConfirm</key><true/><key>audioControlEnabled</key><true/><key>audioMute</key><false/>"
            . "<key>allowSpellCheck</key><false/><key>browserWindowAllowReload</key><true/><key>URLFilterEnable</key><true/>"
            . "<key>URLFilterEnableContentFilter</key><false/><key>hashedQuitPassword</key>"
            . "<string>9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08</string><key>URLFilterRules</key>"
            . "<array><dict><key>action</key><integer>1</integer><key>active</key><true/><key>expression</key>"
            . "<string>test.com</string><key>regex</key><false/></dict></array>"
            . "<key>sendBrowserExamKey</key><true/></dict></plist>\n";
        $invalidseb = 'Invalid seb';
        $emptyseb = '';

        $this->assertTrue(\quizaccess_seb\helper::is_valid_seb_config($validseb));
        $this->assertFalse(\quizaccess_seb\helper::is_valid_seb_config($invalidseb));
        $this->assertFalse(\quizaccess_seb\helper::is_valid_seb_config($emptyseb));
    }

    /**
     * Test that we can get seb file headers.
     */
    public function test_get_seb_file_headers() {
        $expiretime = 1582767914;
        $headers = \quizaccess_seb\helper::get_seb_file_headers($expiretime);

        $this->assertCount(5, $headers);
        $this->assertEquals('Cache-Control: private, max-age=1, no-transform', $headers[0]);
        $this->assertEquals('Expires: Thu, 27 Feb 2020 01:45:14 GMT', $headers[1]);
        $this->assertEquals('Pragma: no-cache', $headers[2]);
        $this->assertEquals('Content-Disposition: attachment; filename=config.seb', $headers[3]);
        $this->assertEquals('Content-Type: application/seb', $headers[4]);
    }
}
