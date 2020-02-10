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


defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Render the inplace editable used to edit the template enable state.
     *
     * @param \quizaccess_seb\template $template The template to edit.
     *
     * @return string
     */
    public static function render_templ_enabled_inplace_editable(template $template) {
        global $OUTPUT;

        if ($template->get('enabled')) {
            $icon = 't/hide';
            $alt = get_string('disable');
            $value = 1;
        } else {
            $icon = 't/show';
            $alt = get_string('enable');
            $value = 0;
        }

        $editable = new \core\output\inplace_editable(
            'quizaccess_seb',
            'templenabled',
            $template->get('id'),
            true,
            $OUTPUT->pix_icon($icon, $alt, 'moodle', [
                'title' => $alt,
            ]),
            $value
        );

        $editable->set_type_toggle();

        return $editable;
    }

    /**
     * Render the inplace editable used to edit the template name.
     *
     * @param \quizaccess_seb\template $template The template to edit.
     *
     * @return string
     */
    public static function render_templ_name_inplace_editable(template $template) {
        return new \core\output\inplace_editable(
            'quizaccess_seb',
            'templname',
            $template->get('id'),
            true,
            \html_writer::link(
                new \moodle_url(template_controller::get_base_url(), [
                    'id' => $template->get('id'),
                    'action' => template_controller::ACTION_EDIT,
                ]),
                $template->get('name')
            ),
            $template->get('name')
        );
    }

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

}

