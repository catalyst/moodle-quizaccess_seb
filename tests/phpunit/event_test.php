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
 * PHPUnit tests for all plugin events.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class quizaccess_seb_event_testcase extends advanced_testcase {

    /**
     * Test creating the access_prevented event.
     */
    public function test_access_prevented() {
        $this->resetAfterTest();
        // Set up event with data.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $quizsettings = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        $event = \quizaccess_seb\event\access_prevented::create_strict(
                $quizsettings,
                $course->id,
                context_module::instance($quiz->cmid),
                'Because I said so.',
                'www.example.com/moodle',
                hash('sha256', 'configkey'),
                hash('sha256', 'browserexamkey'));

        // Create an event sink, trigger event and retrieve event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertEquals(1, count($events));
        $event = reset($events);

        // Test that the event data is as expected.
        $this->assertInstanceOf('\quizaccess_seb\event\access_prevented', $event);
        $this->assertEquals('Quiz access was prevented.', $event->get_name());
        $this->assertEquals("The user with id '$user->id' has been prevented from accessing quiz with id '$quiz->id' by the "
            . "Safe Exam Browser access plugin. The reason was 'Because I said so.'.", $event->get_description());
        $this->assertEquals(context_module::instance($quiz->cmid), $event->get_context());
        $this->assertEquals($user->id, $event->userid);
        $this->assertEquals($quiz->id, $event->objectid);
        $this->assertEquals($course->id, $event->courseid);
        $this->assertEquals('Because I said so.', $event->other['reason']);
        $this->assertEquals('www.example.com/moodle', $event->other['url']);
        $this->assertEquals($quizsettings->get('configkey'), $event->other['savedconfigkey']);
        $this->assertEquals(hash('sha256', 'configkey'), $event->other['receivedconfigkey']);
        $this->assertEquals(hash('sha256', 'browserexamkey'), $event->other['receivedbrowserexamkey']);
    }
}