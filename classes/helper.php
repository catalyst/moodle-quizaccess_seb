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
 * Helper class.
 *
 * @package    quizaccess_seb
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;


use CFPropertyList\CFPropertyList;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../vendor/autoload.php');

class helper {
    /**
     * Get a filler icon for display in the actions column of a table.
     *
     * @param string $url The URL for the icon.
     * @param string $icon The icon identifier.
     * @param string $alt The alt text for the icon.
     * @param string $iconcomponent The icon component.
     * @param array $options Display options.
     * @return string
     */
    public static function format_icon_link($url, $icon, $alt, $iconcomponent = 'moodle', $options = array()) {
        global $OUTPUT;

        return $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, $iconcomponent, [
                'title' => $alt,
            ]),
            null,
            $options
        );
    }

    /**
     * Validate seb config string.
     *
     * @param string $sebconfig
     * @return bool
     */
    public static function is_valid_seb_config(string $sebconfig) : bool {
        $result = true;

        set_error_handler(function($errno, $errstr, $errfile, $errline ){
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        $plist = new CFPropertyList();
        try {
            $plist->parse($sebconfig);
        } catch (\ErrorException $e) {
            $result = false;
        }

        restore_error_handler();

        return $result;
    }

}

