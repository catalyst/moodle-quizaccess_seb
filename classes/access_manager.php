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
 * Manage the access to the quiz.
 *
 * @package    quizaccess_seb
 * @author     Tim Hunt
 * @author     Luca Bösch <luca.boesch@bfh.ch>
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

use context_module;
use quiz;

defined('MOODLE_INTERNAL') || die();

class access_manager {

    /** Header sent by Safe Exam Browser containing the Config Key hash. */
    private const CONFIG_KEY_HEADER = 'HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH';

    /** Header sent by Safe Exam Browser containing the Browser Exam Key hash. */
    private const BROWSER_EXAM_KEY_HEADER = 'HTTP_X_SAFEEXAMBROWSER_REQUESTHASH';

    /** @var quiz $quiz A quiz object containing all information pertaining to current quiz. */
    private $quiz;

    /** @var quiz_settings $quizsettings A quiz settings persistent object containing plugin settings */
    private $quizsettings;

    /** @var context_module $context Context of this quiz activity. */
    private $context;

    /**
     * The access_manager constructor.
     *
     * @param quiz $quiz The details of the quiz.
     *
     * @throws \moodle_exception
     */
    public function __construct(quiz $quiz) {
        $this->quiz = $quiz;
        $this->context = context_module::instance($quiz->get_cmid());
        $this->quizsettings = quiz_settings::get_record(['quizid' => $quiz->get_quizid()]);
        if ($this->quizsettings == false) {
            throw new \moodle_exception('noconfigfound', 'quizaccess_seb', '', $quiz->get_cmid());
        }
    }

    /**
     * Check if the current request contains the required key hashes in header.
     *
     * @param string $pageurl URL to validate keys on.
     * @return bool true if the user is using a browser with a permitted key, false if not.
     *
     * @throws \coding_exception
     */
    public function validate_access_keys(string $pageurl = '') : bool {
        $configkey = $this->quizsettings->get('configkey');
        if (empty($configkey)) {
            return false;
        }

        // If the Config Key header isn't present, prevent access.
        if (!array_key_exists(self::CONFIG_KEY_HEADER, $_SERVER)) {
            return false;
        }

        if (empty($pageurl)) {
            $pageurl = $this->get_this_page_url();
        }
        return $this->check_key($configkey, $pageurl,
                trim($_SERVER[self::CONFIG_KEY_HEADER]));
    }

    /**
     * Check if Safe Exam Browser is required to access quiz.
     *
     * @return bool If required.
     *
     * @throws \coding_exception
     */
    public function seb_required() : bool {
        return $this->quizsettings->get('requiresafeexambrowser') == true;
    }

    /**
     * Check if user has any capability to bypass the Safe Exam Browser requirement.
     *
     * @return bool True if user can bypass check.
     *
     * @throws \coding_exception
     */
    public function can_bypass_seb() : bool {
        return has_capability('quizaccess/seb:bypassseb', $this->context);
    }

    /**
     * Return the full URL that was used to request the current page, which is
     * what we need for verifying the X-SafeExamBrowser-RequestHash header.
     */
    private function get_this_page_url() : string {
        global $FULLME;
        return $FULLME;
    }

    /**
     * Check the hash from the request header against the permitted keys.
     *
     * @param array $keys allowed keys.
     * @param string $url the request URL.
     * @param string $header the value of the X-SafeExamBrowser-RequestHash to check.
     * @return bool true if the hash matches.
     */
    private function check_keys(array $keys, string $url, string $header) : bool {
        foreach ($keys as $key) {
            if ($this->check_key($key, $url, $header)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check the hash from the request header against a single permitted key.
     *
     * @param string $key an allowed key.
     * @param string $url the request URL.
     * @param string $header the value of the X-SafeExamBrowser-RequestHash to check.
     * @return bool true if the hash matches.
     */
    private function check_key($key, $url, $header) : bool {
        return hash('sha256', $url . $key) === $header;
    }
}
