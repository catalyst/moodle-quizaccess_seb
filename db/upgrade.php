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

    if ($oldversion < 2020020600) {

        // Define field cmid to be added to quizaccess_seb_quizsettings.
        $table = new xmldb_table('quizaccess_seb_quizsettings');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'quizid');

        // Conditionally launch add field cmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020020600, 'quizaccess', 'seb');
    }

    if ($oldversion < 2020020700) {

        $table = new xmldb_table('quizaccess_seb_template');

        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'name');
        // Conditionally launch add field description.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'content');
        // Conditionally launch add field enabled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'enabled');
        // Conditionally launch add field sortorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'sortorder');
        // Conditionally launch add field usermodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'usermodified');
        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020020700, 'quizaccess', 'seb');
    }

    if ($oldversion < 2020030402) {

        // Define key cmid (foreign-unique) to be added to quizaccess_seb_quizsettings.
        $table = new xmldb_table('quizaccess_seb_quizsettings');
        $key = new xmldb_key('cmid', XMLDB_KEY_FOREIGN_UNIQUE, ['cmid'], 'course_modules', ['id']);
        // Launch add key cmid.
        $dbman->add_key($table, $key);

        $field = new xmldb_field('sebconfigfile', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'requiresafeexambrowser');
        // Launch change of type for field sebconfigfile.
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('showsebtaskbar', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'sebconfigfile');
        // Launch change of nullability for field showsebtaskbar.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('showwificontrol', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'showsebtaskbar');
        // Launch change of nullability for field showwificontrol.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('showreloadbutton', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'showwificontrol');
        // Launch change of nullability for field showreloadbutton.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('showtime', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'showreloadbutton');
        // Launch change of nullability for field showtime.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('showkeyboardlayout', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'showtime');
        // Launch change of nullability for field showkeyboardlayout.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('allowuserquitseb', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'showkeyboardlayout');
        // Launch change of nullability for field allowuserquitseb.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('linkquitseb', XMLDB_TYPE_TEXT, null, null, null, null, null, 'quitpassword');
        // Launch change of nullability for field linkquitseb.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('userconfirmquit', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'linkquitseb');
        // Launch change of nullability for field userconfirmquit.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('enableaudiocontrol', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'userconfirmquit');
        // Launch change of nullability for field enableaudiocontrol.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('muteonstartup', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'enableaudiocontrol');
        // Launch change of nullability for field muteonstartup.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('allowspellchecking', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'muteonstartup');
        // Launch change of nullability for field allowspellchecking.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('allowreloadinexam', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'allowspellchecking');
        // Launch change of nullability for field allowreloadinexam.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('activateurlfiltering', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'allowreloadinexam');
        // Launch change of nullability for field activateurlfiltering.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('filterembeddedcontent', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'activateurlfiltering');
        // Launch change of nullability for field filterembeddedcontent.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('suppresssebdownloadlink',
            XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'allowedbrowserexamkeys');
        // Launch change of nullability for field suppresssebdownloadlink.
        $dbman->change_field_notnull($table, $field);

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020030402, 'quizaccess', 'seb');
    }

    if ($oldversion < 2020030403) {

        // Define field sebconfigfile to be dropped from quizaccess_seb_quizsettings.
        $table = new xmldb_table('quizaccess_seb_quizsettings');
        $field = new xmldb_field('sebconfigfile');

        // Conditionally launch drop field sebconfigfile.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020030403, 'quizaccess', 'seb');
    }

    if ($oldversion < 2020040101) {
        // Drop config and configkey fields.
        $table = new xmldb_table('quizaccess_seb_quizsettings');
        $config = new xmldb_field('config');
        $configkey = new xmldb_field('configkey');

        if ($dbman->field_exists($table, $config)) {
            $dbman->drop_field($table, $config);
        }

        if ($dbman->field_exists($table, $configkey)) {
            $dbman->drop_field($table, $configkey);
        }

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020040101, 'quizaccess', 'seb');
    }

    if ($oldversion < 2020040703) {

        // Rename the field.
        $table = new xmldb_table('quizaccess_seb_quizsettings');
        $field = new xmldb_field('suppresssebdownloadlink');

        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'allowedbrowserexamkeys');
            $dbman->rename_field($table, $field, 'showsebdownloadlink');
        }

        // Reverse the field values as the logic has been changed from suppress to show.
        $records = $DB->get_records('quizaccess_seb_quizsettings');
        foreach ($records as $record) {
            if (!is_null($record->showsebdownloadlink)) {
                $record->showsebdownloadlink = !$record->showsebdownloadlink;
                $DB->update_record('quizaccess_seb_quizsettings', $record);
            }
        }

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020040703, 'quizaccess', 'seb');
    }

    if ($oldversion < 2020042600) {

        $table = new xmldb_table('quizaccess_seb_template');

        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $dbman->change_field_type($table, $field);

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2020042600, 'quizaccess', 'seb');
    }

    return true;
}
