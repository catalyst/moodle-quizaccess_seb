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
 * PHPUnit tests for link_generator.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\link_generator;

defined('MOODLE_INTERNAL') || die();

class quizaccess_seb_link_generator_testcase extends advanced_testcase {

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that a http link is generated correctly.
     */
    public function test_http_link_generated() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = context_module::instance($quiz->cmid);
        $this->create_config_file($quiz->cmid);
        $generator = new link_generator($quiz->cmid);
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/$context->id/quizaccess_seb/config/0/config.seb?forcedownload=1",
            $generator->get_http_link());
    }

    /**
     * Test that a seb link is generated correctly.
     */
    public function test_seb_link_generated() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = context_module::instance($quiz->cmid);
        $this->create_config_file($quiz->cmid);
        $generator = new link_generator($quiz->cmid);
        $this->assertEquals(
            "seb://www.example.com/moodle/pluginfile.php/$context->id/quizaccess_seb/config/0/config.seb?forcedownload=1",
            $generator->get_seb_link());
    }

    /**
     * Test that no HTTP link is returned if file is not found.
     */
    public function test_get_http_link_if_file_does_not_exist() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Create link generator with new content and get file to be downloaded.
        $generator = new link_generator($quiz->cmid);

        $this->assertEmpty($generator->get_http_link());
    }

    /**
     * Test that no SEB link is returned if file is not found.
     */
    public function test_get_seb_link_if_file_does_not_exist() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Create link generator with new content and get file to be downloaded.
        $generator = new link_generator($quiz->cmid);

        $this->assertEmpty($generator->get_seb_link());
    }

    /**
     * Test that link_generator can't not be instantiated with fake course module.
     */
    public function test_course_module_does_not_exist() {
        $this->expectException(dml_exception::class);
        $this->expectExceptionMessage("Invalid course module ID (SELECT id,course FROM {course_modules} WHERE id = ?"
                . "\n[array ("
                . "\n  0 => '123456',"
                . "\n)])");
        $generator = new link_generator(123456);
    }

    /**
     * Create a module SEB config file.
     *
     * @param string $cmid ID of course module to associate with file.
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    private function create_config_file(string $cmid) {
        $context = context_module::instance($cmid);

        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'quizaccess_seb',
            'filearea' => 'config',
            'itemid' => 0, // Only one config file should exist per quiz activity.
            'filepath' => '/',
            'filename' => 'config.seb',
        ], 'File contents.');
    }
}
