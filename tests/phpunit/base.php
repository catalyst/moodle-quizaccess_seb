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
 * This is a Moodle file.
 *
 * This is a longer description of the file.
 *
 * @package    mod_mymodule
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb\tests\phpunit;

defined('MOODLE_INTERNAL') || die();

abstract class quizaccess_seb_testcase extends \advanced_testcase {

    /**
     * Assign a capability to $USER
     * The function creates a student $USER if $USER->id is empty
     *
     * @param string $capability Capability name.
     * @param int $contextid Context ID.
     * @param int $roleid Role ID.
     * @return int The role id - mainly returned for creation, so calling function can reuse it.
     *
     * @throws \coding_exception
     */
    protected function assign_user_capability($capability, $contextid, $roleid = null) {
        global $USER;

        // Create a new student $USER if $USER doesn't exist.
        if (empty($USER->id)) {
            $user = $this->getDataGenerator()->create_user();
            $this->setUser($user);
        }

        if (empty($roleid)) {
            $roleid = \create_role('Dummy role', 'dummyrole', 'dummy role description');
        }

        \assign_capability($capability, CAP_ALLOW, $roleid, $contextid);

        \role_assign($roleid, $USER->id, $contextid);

        \accesslib_clear_all_caches_for_unit_testing();

        return $roleid;
    }
}
