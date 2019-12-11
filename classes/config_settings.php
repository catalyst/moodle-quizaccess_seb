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
 * Data object containing SEB config settings with defaults if required.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

defined('MOODLE_INTERNAL') || die();

class config_settings {

    /** @var string SETTINGS_TABLE DB table that contains quiz settings. */
    const SETTINGS_TABLE = 'quizaccess_seb_quizsettings';

    /** @var int $quizid Quiz ID of quiz the settings relate to. */
    private $quizid;

    /** @var \stdClass $settings Settings used to create class. */
    private $settings;

    /** @var int $existingsettingsid ID of existing settings in table. Defaults to -1 if none exist. */
    private $existingsettingsid = -1;

    /**
     * config_settings constructor.
     *
     * @param int $quizid ID of quiz that the settings belong to.
     * @param \stdClass|null $settings New quiz settings. May be provided by quiz settings form.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(int $quizid, \stdClass $settings = null) {
        global $DB;

        $this->quizid = $quizid;

        // Try and retrieve existing settings, otherwise assign null.
        $existingsettings = $DB->get_record(self::SETTINGS_TABLE, ['quizid' => $quizid], '*');
        if (empty($existingsettings)) {
            $existingsettings = new \stdClass();
        } else {
            $this->existingsettingsid = $existingsettings->id;
        }

        // If new settings are empty, set existing settings, otherwise merge existing settings with new settings.
        if (empty((array) $settings)) {
            $this->settings = $existingsettings;
        } else {
            $this->settings = $this->merge_new_and_existing_settings($this->strip_prefix($settings), $existingsettings);
        }

        // Associate settings with quiz.
        $this->settings->quizid = $quizid;

        // TODO: Process sebconfigtemplate into templateid.
        $this->settings->templateid = 0;
        unset($this->settings->sebconfigtemplate);

        // Check to make sure all required settings are set up.
        foreach ($this->get_required_settings_keys() as $name) {
            if (!array_key_exists($name, $this->settings)) {
                throw new \moodle_exception('missingrequiredsettings', 'quizaccess_seb', '', '', "Missing setting: $name");
            }
        }
    }

    /**
     * Static constructor to create settings from existing quiz.
     *
     * @param string $quizid ID of quiz that the settings belong to.
     * @return config_settings This instance.
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function with_quizid(string $quizid) {
        return new self($quizid);
    }

    /**
     * Static constructor to create settings from form data.
     *
     * @param \stdClass $quiz New quiz settings. May be provided by quiz settings form. Must include 'id'.
     * @return config_settings This instance.
     *
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function with_form_data(\stdClass $quiz) {
        $settings = new \stdClass;
        if (empty($quiz->id)) {
            throw new \invalid_parameter_exception('Cannot construct config_settings without $quiz->id');
        }

        foreach ($quiz as $key => $quizsetting) {
            if (strpos($key, 'seb_') === 0) {
                $settings->$key = $quizsetting;
            }
        }

        return new self($quiz->id, $settings);
    }

    /**
     * Check if the settings exist.
     *
     * @return bool Whether any new or existing settings were found for quiz.
     */
    public function exists() {
        // Cast settings to array as empty stdClass will return true.
        return !empty((array) $this->settings);
    }

    /**
     * Get the generic object containing all quiz settings.
     *
     * @return \stdClass An object containing all new and existing settings for quiz.
     */
    public function get_settings() : \stdClass {
        return $this->settings;
    }

    /**
     * Get the itemid of the upoaded seb config file if it exists.
     *
     * @return string Item ID of stored file.
     */
    public function get_config_itemid() {
        if ($this->exists() && !empty($this->settings->sebconfigfile)) {
            return $this->settings->sebconfigfile;
        }
        return '';
    }

    /**
     * Save the settings to the DB. May be an update or a create.
     *
     * @return bool Whether or not the settings could be saved.
     */
    public function save_settings() : bool {
        global $DB;
        $success = false;

        // If existing settings, update record, otherwise create record.
        try {
            if ($this->existingsettingsid !== -1) {
                $this->settings->id = $this->existingsettingsid; // Set the existing id of the settings.
                $success = $DB->update_record(self::SETTINGS_TABLE, $this->settings);
            } else {
                $this->existingsettingsid = $DB->insert_record(self::SETTINGS_TABLE, $this->settings);
                // Check if the insert was successful, and if not, reset existingsettingsid to -1.
                if ($this->existingsettingsid === false) {
                    $this->existingsettingsid = -1;
                } else {
                    $success = true;
                }
            }
        } catch (\dml_exception $e) {
            debugging('quizaccess_seb - save settings: ' . $e->getMessage());
        }

        return $success;
    }

    /**
     * Delete files and settings from DB for this quiz.
     *
     * @return bool Whether or not settings were deleted successfully.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function delete_settings() : bool {
        global $DB;

        // Delete settings from DB.
        return $DB->delete_records(self::SETTINGS_TABLE, ['quizid' => $this->quizid]);
    }

    /**
     * Strip the seb_ prefix from each setting key.
     *
     * @param \stdClass $settings Object containing settings.
     * @return \stdClass The modified settings object.
     */
    private function strip_prefix(\stdClass $settings) : \stdClass {
        $newsettings = new \stdClass();
        foreach ($settings as $name => $setting) {
            $newname = preg_replace("/^seb_/", "", $name);
            $newsettings->$newname = $setting; // Add new key.
        }
        return $newsettings;
    }

    /**
     * Merge new settings and existing settings into one object.
     *
     * @param \stdClass $newsettings Object containing settings.
     * @param \stdClass $existingsettings Object containing settings.
     * @return \stdClass Object containing settings.
     */
    private function merge_new_and_existing_settings(\stdClass $newsettings, \stdClass $existingsettings) : \stdClass {
        // Overwrite existing settings with new settings.
        foreach ($newsettings as $name => $setting) {
            // Assign to merged settings.
            $existingsettings->$name = $setting;
        }
        return $existingsettings;
    }

    /**
     * Get a list of array keys representing all required settings in settings DB.
     *
     * @return array List of required settings.
     */
    private function get_required_settings_keys() : array {
        return [
            'quizid',
            'templateid',
            'requiresafeexambrowser',
            'showsebtaskbar',
            'showwificontrol',
            'showreloadbutton',
            'showtime',
            'showkeyboardlayout',
            'allowuserquitseb',
            'linkquitseb',
            'userconfirmquit',
            'enableaudiocontrol',
            'muteonstartup',
            'allowspellchecking',
            'allowreloadinexam',
            'activateurlfiltering',
            'filterembeddedcontent',
            'suppresssebdownloadlink',
        ];
    }
}
