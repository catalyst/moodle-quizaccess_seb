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
 * PHPUnit tests for backup and restore functionality.
 *
 * @package    quizaccess_seb
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\tests\phpunit\quizaccess_seb_testcase;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/base.php');

/**
 * PHPUnit tests for backup and restore functionality.
 *
 * @copyright  2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_seb_backup_restore_testcase extends quizaccess_seb_testcase {

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * A helper method to validate backup and restore results.
     *
     * @param cm_info $newcm Restored course_module object.
     */
    protected function validate_backup_restore(cm_info $newcm) {
        $this->assertEquals(2, quizaccess_seb\quiz_settings::count_records());
        $actual = \quizaccess_seb\quiz_settings::get_record(['quizid' => $newcm->instance]);


        $expected = \quizaccess_seb\quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $this->assertEquals($expected->get('templateid'), $actual->get('templateid'));
        $this->assertEquals($expected->get('requiresafeexambrowser'), $actual->get('requiresafeexambrowser'));
        $this->assertEquals($expected->get('showsebdownloadlink'), $actual->get('showsebdownloadlink'));
        $this->assertEquals($expected->get('allowuserquitseb'), $actual->get('allowuserquitseb'));
        $this->assertEquals($expected->get('quitpassword'), $actual->get('quitpassword'));
        $this->assertEquals($expected->get('allowedbrowserexamkeys'), $actual->get('allowedbrowserexamkeys'));

        // Validate specific SEB config settings.
        foreach (\quizaccess_seb\settings_provider::get_seb_config_elements() as $name => $notused) {
            $name = preg_replace("/^seb_/", "", $name);
            $this->assertEquals($expected->get($name), $actual->get($name));
        }
    }

    /**
     * Test backup and restore when no seb.
     */
    public function test_backup_restore_no_seb() {
        $this->quiz = $this->create_test_quiz($this->course, \quizaccess_seb\settings_provider::USE_SEB_NO);
        $this->assertEquals(0, quizaccess_seb\quiz_settings::count_records());

        $this->backup_and_restore_quiz();
        $this->assertEquals(0, quizaccess_seb\quiz_settings::count_records());
    }

    /**
     * Test backup and restore when manually configured.
     */
    public function test_backup_restore_manual_config() {
        $this->quiz = $this->create_test_quiz($this->course, \quizaccess_seb\settings_provider::USE_SEB_CONFIG_MANUALLY);

        $expected = \quizaccess_seb\quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $expected->set('showsebdownloadlink', 0);
        $expected->set('quitpassword', '123');
        $expected->save();

        $this->assertEquals(1, quizaccess_seb\quiz_settings::count_records());

        $newcm = $this->backup_and_restore_quiz();
        $this->validate_backup_restore($newcm);
    }

    /**
     * Test backup and restore when using template.
     */
    public function test_backup_restore_template_config() {
        $this->quiz = $this->create_test_quiz($this->course, \quizaccess_seb\settings_provider::USE_SEB_CONFIG_MANUALLY);

        $expected = \quizaccess_seb\quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $template = $this->create_template();
        $expected->set('requiresafeexambrowser', \quizaccess_seb\settings_provider::USE_SEB_TEMPLATE);
        $expected->set('templateid', $template->get('id'));
        $expected->save();

        $this->assertEquals(1, quizaccess_seb\quiz_settings::count_records());

        $newcm = $this->backup_and_restore_quiz();
        $this->validate_backup_restore($newcm);
    }

    /**
     * Test backup and restore when using uploaded file.
     */
    public function test_backup_restore_uploaded_config() {
        $this->quiz = $this->create_test_quiz($this->course, \quizaccess_seb\settings_provider::USE_SEB_CONFIG_MANUALLY);

        $expected = \quizaccess_seb\quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $expected->set('requiresafeexambrowser', \quizaccess_seb\settings_provider::USE_SEB_UPLOAD_CONFIG);
        $xml = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $this->create_module_test_file($xml, $this->quiz->cmid);
        $expected->save();

        $this->assertEquals(1, quizaccess_seb\quiz_settings::count_records());

        $newcm = $this->backup_and_restore_quiz();
        $this->validate_backup_restore($newcm);

        $expectedfile = \quizaccess_seb\settings_provider::get_module_context_sebconfig_file($this->quiz->cmid);
        $actualfile = \quizaccess_seb\settings_provider::get_module_context_sebconfig_file($newcm->id);

        $this->assertEquals($expectedfile->get_content(), $actualfile->get_content());
    }

}
