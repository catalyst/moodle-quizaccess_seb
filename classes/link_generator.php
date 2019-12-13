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
 * Generate the links to open/download the Safe Exam Browser with correct settings.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

use moodle_url;
use stored_file;

defined('MOODLE_INTERNAL') || die();

class link_generator {

    /** @var bool|stored_file $file File to serve to users via links. False if not found. */
    private $file = false;

    /** @var \context_module $context The context of the file that will be used by link. */
    private $context;

    /**
     * link_generator constructor.
     *
     * @param string $cmid Context module ID of a quiz activity.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(string $cmid) {
        // Check if course module exists.
        $this->context = \context_module::instance($cmid, MUST_EXIST);
    }

    /**
     * Get a link to force the download of the file over https.
     *
     * @return string A URL.
     */
    public function get_http_link() : string {
        if (!$this->file_exists()) {
            return ''; // Return empty string if file not found.
        }
        $url = moodle_url::make_pluginfile_url($this->file->get_contextid(), $this->file->get_component(),
                $this->file->get_filearea(), $this->file->get_itemid(), $this->file->get_filepath(),
                $this->file->get_filename(), true);
        return $url->out();
    }

    /**
     * Get a link that if SEB is installed and has a compatible version, will run with the file as configuration settings.
     *
     * @return string A URL.
     *
     * @throws \coding_exception
     */
    public function get_seb_link() : string {
        if (!$this->file_exists()) {
            return ''; // Return empty string if file not found.
        }
        $url = moodle_url::make_pluginfile_url($this->file->get_contextid(), $this->file->get_component(),
            $this->file->get_filearea(), $this->file->get_itemid(), $this->file->get_filepath(),
            $this->file->get_filename(), true);
        $url->set_scheme('seb');
        return $url->out();
    }

    /**
     * Check if file is set, and try to retrieve if it isn't.
     *
     * @return bool Whether the config file for the course module is found.
     */
    private function file_exists() {
        if (!$this->file) { // Check if instance has file.
            // Try and retrieve config file.
            $this->get_file();
            if (!$this->file) { // Re-check if instance has file after retrieval.
                return false; // File can't be found.
            }
        }
        return true;
    }

    /**
     * Try and find the config file for the course module.
     */
    private function get_file() {
        // Get config file for course.
        $fs = get_file_storage();
        $this->file = $fs->get_file($this->context->id, 'quizaccess_seb', 'config', 0, '/', 'config.seb');
    }
}
