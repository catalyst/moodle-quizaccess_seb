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
 * Upgrade script for plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot  . '/mod/quiz/accessrule/seb/lib.php');

/**
 * Function to upgrade quizaccess_seb plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Result.
 */
function xmldb_quizaccess_seb_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019122000) {

        // Define key quizid (foreign-unique) to be added to quizaccess_seb_quizsettings.
        $table = new xmldb_table('quizaccess_seb_quizsettings');

        // Launch drop unique key quizid.
        $key = new xmldb_key('quizid', XMLDB_KEY_UNIQUE, ['quizid']);
        $dbman->drop_key($table, $key);

        // Launch add foreign key quizid.
        $key = new xmldb_key('quizid', XMLDB_KEY_FOREIGN_UNIQUE, ['quizid'], 'quiz', ['id']);
        $dbman->add_key($table, $key);

        // Launch drop unique key templateid.
        $key = new xmldb_key('templateid', XMLDB_KEY_UNIQUE, ['templateid']);
        $dbman->drop_key($table, $key);

        // Launch add foreign key templateid.
        $key = new xmldb_key('templateid', XMLDB_KEY_FOREIGN, ['templateid'], 'quizacces_seb_template', ['id']);
        $dbman->add_key($table, $key);

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2019122000, 'quizaccess', 'seb');
    }

    if ($oldversion < 2020010700) {

        // Define field allowedbrowserexamkeys to be added to quizaccess_seb_quizsettings.
        $table = new xmldb_table('quizaccess_seb_quizsettings');
        $field = new xmldb_field('allowedbrowserexamkeys', XMLDB_TYPE_TEXT, null, null, null, null, null, 'regexblocked');

        // Conditionally launch add field allowedbrowserexamkeys.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020010700, 'quizaccess', 'seb');
    }

    return true;
}
