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

use quizaccess_seb\access_manager;
use quizaccess_seb\quiz_settings;
use quizaccess_seb\settings_provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

class quizaccess_seb extends quiz_access_rule_base {

    /**
     * Default URL to download SEB browser.
     */
    const DEFAULT_SEB_DOWNLOAD_URL = 'https://safeexambrowser.org/download_en.html';

    /** @var access_manager $accessmanager Instance to manage the access to the quiz for this plugin. */
    private $accessmanager;

    /**
     * Create an instance of this rule for a particular quiz.
     *
     * @param quiz $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     */
    public function __construct (quiz $quizobj, int $timenow) {
        parent::__construct($quizobj, $timenow);
        $this->accessmanager = new access_manager($quizobj);
    }

    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     *
     * @param quiz $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/quiz:ignoretimelimits capability.
     * @return quiz_access_rule_base|null the rule, if applicable, else null.
     */
    public static function make (quiz $quizobj, $timenow, $canignoretimelimits) {
        $accessmanager = new access_manager($quizobj);
        // If Safe Exam Browser is not required, this access rule is not applicable.
        if (!$accessmanager->seb_required()) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Add any fields that this rule requires to the quiz settings form. This
     * method is called from {@link mod_quiz_mod_form::definition()}, while the
     * security section is being built.
     *
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     *
     * @throws coding_exception
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $defaults = settings_provider::get_quiz_defaults();
        $hideifs = settings_provider::get_quiz_hideifs();

        // Insert all the form elements before the 'security' section as a group.
        foreach (settings_provider::get_quiz_element_types() as $name => $type) {
            // Create element.
            $element = $mform->createElement($type, $name, get_string($name, 'quizaccess_seb'));

            // Insert element.
            $mform->insertElementBefore($element, 'security');
            unset($element); // We need to make sure each &element only references the current element in loop.

            $mform->addHelpButton($name, $name, 'quizaccess_seb');

            // Set defaults.
            if (isset($defaults[$name])) {
                $mform->setDefault($name, $defaults[$name]);
            }

            // Set hideifs.
            if (isset($hideifs[$name])) {
                foreach ($hideifs[$name] as $dependantname => $dependantvalue) {
                    $mform->hideIf($name, $dependantname, 'eq', $dependantvalue);
                }
            }
        }
    }

    /**
     * Validate the data from any form fields added using {@link add_settings_form_fields()}.
     *
     * @param array $errors the errors found so far.
     * @param array $data the submitted form data.
     * @param array $files information about any uploaded files.
     * @param mod_quiz_mod_form $quizform the quiz form object.
     * @return array $errors the updated $errors array.
     *
     * @throws coding_exception
     */
    public static function validate_settings_form_fields(array $errors,
                                                         array $data, $files, mod_quiz_mod_form $quizform) : array {
        $settings = self::filter_plugin_settings((object) $data);

        // Validate basic settings using persistent class.
        $quizsettings = (new quiz_settings())->from_record($settings);
        // Set non-form fields.
        $quizsettings->set('quizid', $data['instance']);
        $quizsettings->validate();

        // Add any errors to list.
        foreach ($quizsettings->get_errors() as $name => $error) {
            $name = self::add_prefix($name); // Re-add prefix to match form element.
            $errors[$name] = $error->out();
        }

        return $errors;
    }

