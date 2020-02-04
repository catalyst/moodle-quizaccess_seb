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
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
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
     */
    public function __construct(quiz $quiz) {
        $this->quiz = $quiz;
        $this->context = context_module::instance($quiz->get_cmid());
        $this->quizsettings = quiz_settings::get_record(['quizid' => $quiz->get_quizid()]);
    }

    /**
     * Check if the current request contains the required key hashes in header.
     *
     * @param string $pageurl URL to validate keys on.
     * @return bool true if the user is using a browser with a permitted key, false if not.
     */
    public function validate_access_keys(string $pageurl = '') : bool {
        return $this->validate_browser_exam_keys($pageurl) && $this->validate_config_key($pageurl);
    }

    /**
     * Check if the browser exam key hash in header matches one of the listed browser exam keys from quiz settings.
     *
     * @param string $pageurl URL of page that is attempting to access restricted quiz.
     * @return bool True if header key matches one of the saved keys.
     */
    private function validate_browser_exam_keys(string $pageurl = '') : bool {
        // If browser exam keys are entered in settings, check they match the header.
        $browserexamkeys = $this->quizsettings->get('allowedbrowserexamkeys');
        if (empty($browserexamkeys)) {
            return true; // If no browser exam keys, no check required.
        }

        // If the Browser Exam Key header isn't present, prevent access.
        if (!array_key_exists(self::BROWSER_EXAM_KEY_HEADER, $_SERVER)) {
            return false;
        }

        if (empty($pageurl)) {
            $pageurl = $this->get_this_page_url();
        }

        return $this->check_browser_exam_keys($browserexamkeys, $pageurl, trim($_SERVER[self::BROWSER_EXAM_KEY_HEADER]));
    }

    /**
     * Check if the config key hash in header matches quiz settings.
     *
     * @param string $pageurl URL of page that is attempting to access restricted quiz.
     * @return bool True if header key matches saved key.
     */
    private function validate_config_key(string $pageurl = '') : bool {
        // If using client config, or with no requirement, then no check required.
        $requiredtype = $this->quizsettings->get('requiresafeexambrowser');
        if ($requiredtype == settings_provider::USE_SEB_NO
                || $requiredtype == settings_provider::USE_SEB_CLIENT_CONFIG) {
            return true;
        }

        $configkey = $this->quizsettings->get('configkey');
        if (empty($configkey)) {
            return false; // No config key has been saved.
        }

        // If the Config Key header isn't present, prevent access.
        if (!array_key_exists(self::CONFIG_KEY_HEADER, $_SERVER)) {
            return false;
        }

        if (empty($pageurl)) {
            $pageurl = $this->get_this_page_url();
        }

        return $this->check_key($configkey, $pageurl, trim($_SERVER[self::CONFIG_KEY_HEADER]));
    }

    /**
     * Check if Safe Exam Browser is required to access quiz.
     * If quizsettings do not exist, then there is no requirement for using SEB.
     *
     * @return bool If required.
     */
    public function seb_required() : bool {
        if (!$this->quizsettings) {
            return false;
        } else {
            return $this->quizsettings->get('requiresafeexambrowser') != settings_provider::USE_SEB_NO;
        }
    }

    /**
     * This is the basic check for the Safe Exam Browser previously used in the quizaccess_safebrowser plugin that
     * managed basic Moodle interactions with SEB.
     *
     * @return bool
     */
    public function validate_basic_header() : bool {
        if ($this->quizsettings->get('requiresafeexambrowser') == settings_provider::USE_SEB_CLIENT_CONFIG) {
            return strpos($_SERVER['HTTP_USER_AGENT'], 'SEB') !== false;
        }
        return true;
    }

    /**
     * Check if user has any capability to bypass the Safe Exam Browser requirement.
     *
     * @return bool True if user can bypass check.
     */
    public function can_bypass_seb() : bool {
        return has_capability('quizaccess/seb:bypassseb', $this->context);
    }

    /**
     * Return the full URL that was used to request the current page, which is
     * what we need for verifying the X-SafeExamBrowser-RequestHash header.
     */
    private function get_this_page_url() : string {
        global $CFG, $FULLME;
        // If $FULLME not set fall back to wwwroot.
        if ($FULLME == null) {
            return $CFG->wwwroot;
        }
        return $FULLME;
    }

    /**
     * Check the hash from the request header against the permitted browser exam keys.
     *
     * @param array $keys Allowed browser exam keys.
     * @param string $url The request URL.
     * @param string $header The value of the X-SafeExamBrowser-RequestHash to check.
     * @return bool True if the hash matches.
     */
    private function check_browser_exam_keys(array $keys, string $url, string $header) : bool {
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
     * @param string $header the value of the X-SafeExamBrowser-RequestHash or X-SafeExamBrowser-ConfigKeyHash to check.
     * @return bool true if the hash matches.
     */
    private function check_key($key, $url, $header) : bool {
        return hash('sha256', $url . $key) === $header;
    }
}
