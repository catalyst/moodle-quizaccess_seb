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
 * Class for providing quiz settings, to make setting up quiz form manageable.
 *
 * To make sure there are no inconsistencies between data sets, run tests in tests/phpunit/settings_provider_test.php.
 *
 * @package    quizaccess_seb
 * @author     Luca Bösch <luca.boesch@bfh.ch>
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

use context_module;
use context_user;
use lang_string;
use stdClass;
use stored_file;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../vendor/autoload.php');

class settings_provider {

    /**
     * No SEB should be used.
     */
    const USE_SEB_NO = 0;

    /**
     * Use SEB and configure it manually.
     */
    const USE_SEB_CONFIG_MANUALLY = 1;

    /**
     * Use SEB config from pre configured template.
     */
    const USE_SEB_TEMPLATE = 2;

    /**
     * Use SEB config from uploaded config file.
     */
    const USE_SEB_UPLOAD_CONFIG = 3;

    /**
     * Use client config. Not SEB config is required.
     */
    const USE_SEB_CLIENT_CONFIG = 4;

    /**
     * Insert form element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param \HTML_QuickForm_element $element Element to insert.
     * @param string $before Insert element before.
     */
    protected static function insert_element(\mod_quiz_mod_form $quizform,
                                             \MoodleQuickForm $mform, \HTML_QuickForm_element $element, $before = 'security') {
        $mform->insertElementBefore($element, $before);
    }

    /**
     * Remove element from the form.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     */
    protected static function remove_element(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string  $elementname) {
        if ($mform->elementExists($elementname)) {
            $mform->removeElement($elementname);
            $mform->setDefault($elementname, null);
        }
    }

    /**
     * Add help button to the element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     */
    protected static function add_help_button(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string $elementname) {
        if ($mform->elementExists($elementname)) {
            $mform->addHelpButton($elementname, $elementname, 'quizaccess_seb');
        }
    }

    /**
     * Set default value for the element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     * @param mixed $value Default value.
     */
    public static function set_default(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string  $elementname, $value) {
        $mform->setDefault($elementname, $value);
    }

    /**
     * Set element type.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     * @param string $type Type of the form element.
     */
    public static function set_type(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string $elementname, string $type) {
        $mform->setType($elementname, $type);
    }

