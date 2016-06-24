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
 * Badges block edit form definition.
 *
 * @package    block_game_badges
 * @copyright  20016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_game_badges_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $COURSE;

        $context = context_course::instance($COURSE->id);
		if(has_capability('block/game_badges:managebadges', $context)) {

			$mform->addElement('header', 'configheader', get_string('configpage_header', 'block_game_badges'));
			
			$mform->addElement('text', 'config_title', get_string('configpage_titletext', 'block_game_badges'));
			$mform->setType('config_title', PARAM_TEXT);

        }

    }

}