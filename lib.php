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
 * @param int $cachelifetime Time in seconds til cached file expires.
 * @return bool Whether the file was created and served.
 */
function serve_quiz_config_xml(string $cmid, $cachelifetime = 300) {
    // Check that the course module exists, user is logged into course and can access course module.
    try {
        // Try and get the course module.
        $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);

        // Make sure the user is logged in and has access to the module.
        require_login($cm->course, false, $cm);
    } catch (moodle_exception $e) {
        debugging($e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }

    // Retrieve the config.
    // TODO: Issue #27 - Once the config generator is set up to store the XML, update this to attempt to retrieve it.
    $config = '';

    // We can now send the file back to the browser - in this case with a cache lifetime of 5 minutes.
    header("Cache-Control: private, max-age=$cachelifetime, no-transform");
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $cachelifetime) .' GMT');
    header('Pragma: ');
    header('Content-Disposition: attachment; filename=config.seb');
    header('Content-Type: text/xml');
    echo($config);
    return true;
}