    /**
     * Add SEB header element to  the form.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_seb_header_element(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        global  $OUTPUT;

        $element = $mform->createElement('header', 'seb', get_string('seb', 'quizaccess_seb'));
        self::insert_element($quizform, $mform, $element);

        // Display notification about locked settings.
        if (self::is_seb_settings_locked($quizform->get_instance())) {
            $notify = new \core\output\notification(
                get_string('settingsfrozen', 'quizaccess_seb'),
                \core\output\notification::NOTIFY_WARNING
            );

            $notifyelement = $mform->createElement('html', $OUTPUT->render($notify));
            self::insert_element($quizform, $mform, $notifyelement);
        }

        if (self::is_conflicting_permissions($quizform->get_context())) {
            $notify = new \core\output\notification(
                get_string('conflictingsettings', 'quizaccess_seb'),
                \core\output\notification::NOTIFY_WARNING
            );

            $notifyelement = $mform->createElement('html', $OUTPUT->render($notify));
            self::insert_element($quizform, $mform, $notifyelement);
        }
    }

    /**
     * Add SEB usage element with all available options.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_seb_usage_options(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        $element = $mform->createElement(
            'select',
            'seb_requiresafeexambrowser',
            get_string('seb_requiresafeexambrowser', 'quizaccess_seb'),
            self::get_requiresafeexambrowser_options($quizform->get_context())
        );

        self::insert_element($quizform, $mform, $element);
        self::set_type($quizform, $mform, 'seb_requiresafeexambrowser', PARAM_INT);
        self::set_default($quizform, $mform, 'seb_requiresafeexambrowser', self::USE_SEB_NO);
        self::add_help_button($quizform, $mform, 'seb_requiresafeexambrowser');

        if (self::is_conflicting_permissions($quizform->get_context())) {
            $mform->freeze(['seb_requiresafeexambrowser']);
        }
    }

    /**
     * Add Templates element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_seb_templates(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        if (self::can_use_seb_template($quizform->get_context()) || self::is_conflicting_permissions($quizform->get_context())) {
            $element = $mform->createElement(
                'select',
                'seb_templateid',
                get_string('seb_templateid', 'quizaccess_seb'),
                self::get_template_options()
            );
        } else {
            $element = $mform->createElement('hidden', 'seb_templateid');
        }

        self::insert_element($quizform, $mform, $element);
        self::set_type($quizform, $mform, 'seb_templateid', PARAM_INT);
        self::set_default($quizform, $mform, 'seb_templateid', 0);
        self::add_help_button($quizform, $mform, 'seb_templateid');

        // In case if the user can't use templates, but the quiz is configured to use them,
        // we'd like to display template, but freeze it.
        if (self::is_conflicting_permissions($quizform->get_context())) {
            $mform->freeze(['seb_templateid']);
        }
    }

    /**
     * Add upload config file element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_seb_config_file(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        $itemid = 0;

        $draftitemid = 0;
        file_prepare_draft_area(
            $draftitemid,
            $quizform->get_context()->id,
            'quizaccess_seb',
            'filemanager_sebconfigfile',
            $itemid
        );

        if (self::can_upload_seb_file($quizform->get_context())) {
            $element = $mform->createElement(
                'filemanager',
                'filemanager_sebconfigfile',
                get_string('filemanager_sebconfigfile', 'quizaccess_seb'),
                null,
                self::get_filemanager_options()
            );
        } else {
            $element = $mform->createElement('hidden', 'filemanager_sebconfigfile');
        }

        self::insert_element($quizform, $mform, $element);
        self::set_type($quizform, $mform, 'filemanager_sebconfigfile', PARAM_RAW);
        self::set_default($quizform, $mform, 'filemanager_sebconfigfile', $draftitemid);
        self::add_help_button($quizform, $mform, 'filemanager_sebconfigfile');
    }

    /**
     * Add SEB settings elements.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_seb_config_elements(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        $defaults = self::get_quiz_defaults();
        $types = self::get_quiz_element_types();

        foreach (self::get_quiz_elements() as $name => $type) {

            if (!self::can_manage_setting($name, $quizform->get_context())) {
                $type = 'hidden';
            }

            $element = $mform->createElement($type, $name, get_string($name, 'quizaccess_seb'));
            self::insert_element($quizform, $mform, $element);
            unset($element); // We need to make sure each &element only references the current element in loop.

            self::add_help_button($quizform, $mform, $name);

            if (isset($defaults[$name])) {
                self::set_default($quizform, $mform, $name, $defaults[$name]);
            }

            if (isset($types[$name])) {
                self::set_type($quizform, $mform, $name, $types[$name]);
            }

            if (!self::can_manage_setting($name, $quizform->get_context())) {
                $mform->freeze([$name]);
            }
        }
    }

    /**
     * Hide SEB elements if required.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function hide_seb_elements(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        foreach (self::get_quiz_hideifs() as $elname => $rules) {
            if ($mform->elementExists($elname)) {
                foreach ($rules as $hideif) {
                    $mform->hideIf(
                        $hideif->get_element(),
                        $hideif->get_dependantname(),
                        $hideif->get_condition(),
                        $hideif->get_dependantvalue()
                    );
                }
            }
        }
    }

    /**
     * Lock SEB elements if required.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function lock_seb_elements(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        if (self::is_seb_settings_locked($quizform->get_instance()) || self::is_conflicting_permissions($quizform->get_context())) {
            $mform->freeze('seb_requiresafeexambrowser');
            $mform->freeze('seb_templateid');

            $quizsettings = quiz_settings::get_record(['quizid' => (int) $quizform->get_instance()]);

            if (!empty($quizsettings) && $quizsettings->get('requiresafeexambrowser') == self::USE_SEB_UPLOAD_CONFIG) {
                self::remove_element($quizform, $mform, 'filemanager_sebconfigfile');

                if ($link = self::get_uploaded_seb_file_download_link($quizform, $mform)) {
                    $element = $mform->createElement(
                        'static',
                        'filemanager_sebconfigfile',
                        get_string('filemanager_sebconfigfile', 'quizaccess_seb'),
                        $link
                    );
                    self::insert_element($quizform, $mform, $element, 'seb_suppresssebdownloadlink');
                }
            }

            if (empty($quizsettings) || $quizsettings->get('requiresafeexambrowser') != self::USE_SEB_TEMPLATE) {
                $mform->removeElement('seb_templateid');
            }

            $mform->freeze(array_keys(self::get_quiz_elements()));
        }
    }

    /**
     * Return uploaded SEB config file link.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @return string
     */
    protected static function get_uploaded_seb_file_download_link(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) : string {
        $link = '';
        $file = self::get_module_context_sebconfig_file($quizform->get_coursemodule()->id);

        if ($file) {
            $url = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                true
            );
            $link = \html_writer::link($url, get_string('downloadsebconfig', 'quizaccess_seb'));
        }

