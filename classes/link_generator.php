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

defined('MOODLE_INTERNAL') || die();

class link_generator {

    /** @var string $cmid Course module ID of a quiz. */
    private $cmid;

    /**
     * link_generator constructor.
     *
     * @param string $cmid Context module ID of a quiz activity.
     *
     * @throws \coding_exception
     */
    public function __construct(string $cmid) {
        // Check if course module exists.
        get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
        $this->cmid = $cmid;
    }

    /**
     * Get a link to force the download of the file over https.
     *
     * @param bool $secure Whether to use HTTPS or HTTP protocol.
     * @return string A URL.
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function get_http_link(bool $secure = true) : string {
        $url = new moodle_url('/mod/quiz/accessrule/seb/config.php?cmid=' . $this->cmid);
        $secure ? $url->set_scheme('https') : $url->set_scheme('http');
        return $url->out();
    }

    /**
     * Get a link that if SEB is installed and has a compatible version, will run with the file as configuration settings.
     *
     * @param bool $secure Whether to use SEBS or SEB protocol.
     * @return string A URL.
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function get_seb_link(bool $secure = true) : string {
        $url = new moodle_url('/mod/quiz/accessrule/seb/config.php?cmid=' . $this->cmid);
        $secure ? $url->set_scheme('sebs') : $url->set_scheme('seb');
        return $url->out();
    }
}
