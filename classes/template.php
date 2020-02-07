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
 * Entity model representing template settings for the seb plugin.
 *
 * @package    quizaccess_seb
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;


defined('MOODLE_INTERNAL') || die();

class template extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'quizaccess_seb_template';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'description' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'content' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'enabled' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'sortorder' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }
}
