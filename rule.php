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
 * Implementaton of the quizaccess_seb plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

class quizaccess_seb extends quiz_access_rule_base {

    /**
     * Constructor for quizaccess_seb.
     *
     * @param quiz $quizobj Information about the quiz in question.
     * @param int $timenow The time that should be considered as 'now'.
     */
    public function __construct(quiz $quizobj, int $timenow) {
        parent::__construct($quizobj, $timenow);
    }

    /**
     * Create the rule.
     *
     * @param quiz $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     * @return quiz_access_rule_base|quizaccess_seb|null
     */
    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        return new self($quizobj, $timenow);
    }

    /**
     * Add extra setting elements to the quiz settings.
     *
     * @param mod_quiz_mod_form $quizform
     * @param MoodleQuickForm $mform
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
    }

    /**
     * Validate the form elements when form is submitted.
     *
     * @param array $errors
     * @param array $data
     * @param array $files
     * @param mod_quiz_mod_form $quizform
     * @return array
     */
    public static function validate_settings_form_fields(array $errors,
                                                         array $data, $files, mod_quiz_mod_form $quizform) : array {
        return $errors;
    }

    /**
     * Hook to handle saving the quiz settings for plugin.
     *
     * @param object $quiz
     */
    public static function save_settings($quiz) {
    }

    /**
     * Delete quiz settings from DB and related files from storage.
     *
     * @param object $quiz
     */
    public static function delete_settings($quiz) {
    }

    /**
     * Return the SQL needed to load all the settings from all the access plugins in one DB query.
     *
     * Array to return contains three elements - [fields, joins, params].
     *
     * @param int $quizid
     * @return array
     */
    public static function get_settings_sql($quizid) : array {
        return [
                'seb.requiresafeexambrowser AS seb_requiresafeexambrowser, '
                . 'seb.sebconfigfile AS seb_sebconfigfile, '
                . 'seb.showsebtaskbar AS seb_showsebtaskbar, '
                . 'seb.showwificontrol AS seb_showwificontrol, '
                . 'seb.showreloadbutton AS seb_showreloadbutton, '
                . 'seb.showtime AS seb_showtime, '
                . 'seb.showkeyboardlayout AS seb_showkeyboardlayout, '
                . 'seb.allowuserquitseb AS seb_allowuserquitseb, '
                . 'seb.quitpassword AS seb_quitpassword, '
                . 'seb.linkquitseb AS seb_linkquitseb, '
                . 'seb.userconfirmquit AS seb_userconfirmquit, '
                . 'seb.enableaudiocontrol AS seb_enableaudiocontrol, '
                . 'seb.muteonstartup AS seb_muteonstartup, '
                . 'seb.allowspellchecking AS seb_allowspellchecking, '
                . 'seb.allowreloadinexam AS seb_allowreloadinexam, '
                . 'seb.activateurlfiltering AS seb_activateurlfiltering, '
                . 'seb.filterembeddedcontent AS seb_filterembeddedcontent, '
                . 'seb.expressionsallowed AS seb_expressionsallowed, '
                . 'seb.regexallowed AS seb_regexallowed, '
                . 'seb.expressionsblocked AS seb_expressionsblocked, '
                . 'seb.regexblocked AS seb_regexblocked, '
                . 'seb.suppresssebdownloadlink AS seb_suppresssebdownloadlink, '
                . 'sebtemplate.name AS seb_templatename '
                , 'LEFT JOIN {quizaccess_seb_quizsettings} seb ON seb.quizid = quiz.id '
                . 'LEFT JOIN {quizaccess_seb_template} sebtemplate ON seb.templateid = sebtemplate.id '
                , []
        ];
    }

    /**
     * Logic to decide whether or not to prevent access.
     *
     * @return bool|string
     */
    public function prevent_access() {
        return true;
    }

    /**
     * Get a description of why the access rule is preventing access.
     *
     * @return mixed|string
     */
    public function description() {
        return '';
    }

    /**
     * Set up the quiz attempt page.
     *
     * @param moodle_page $page
     */
    public function setup_attempt_page($page) {
        $page->set_title($this->quizobj->get_course()->shortname . ': ' . $page->title);
        $page->set_cacheable(false);
        $page->set_popup_notification_allowed(false); // Prevent message notifications.
        $page->set_heading($page->title);
        $page->set_pagelayout('secure');
    }
}
