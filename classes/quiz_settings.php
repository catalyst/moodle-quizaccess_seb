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
 * Entity model representing quiz settings for the seb plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

use core\persistent;

defined('MOODLE_INTERNAL') || die();

class quiz_settings extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'quizaccess_seb_quizsettings';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'quizid' => [
                'type' => PARAM_INT,
            ],
            'templateid' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'requiresafeexambrowser' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'sebconfigfile' => [
                'type' => PARAM_TEXT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'showsebtaskbar' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'showwificontrol' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'showreloadbutton' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'showtime' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'showkeyboardlayout' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'allowuserquitseb' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'quitpassword' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'linkquitseb' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'userconfirmquit' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'enableaudiocontrol' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'muteonstartup' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'allowspellchecking' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'allowreloadinexam' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'activateurlfiltering' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'filterembeddedcontent' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'expressionsallowed' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'regexallowed' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'expressionsblocked' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'regexblocked' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'suppresssebdownloadlink' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'configkey' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
        ];
    }
}
