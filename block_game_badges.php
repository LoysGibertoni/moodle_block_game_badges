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
 * Badges block definition.
 *
 * @package    block_game_badges
 * @copyright  2016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_game_badges extends block_base {
	
    public function init() {
        $this->title = get_string('title', 'block_game_badges');
    }

	public function applicable_formats() {
        return array(
            'all'    => true
        );
    }
	
	public function instance_allow_multiple() {
	  return true;
	}
	
    public function get_content() {
		//global $DB, $USER;
		$this->content = new stdClass;
        $this->content->text = 'Hello';
        $this->content->footer = '';
        return $this->content;
    }

	public function specialization() {
		if(isset($this->config)) {
			if(empty($this->config->title)) {
				$this->title = get_string('title', 'block_game_badges');            
			}
			else {
				$this->title = $this->config->title;
			}
		}
	}

}