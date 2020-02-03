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

use CFPropertyList\CFPropertyList;
use context_course;
use context_module;
use context_user;
use lang_string;
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
     * Get the type of element for each of the form elements in quiz settings.
     *
     * Contains all setting elements. Array key is name of 'form element'/'database column (excluding prefix)'.
     *
     * @param context_course|context_module $context Optional parameter, context used with capability checking selection options.
     * @return array All quiz form elements to be added and their types.
     */
    public static function get_quiz_elements($context = null) : array {
        return [
            'seb' => 'header',
            'seb_requiresafeexambrowser' => ['select', self::get_requiresafeexambrowser_options($context)],
            'seb_sebconfigtemplate' => ['select', self::get_template_options()],
            'filemanager_sebconfigfile' => ['filemanager', self::get_filemanager_options()],
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
            'seb_requiresafeexambrowser' => PARAM_INT,
            'seb_sebconfigtemplate' => PARAM_BOOL,
            'seb_suppresssebdownloadlink' => PARAM_BOOL,
            'filemanager_sebconfigfile' => PARAM_RAW,
            'seb_linkquitseb' => PARAM_URL,
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
     * Returns a list of all options of SEB usage.
     * @param context_course|context_module $context Optional parameter, context used with capability checking selection options.
     * @return array
     */
    public static function get_requiresafeexambrowser_options($context = null) : array {
        $options[self::USE_SEB_NO] = get_string('no');
        $options[self::USE_SEB_CONFIG_MANUALLY] = get_string('seb_use_manually', 'quizaccess_seb');

        // @codingStandardsIgnoreStart
        // TODO: Implement following features and uncomment options.
        //if ($context && has_capability('quizaccess/seb:manage_seb_sebconfigtemplate', $context)) {
        //    $options[self::USE_SEB_TEMPLATE] = get_string('seb_use_template', 'quizaccess_seb');
        //}

        if ($context && has_capability('quizaccess/seb:manage_filemanager_sebconfigfile', $context)) {
            $options[self::USE_SEB_UPLOAD_CONFIG] = get_string('seb_use_upload', 'quizaccess_seb');
        }

        $options[self::USE_SEB_CLIENT_CONFIG] = get_string('seb_use_client', 'quizaccess_seb');
        // @codingStandardsIgnoreEnd

        return $options;
    }

    /**
     * Returns a list of templates.
     * @return array
     */
    public static function get_template_options() : array {
        // TODO: implement as part of Issue #19.
        return [];
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
     * TODO: Update sebconfigtemplate default after templates are implemented.
     *
     * @return array List of settings and their defaults.
     */
    public static function get_quiz_defaults() : array {
        return [
            'seb_requiresafeexambrowser' => self::USE_SEB_NO,
            'seb_sebconfigtemplate' => 0,
            'filemanager_sebconfigfile' => null,
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
            'seb_sebconfigtemplate' => [
                new hideif_rule('seb_sebconfigtemplate', 'seb_requiresafeexambrowser', 'noteq', self::USE_SEB_TEMPLATE),
            ],
            'filemanager_sebconfigfile' => [
                new hideif_rule('filemanager_sebconfigfile', 'seb_requiresafeexambrowser', 'noteq', self::USE_SEB_UPLOAD_CONFIG),
            ],
            'seb_showsebtaskbar' => [
                new hideif_rule('seb_showsebtaskbar', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showsebtaskbar', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showsebtaskbar', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_showwificontrol' => [
                new hideif_rule('seb_showwificontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showwificontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showwificontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showwificontrol', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_showreloadbutton' => [
                new hideif_rule('seb_showreloadbutton', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showreloadbutton', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showreloadbutton', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showreloadbutton', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_showtime' => [
                new hideif_rule('seb_showtime', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showtime', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showtime', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showtime', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_showkeyboardlayout' => [
                new hideif_rule('seb_showkeyboardlayout', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_showkeyboardlayout', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_showkeyboardlayout', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_showkeyboardlayout', 'seb_showsebtaskbar', 'eq', 0),
            ],
            'seb_allowuserquitseb' => [
                new hideif_rule('seb_allowuserquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_allowuserquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_allowuserquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_quitpassword' => [
                new hideif_rule('seb_quitpassword', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_quitpassword', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_quitpassword', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_quitpassword', 'seb_allowuserquitseb', 'eq', 0),
            ],
            'seb_linkquitseb' => [
                new hideif_rule('seb_linkquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_linkquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_linkquitseb', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_userconfirmquit' => [
                new hideif_rule('seb_userconfirmquit', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_userconfirmquit', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_userconfirmquit', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_userconfirmquit', 'seb_linkquitseb', 'eq', 0),
            ],
            'seb_enableaudiocontrol' => [
                new hideif_rule('seb_enableaudiocontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_enableaudiocontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_enableaudiocontrol', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_muteonstartup' => [
                new hideif_rule('seb_muteonstartup', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_muteonstartup', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_muteonstartup', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_muteonstartup', 'seb_enableaudiocontrol', 'eq', 0),
            ],
            'seb_allowspellchecking' => [
                new hideif_rule('seb_allowspellchecking', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_allowspellchecking', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_allowspellchecking', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_allowreloadinexam' => [
                new hideif_rule('seb_allowreloadinexam', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_allowreloadinexam', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_allowreloadinexam', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),

            ],
            'seb_activateurlfiltering' => [
                new hideif_rule('seb_activateurlfiltering', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_activateurlfiltering', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_activateurlfiltering', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_filterembeddedcontent' => [
                new hideif_rule('seb_filterembeddedcontent', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_filterembeddedcontent', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_filterembeddedcontent', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_filterembeddedcontent', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_expressionsallowed' => [
                new hideif_rule('seb_expressionsallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_expressionsallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_expressionsallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_expressionsallowed', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_regexallowed' => [
                new hideif_rule('seb_regexallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_regexallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_regexallowed', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_regexallowed', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_expressionsblocked' => [
                new hideif_rule('seb_expressionsblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_expressionsblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_expressionsblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_expressionsblocked', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_regexblocked' => [
                new hideif_rule('seb_regexblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_regexblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_regexblocked', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
                new hideif_rule('seb_regexblocked', 'seb_activateurlfiltering', 'eq', 0),
            ],
            'seb_suppresssebdownloadlink' => [
                new hideif_rule('seb_suppresssebdownloadlink', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_suppresssebdownloadlink', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_UPLOAD_CONFIG),
                new hideif_rule('seb_suppresssebdownloadlink', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
            ],
            'seb_allowedbrowserexamkeys' => [
                new hideif_rule('seb_allowedbrowserexamkeys', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_NO),
                new hideif_rule('seb_allowedbrowserexamkeys', 'seb_requiresafeexambrowser', 'eq', self::USE_SEB_CLIENT_CONFIG),
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
            $plist = new CFPropertyList();
            try {
                $plist->parse($file->get_content());
            } catch (\Exception $e) {
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

        if (!$files = $fs->get_area_files($context->id, 'quizaccess_seb', 'filemanager_sebconfigfile', $cmid,
            'id DESC', false)) {
            return null;
        }

        return reset($files);
    }

    /**
     * Saves filemanager_sebconfigfile files to the moodle storage backend.
     *
     * @param string $cmid The cmid of for the quiz.
     * @return bool Always true
     */
    public static function save_filemanager_sebconfigfile_draftarea(string $draftitemid, string $cmid) : bool {
        if ($draftitemid) {
            $context = context_module::instance($cmid);
            file_save_draft_area_files($draftitemid, $context->id, 'quizaccess_seb', 'filemanager_sebconfigfile',
                $cmid, []);
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
}

