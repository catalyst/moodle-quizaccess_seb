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
 * Action buttons that provide a link to the user.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

class action_buttons implements renderable, templatable {

    /** @var array Action buttons. */
    private $buttons;

    /**
     * Initialize class.
     */
    public function __construct() {
        $this->buttons = [];
    }

    /**
     * Add a new button.
     *
     * @param string $link Link for button.
     * @param string $text Button text.
     */
    public function add_button(string $link, string $text) {
        $this->buttons[] = (object) ['link' => $link, 'text' => $text];
    }

    /**
     * Delete all buttons added.
     */
    public function delete_all() {
        $this->buttons = [];
    }

    /**
     * Delete last button added.
     */
    public function delete_last() {
        array_pop($this->buttons);
    }

    /**
     * Export class ready for conversion to HTML.
     *
     * @inheritDoc
     */
    public function export_for_template(renderer_base $output) : stdClass {
        $data = new stdClass();
        $data->buttons = $this->buttons;
        return $data;
    }
}
