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
 * Restore instructions for the seb (Safe Exam Browser) quiz access subplugin.
 *
 * @package    quizaccess_seb
 * @category   backup
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\quiz_settings;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');

class restore_quizaccess_seb_subplugin extends restore_mod_quiz_access_subplugin {

    /**
     * Provides path structure required to restore data for seb quiz access plugin.
     *
     * @return array
     */
    protected function define_quiz_subplugin_structure() {
        $paths = [];

        // TODO: Templates.

        // Quiz settings.
        $path = $this->get_pathfor('/quizaccess_seb_quizsettings'); // Subplugin root path.
        $paths[] = new restore_path_element('quizaccess_seb_quizsettings', $path);

        return $paths;
    }

    /**
     * Process the restored data for the quizaccess_seb_quizsettings table.
     *
     * @param stdClass $data Data for quizaccess_seb_quizsettings retrieved from backup xml.
     *
     * @throws DOMException
     * @throws \CFPropertyList\IOException
     * @throws \CFPropertyList\PListException
     * @throws coding_exception
     */
    public function process_quizaccess_seb_quizsettings($data) {
        // Process quizsettings.
        $data = (object) $data;
        $data->quizid = $this->get_new_parentid('quiz'); // Update quizid with new reference.
        // TODO: Map template ID to new template reference once implemented.
        $quizsettings = new quiz_settings(0, $data);
        $quizsettings->save();
    }
}