        return $link;
    }

    /**
     * Get the type of element for each of the form elements in quiz settings.
     *
     * Contains all setting elements. Array key is name of 'form element'/'database column (excluding prefix)'.
     *
     * @return array All quiz form elements to be added and their types.
     */
    public static function get_quiz_elements() : array {
        return [
            'seb_suppresssebdownloadlink' => 'selectyesno',
            'seb_linkquitseb' => 'text',
            'seb_userconfirmquit' => 'selectyesno',
            'seb_allowuserquitseb' => 'selectyesno',
            'seb_quitpassword' => 'passwordunmask',
            'seb_allowreloadinexam' => 'selectyesno',
            'seb_showsebtaskbar' => 'selectyesno',
            'seb_showreloadbutton' => 'selectyesno',
            'seb_showtime' => 'selectyesno',
            'seb_showkeyboardlayout' => 'selectyesno',
            'seb_showwificontrol' => 'selectyesno',
            'seb_enableaudiocontrol' => 'selectyesno',
            'seb_muteonstartup' => 'selectyesno',
            'seb_allowspellchecking' => 'selectyesno',
            'seb_activateurlfiltering' => 'selectyesno',
            'seb_filterembeddedcontent' => 'selectyesno',
            'seb_expressionsallowed' => 'textarea',
            'seb_regexallowed' => 'textarea',
            'seb_expressionsblocked' => 'textarea',
            'seb_regexblocked' => 'textarea',
            'seb_allowedbrowserexamkeys' => 'textarea',
        ];
    }


    /**
     * Get the types of the quiz settings elements.
     * @return array List of types for the setting elements.
     */
    public static function get_quiz_element_types() : array {
        return [
            'seb_suppresssebdownloadlink' => PARAM_BOOL,
            'seb_linkquitseb' => PARAM_RAW,
            'seb_userconfirmquit' => PARAM_BOOL,
            'seb_allowuserquitseb' => PARAM_BOOL,
            'seb_quitpassword' => PARAM_RAW,
            'seb_allowreloadinexam' => PARAM_BOOL,
            'seb_showsebtaskbar' => PARAM_BOOL,
            'seb_showreloadbutton' => PARAM_BOOL,
            'seb_showtime' => PARAM_BOOL,
            'seb_showkeyboardlayout' => PARAM_BOOL,
            'seb_showwificontrol' => PARAM_BOOL,
            'seb_enableaudiocontrol' => PARAM_BOOL,
            'seb_muteonstartup' => PARAM_BOOL,
            'seb_allowspellchecking' => PARAM_BOOL,
            'seb_activateurlfiltering' => PARAM_BOOL,
            'seb_filterembeddedcontent' => PARAM_BOOL,
            'seb_expressionsallowed' => PARAM_RAW,
            'seb_regexallowed' => PARAM_RAW,
            'seb_expressionsblocked' => PARAM_RAW,
            'seb_regexblocked' => PARAM_RAW,
            'seb_allowedbrowserexamkeys' => PARAM_RAW,
        ];
    }

    /**
     * Check that we have conflicting permissions.
     *
     * In Some point we can have settings save by the person who use specific
     * type of SEB usage (e.g. use templates). But then another person who can't
     * use template (but still can update other settings) edit the same quiz. This is
     * conflict of permissions and we'd like to build the settings form having this in
     * mind.
     *
     * @param \context $context Context used with capability checking.
     *
     * @return bool
     */
    public static function is_conflicting_permissions(\context $context) {
        if ($context instanceof \context_course) {
            return false;
        }

        $settings = quiz_settings::get_record(['cmid' => (int) $context->instanceid]);

        if (empty($settings)) {
            return false;
        }

        if (!self::can_use_seb_template($context) &&
            $settings->get('requiresafeexambrowser') == self::USE_SEB_TEMPLATE) {
            return true;
        }

        if (!self::can_upload_seb_file($context) &&
            $settings->get('requiresafeexambrowser') == self::USE_SEB_UPLOAD_CONFIG) {
            return true;
        }

        return false;
    }

    /**
     * Returns a list of all options of SEB usage.
     *
     * @param \context $context Context used with capability checking selection options.
     * @return array
     */
    public static function get_requiresafeexambrowser_options(\context $context) : array {
        $options[self::USE_SEB_NO] = get_string('no');
        $options[self::USE_SEB_CONFIG_MANUALLY] = get_string('seb_use_manually', 'quizaccess_seb');

        if (self::can_use_seb_template($context) || self::is_conflicting_permissions($context)) {
            if (!empty(self::get_template_options())) {
                $options[self::USE_SEB_TEMPLATE] = get_string('seb_use_template', 'quizaccess_seb');
            }
        }

        if (self::can_upload_seb_file($context) || self::is_conflicting_permissions($context)) {
            $options[self::USE_SEB_UPLOAD_CONFIG] = get_string('seb_use_upload', 'quizaccess_seb');
        }

        $options[self::USE_SEB_CLIENT_CONFIG] = get_string('seb_use_client', 'quizaccess_seb');

        return $options;
    }

    /**
     * Returns a list of templates.
     * @return array
     */
    protected static function get_template_options() : array {
        $templates = [];
        $records = template::get_records(['enabled' => 1], 'name');
        if ($records) {
            foreach ($records as $record) {
                $templates[$record->get('id')] = $record->get('name');
            }
        }

        return $templates;
    }

    /**
     * Returns a list of options for the file manager element.
     * @return array
     */
    public static function get_filemanager_options() : array {
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.seb']
        ];
    }

    /**
     * Get the default values of the quiz settings.
     *
     * Array key is name of 'form element'/'database column (excluding prefix)'.
     *
     * @return array List of settings and their defaults.
     */
    public static function get_quiz_defaults() : array {
        return [
            'seb_suppresssebdownloadlink' => 0,
            'seb_linkquitseb' => '',
            'seb_userconfirmquit' => 1,
            'seb_allowuserquitseb' => 1,
            'seb_quitpassword' => '',
            'seb_allowreloadinexam' => 1,
            'seb_showsebtaskbar' => 1,
            'seb_showreloadbutton' => 1,
            'seb_showtime' => 1,
            'seb_showkeyboardlayout' => 1,
            'seb_showwificontrol' => 0,
            'seb_enableaudiocontrol' => 0,
            'seb_muteonstartup' => 0,
            'seb_allowspellchecking' => 0,
            'seb_activateurlfiltering' => 0,
            'seb_filterembeddedcontent' => 0,
            'seb_expressionsallowed' => '',
            'seb_regexallowed' => '',
            'seb_expressionsblocked' => '',
            'seb_regexblocked' => '',
            'seb_allowedbrowserexamkeys' => '',
        ];
    }

    /**
     * Get the conditions that an element should be hid in the form. Expects matching using 'eq'.
     *
     * Array key is name of 'form element'/'database column (excluding prefix)'.
     * Values are instances of hideif_rule class.
     *
     * @return \quizaccess_seb\hideif_rule[] List of rules per element.
     */
    public static function get_quiz_hideifs() : array {
        return [
            'seb_templateid' => [
                new hideif_rule('seb_templateid', 'seb_requiresafeexambrowser', 'noteq', self::USE_SEB_TEMPLATE),
            ],
            'filemanager_sebconfigfile' => [
                new hideif_rule('filemanager_sebconfigfile', 'seb_requiresafeexambrowser', 'noteq', self::USE_SEB_UPLOAD_CONFIG),
            ],
            'seb_showsebtaskbar' => [
                new hideif_rule('seb_showsebtaskbar', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showsebtaskbar', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_showsebtaskbar', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showsebtaskbar', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_showwificontrol' => [
                new hideif_rule('seb_showwificontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showwificontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_showwificontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showwificontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showwificontrol', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_showreloadbutton' => [
                new hideif_rule('seb_showreloadbutton', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showreloadbutton', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_showreloadbutton', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showreloadbutton', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showreloadbutton', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_showtime' => [
                new hideif_rule('seb_showtime', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showtime', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_showtime', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showtime', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showtime', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_showkeyboardlayout' => [
                new hideif_rule('seb_showkeyboardlayout', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showkeyboardlayout', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_showkeyboardlayout', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showkeyboardlayout', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showkeyboardlayout', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_allowuserquitseb' => [
                new hideif_rule('seb_allowuserquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_allowuserquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_quitpassword' => [
                new hideif_rule('seb_quitpassword', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_quitpassword', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_quitpassword', 'seb_allowuserquitseb', 'eq', 0),
            ],
            'seb_linkquitseb' => [
                new hideif_rule('seb_linkquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_linkquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_linkquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_linkquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_userconfirmquit' => [
                new hideif_rule('seb_userconfirmquit', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_userconfirmquit', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_userconfirmquit', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_userconfirmquit', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_userconfirmquit', 'seb_linkquitseb', 'eq', 0),
            ],
            'seb_enableaudiocontrol' => [
                new hideif_rule('seb_enableaudiocontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_enableaudiocontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_enableaudiocontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_enableaudiocontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_muteonstartup' => [
                new hideif_rule('seb_muteonstartup', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_muteonstartup', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_muteonstartup', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_muteonstartup', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_muteonstartup', 'seb_enableaudiocontrol', 'eq', 0),
            ],
            'seb_allowspellchecking' => [
                new hideif_rule('seb_allowspellchecking', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_allowspellchecking', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_allowspellchecking', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_allowspellchecking', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_allowreloadinexam' => [
                new hideif_rule('seb_allowreloadinexam', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_allowreloadinexam', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_allowreloadinexam', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_allowreloadinexam', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),

            ],
            'seb_activateurlfiltering' => [
                new hideif_rule('seb_activateurlfiltering', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_activateurlfiltering', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_activateurlfiltering', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_activateurlfiltering', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_filterembeddedcontent' => [
                new hideif_rule('seb_filterembeddedcontent', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_filterembeddedcontent', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_filterembeddedcontent', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_filterembeddedcontent', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_filterembeddedcontent', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_expressionsallowed' => [
                new hideif_rule('seb_expressionsallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_expressionsallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_expressionsallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_expressionsallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_expressionsallowed', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_regexallowed' => [
                new hideif_rule('seb_regexallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_regexallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_regexallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_regexallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_regexallowed', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_expressionsblocked' => [
                new hideif_rule('seb_expressionsblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_expressionsblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_expressionsblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_expressionsblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_expressionsblocked', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_regexblocked' => [
                new hideif_rule('seb_regexblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_regexblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_TEMPLATE),
                new hideif_rule('seb_regexblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_regexblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_regexblocked', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_suppresssebdownloadlink' => [
                new hideif_rule('seb_suppresssebdownloadlink', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_suppresssebdownloadlink', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_allowedbrowserexamkeys' => [
                new hideif_rule('seb_allowedbrowserexamkeys', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
            ]
        ];
    }

    /**
     * Validate that if a file has been uploaded by current user, that it is a valid PLIST XML file.
     * This function is only called if requiresafeexambrowser == settings_provider::USE_SEB_UPLOAD_CONFIG.
     *
     * @param string $itemid Item ID of file in user draft file area.
     * @return void|lang_string
     */
    public static function validate_draftarea_configfile($itemid) {
        // When saving the settings, this value will be null.
        if (is_null($itemid)) {
            return;
        }
        // If there is a config file uploaded, make sure it is a PList XML file.
        $file = self::get_current_user_draft_file($itemid);

        // If we require an SEB config uploaded, and the file exists, parse it.
        if ($file) {
            if (!helper::is_valid_seb_config($file->get_content())) {
                return new lang_string('fileparsefailed', 'quizaccess_seb');
            }
        }

        // If we require an SEB config uploaded, and the file does not exist, error.
        if (!$file) {
            return new lang_string('filenotpresent', 'quizaccess_seb');
        }
    }

    /**
     * Try and get a file in the user draft filearea by itemid.
     *
     * @param string $itemid Item ID of the file.
     * @return stored_file|null Returns null if no file is found.
     */
    public static function get_current_user_draft_file(string $itemid) : ?stored_file { // @codingStandardsIgnoreLine
        global $USER;
        $context = context_user::instance($USER->id);
        $fs = get_file_storage();
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $itemid, 'id DESC', false)) {
            return null;
        }
        return reset($files);
    }

    /**
     * Get the file that is stored in the course module file area.
     *
     * @param string $cmid The course module id which is used as an itemid reference.
     * @return stored_file|null Returns null if no file is found.
     */
    public static function get_module_context_sebconfig_file(string $cmid) : ?stored_file { // @codingStandardsIgnoreLine
        $fs = new \file_storage();
        $context = context_module::instance($cmid);

        if (!$files = $fs->get_area_files($context->id, 'quizaccess_seb', 'filemanager_sebconfigfile', 0,
            'id DESC', false)) {
            return null;
        }

        return reset($files);
    }

    /**
     * Saves filemanager_sebconfigfile files to the moodle storage backend.
     *
     * @param string $draftitemid The id of the draft area to use.
     * @param string $cmid The cmid of for the quiz.
     * @return bool Always true
     */
    public static function save_filemanager_sebconfigfile_draftarea(string $draftitemid, string $cmid) : bool {
        if ($draftitemid) {
            $context = context_module::instance($cmid);
            file_save_draft_area_files($draftitemid, $context->id, 'quizaccess_seb', 'filemanager_sebconfigfile',
                0, []);
        }

        return true;
    }

    /**
     * Cleanup function to delete the saved config when it has not been specified.
     * This will be called when settings_provider::USE_SEB_UPLOAD_CONFIG is not true.
     *
     * @param string $cmid The cmid of for the quiz.
     * @return bool Always true or exception if error occurred
     */
    public static function delete_uploaded_config_file(string $cmid) : bool {
        $file = self::get_module_context_sebconfig_file($cmid);

        if (!empty($file)) {
            return $file->delete();
        }

        return false;
    }

    /**
     * Check if the current user can configure SEB.
     *
     * @param \context $context Context to check access in.
     * @return bool
     */
    public static function can_configure_seb(\context $context) : bool {
        return has_capability('quizaccess/seb:manage_seb_requiresafeexambrowser', $context);
    }

    /**
     * Check if the current user can use preconfigured templates.
     *
     * @param \context $context Context to check access in.
     * @return bool
     */
    public static function can_use_seb_template(\context $context) : bool {
        return has_capability('quizaccess/seb:manage_seb_templateid', $context);
    }

    /**
     * Check if the current user can upload own SEB config file.
     *
     * @param \context $context Context to check access in.
     * @return bool
     */
    public static function can_upload_seb_file(\context $context) : bool {
        return has_capability('quizaccess/seb:manage_filemanager_sebconfigfile', $context);
    }

    /**
     * Check if the current user can manage provided SEB setting.
     *
     * @param string $settingname Name of the setting.
     * @param \context $context Context to check access in.
     * @return bool
     */
    public static function can_manage_setting(string $settingname, \context $context) : bool {
        $capability = 'quizaccess/seb:manage_' . $settingname;

        // Capability must exist.
        if (!$capinfo = get_capability_info($capability)) {
            throw new \coding_exception("Capability '{$capability}' was not found! This has to be fixed in code.");
        }

        return has_capability($capability, $context);
    }

    /**
     * Check if settings is locked.
     *
     * @param int $quizid Quiz ID.
     * @return bool
     */
    public static function is_seb_settings_locked($quizid) : bool {
        if (empty($quizid)) {
            return false;
        }

        return quiz_has_attempts($quizid);
    }

    /**
     * Filter a standard class by prefix.
     *
     * @param stdClass $settings Quiz settings object.
     * @return stdClass Filtered object.
     */
    public static function filter_by_prefix(\stdClass $settings): stdClass {
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
    public static function filter_plugin_settings(stdClass $settings) : stdClass {
        $settings = self::filter_by_prefix($settings);
        return self::strip_all_prefixes($settings);
    }

    /**
     * Strip the seb_ prefix from each setting key.
     *
     * @param \stdClass $settings Object containing settings.
     * @return \stdClass The modified settings object.
     */
    public static function strip_all_prefixes(\stdClass $settings): stdClass {
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
    public static function add_prefix(string $name): string {
        if (strpos($name, 'seb_') !== 0) {
            $name = 'seb_' . $name;
        }
        return $name;
    }
}