    /**
     * Save any submitted settings when the quiz settings form is submitted. This
     * is called from {@link quiz_after_add_or_update()} in lib.php.
     *
     * @param object $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     *
     * @throws coding_exception
     */
    public static function save_settings($quiz) {

        $settings = self::filter_plugin_settings($quiz);

        // Associate settings with quiz.
        $settings->quizid = $quiz->id;

        // TODO: Process sebconfigtemplate into templateid.
        $settings->templateid = 0;

        // Get existing settings or create new settings if none exist.
        $quizsettings = quiz_settings::get_record(['quizid' => $quiz->id]);
        if (!$quizsettings) {
            $quizsettings = new quiz_settings(0, $settings);
        } else {
            $settings->id = $quizsettings->get('id');
            $quizsettings->from_record($settings);
        }

        // Validate and save settings. Settings should already be validated by validate_settings_form_fields but
        // the validation method also adds in default fields which is useful here.
        $quizsettings->validate();
        $quizsettings->save();
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted. This is called
     * from {@link quiz_delete_instance()} in lib.php.
     *
     * @param object $quiz the data from the database, including $quiz->id
     *      which is the id of the quiz being deleted.
     *
     * @throws coding_exception
     */
    public static function delete_settings($quiz) {
        $quizsettings = quiz_settings::get_record(['quizid' => $quiz->id]);
        // Check that there are existing settings.
        if ($quizsettings !== false) {
            $quizsettings->delete();
        }
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query. The easiest way to understand what you need to do
     * here is probalby to read the code of {@link quiz_access_manager::load_settings()}.
     *
     * If you have some settings that cannot be loaded in this way, then you can
     * use the {@link get_extra_settings()} method instead, but that has
     * performance implications.
     *
     * @param int $quizid the id of the quiz we are loading settings for. This
     *     can also be accessed as quiz.id in the SQL. (quiz is a table alisas for {quiz}.)
     * @return array with three elements:
     *     1. fields: any fields to add to the select list. These should be alised
     *        if neccessary so that the field name starts the name of the plugin.
     *     2. joins: any joins (should probably be LEFT JOINS) with other tables that
     *        are needed.
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        used named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
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
     * Whether the user should be blocked from starting a new attempt or continuing
     * an attempt now.
     *
     * @return string false if access should be allowed, a message explaining the
     *      reason if access should be prevented.
     *
     * @throws coding_exception
     */
    public function prevent_access() {
        $errormessage = '';

        // If Safe Exam Browser is not required or user can bypass check, access to quiz should not be prevented.
        if (!$this->accessmanager->seb_required() || $this->accessmanager->can_bypass_seb()) {
            return false;
        }

        // Check if the quiz can be validated with the quiz Config Key or Browser Exam Keys.
        if ($this->accessmanager->validate_access_keys()) {
            return false;
        } else {
            // Add error message.
            $errormessage .= get_string('invalidkeys', 'quizaccess_seb');
            // TODO: Issue #8 - Trigger event if access is prevented.
        }

        // Display action buttons to assist user in gaining access to quiz.
        $errormessage .= $this->get_action_buttons();
        return $errormessage;
    }

    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     *
     * @return mixed a message, or array of messages, explaining the restriction
     *         (may be '' if no message is appropriate).
     */
    public function description() {
        return [
            get_string('sebrequired', 'quizaccess_seb'),
        ];
    }

    /**
     * Sets up the attempt (review or summary) page with any special extra
     * properties required by this rule.
     *
     * @param moodle_page $page the page object to initialise.
     */
    public function setup_attempt_page($page) {
        $page->set_title($this->quizobj->get_course()->shortname . ': ' . $page->title);
        $page->set_cacheable(false);
        $page->set_popup_notification_allowed(false); // Prevent message notifications.
        $page->set_heading($page->title);
        $page->set_pagelayout('secure');
    }

    /**
     * Get buttons to prompt user to download SEB or config file.
     *
     * @return string Button html as a block.
     *
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_action_buttons() {
        global $OUTPUT;
        $buttons = '';

        // Get data for buttons.
        $seblink = \quizaccess_seb\link_generator::get_link($this->quiz->cmid, true, is_https());
        $httplink = \quizaccess_seb\link_generator::get_link($this->quiz->cmid, false, is_https());

        $buttons .= html_writer::start_div();
        $buttons .= $OUTPUT->single_button($this->get_seb_download_url(), get_string('sebdownloadbutton', 'quizaccess_seb'));
        $buttons .= $OUTPUT->single_button($seblink, get_string('seblinkbutton', 'quizaccess_seb'));
        $buttons .= $OUTPUT->single_button($httplink, get_string('httplinkbutton', 'quizaccess_seb'));
        $buttons .= html_writer::end_div();

        return $buttons;
    }

    /**
     * Returns SEB download URL.
     *
     * @return string
     */
    private function get_seb_download_url() {
        // TODO: Issue #9 - Admin setting or download SEB url.
        return self::DEFAULT_SEB_DOWNLOAD_URL;
    }

    /**
     * Strip the seb_ prefix from each setting key.
     *
     * @param \stdClass $settings Object containing settings.
     * @return \stdClass The modified settings object.
     */
    private static function strip_all_prefixes(\stdClass $settings) : \stdClass {
        $newsettings = new \stdClass();
        foreach ($settings as $name => $setting) {
            $newname = preg_replace("/^seb_/", "", $name);
            $newsettings->$newname = $setting; // Add new key.
        }
        return $newsettings;
    }

    /**
     * Add prefix to string.
     *
     * @param string $name String to add prefix to.
     * @return string String with prefix.
     */
    private static function add_prefix(string $name) : string {
        if (strpos($name, 'seb_') !== 0) {
            $name = 'seb_' . $name;
        }
        return $name;
    }

    /**
     * Filter a standard class by prefix.
     *
     * @param stdClass $settings Quiz settings object.
     * @return stdClass Filtered object.
     */
    private static function filter_by_prefix(\stdClass $settings) : \stdClass {
        $newsettings = new \stdClass();
        foreach ($settings as $name => $setting) {
            // Only add it, if not there.
            if (strpos($name, "seb_") === 0) {
                $newsettings->$name = $setting; // Add new key.
            }
        }
        return $newsettings;
    }

    /**
     * Filter quiz settings for this plugin only.
     *
     * @param stdClass $settings Quiz settings.
     * @return stdClass Filtered settings.
     */
    private static function filter_plugin_settings(stdClass $settings) {
        $settings = self::filter_by_prefix($settings);
        return self::strip_all_prefixes($settings);
    }
}
