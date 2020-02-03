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
 * Main library of plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve a seb config file for a particular quiz.
 *
 * @param string $cmid The course module ID for a quiz with config.
 * @return string SEB config string.
 */
function quizaccess_seb_get_config(string $cmid) : string {
    // Try and get the course module.
    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);

    // Make sure the user is logged in and has access to the module.
    require_login($cm->course, false, $cm);

    // Retrieve the config for quiz.
    $settings = \quizaccess_seb\quiz_settings::get_record(['quizid' => $cm->instance]);
    // If no settings found, config is false, otherwise get config.
    $config = $settings !== false ? $settings->get('config') : false;
    if (empty($config)) {
        throw new moodle_exception('noconfigfound', 'quizaccess_seb', '', $cm->id);
    }
    return $config;
}

/**
 * Serve a file to browser for download.
 *
 * @param string $contents Contents of file.
 */
function quizaccess_seb_send_file(string $contents) {
    // We can now send the file back to the browser.
    header("Cache-Control: private, max-age=1, no-transform");
    header('Expires: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Pragma: no-cache');
    header('Content-Disposition: attachment; filename=config.seb');
    header('Content-Type: text/xml');

    echo($contents);
}

/**
 * Create missing SEB settings for quizzes that don't have them.
 */
function quizaccess_seb_create_missing_settings() {
    global $DB;

    $quizzes = $DB->get_recordset('quiz');

    foreach ($quizzes as $quiz) {
        $quizsettings = \quizaccess_seb\quiz_settings::get_record(['quizid' => $quiz->id]);
        if ($quizsettings == false) {
            $quizsettings = new \quizaccess_seb\quiz_settings(0, (object)['quizid' => $quiz->id]);
            $quizsettings->create();
        }
    }

    $quizzes->close();
}
