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
 * This file contains the activity badge award criteria type class
 *
 * @package    core
 * @subpackage badges
 * @copyright  2016 Loys Henrique Saccomano Gibertoni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');

/**
 * Badge award criteria -- award on activity completion
 *
 */
class award_criteria_achievement extends award_criteria {

    /* @var int Criteria [BADGE_CRITERIA_TYPE_ACHIEVEMENT] */
    public $criteriatype = BADGE_CRITERIA_TYPE_ACHIEVEMENT;

    public $required_param = 'achievement';
    public $optional_params = array();

    public function __construct($record) {
        parent::__construct($record);
    }

    /**
     * Gets the module instance from the database and returns it.
     * If no module instance exists this function returns false.
     *
     * @return stdClass|bool
     */
    private function get_achievement_info($aid) {
        global $DB;
        $rec = $DB->get_record('achievements', array('id' => $aid, 'deleted' => 0));

        if ($rec) {
            $block_info = $DB->get_record('block_instances', array('id' => $rec->blockinstanceid));
            $instance = block_instance('game_achievements', $block_info);

            $rec->blocktitle = $instance->title;

            return $rec;
        } else {
            return null;
        }
    }

    /**
     * Get criteria description for displaying to users
     *
     * @return string
     */
    public function get_details($short = '') {
        global $DB, $OUTPUT;
        $output = array();
        foreach ($this->params as $p) {
            $achievement = self::get_achievement_info($p['achievement']);
            if (!$achievement) {
                $str = $OUTPUT->error_text(get_string('error:nosuchmod', 'badges'));
                // TODO: Arrumar mensagem
            } else {
                $str = html_writer::tag('b', '"' . 'Bloco ' . $achievement->blocktitle . ' - ' . 'Conquista ' . (isset($achievement->name) ? $achievement->name . ' (' . $achievement->id . ')' : $achievement->id) . '"');
            }
            $output[] = $str;
        }

        if ($short) {
            return implode(', ', $output);
        } else {
            return html_writer::alist($output, array(), 'ul');
        }
    }

    /**
     * Add appropriate new criteria options to the form
     *
     */
    public function get_options(&$mform) {
        global $DB;

        $none = true;
        $existing = array();
        $missing = array();

        $achievements = $DB->get_records('achievements', array('deleted' => 0), 'id');
        $aids = array_keys($achievements);

        print_object($aids);

        if ($this->id !== 0) {
            $existing = array_keys($this->params);
            $missing = array_diff($existing, $aids);
        }

        if (!empty($missing)) {
            $mform->addElement('header', 'category_errors', get_string('criterror', 'badges'));
            $mform->addHelpButton('category_errors', 'criterror', 'badges');
            foreach ($missing as $m) {
                // TODO: Mudar mensagem de erro
                $this->config_options($mform, array('id' => $m, 'checked' => true,
                        'name' => 'Conquista inexistente.', 'error' => true));
                $none = false;
            }
        }

        if (!empty($achievements)) {
            $mform->addElement('header', 'first_header', $this->get_title());
            foreach ($achievements as $achievement) {
                $achievement_info = self::get_achievement_info($achievement->id);
                $checked = false;
                if (in_array($achievement->id, $existing)) {
                    $checked = true;
                }
                $param = array('id' => $achievement->id,
                        'checked' => $checked,
                        'name' => 'Bloco ' . $achievement_info->blocktitle . ' - ' . 'Conquista ' . (isset($achievement_info->name) ? $achievement_info->name . ' (' . $achievement_info->id . ')' : $achievement_info->id),
                        'error' => false
                        );

                $this->config_options($mform, $param);
                $none = false;
            }
        }

        // Add aggregation.
        if (!$none) {
            $mform->addElement('header', 'aggregation', get_string('method', 'badges'));
            $agg = array();
            $agg[] =& $mform->createElement('radio', 'agg', '', get_string('allmethodactivity', 'badges'), 1);
            $agg[] =& $mform->createElement('radio', 'agg', '', get_string('anymethodactivity', 'badges'), 2);
            $mform->addGroup($agg, 'methodgr', '', array('<br/>'), false);
            if ($this->id !== 0) {
                $mform->setDefault('agg', $this->method);
            } else {
                $mform->setDefault('agg', BADGE_CRITERIA_AGGREGATION_ANY);
            }
        }

        return array($none, 'Nenhuma conquista existente.');
    }

    /**
     * Review this criteria and decide if it has been completed
     *
     * @param int $userid User whose criteria completion needs to be reviewed.
     * @param bool $filtered An additional parameter indicating that user list
     *        has been reduced and some expensive checks can be skipped.
     *
     * @return bool Whether criteria is complete
     */
    public function review($userid, $filtered = false) {
        global $DB;

        $overall = false;
        foreach($this->params as $param) {
            $aid = $param['achievement'];
            $achievement_info = $this->get_achievement_info($aid);

            $completed = false;
            if($achievement_info->groupmode) {		
                $sql = "SELECT DISTINCT(groupid)
                    FROM {achievements_groups_log}
                    WHERE achievementid = :achievementid";
                $params['achievementid'] = $achievement_info->id;
                $completed_groupids = $DB->get_fieldset_sql($sql, $params);

                foreach($completed_groupids as $completed_groupid) {
                    if(groups_is_member($completed_groupid, $userid)) {
                        $completed = true;
                        break;
                    }
                }
            }
            else {
                $completed = $DB->record_exists('achievements_log', array('userid' => $userid, 'achievementid' => $achievement_info->id));
            }

            if($this->method == BADGE_CRITERIA_AGGREGATION_ALL) {
                if ($completed) {
                    $overall = true;
                    continue;
                } else {
                    return false;
                }
            } else {
                if ($completed) {
                    return true;
                } else {
                    $overall = false;
                    continue;
                }
            }
        }

        return $overall;
    }

    /**
     * Returns array with sql code and parameters returning all ids
     * of users who meet this particular criterion.
     *
     * @return array list($join, $where, $params)
     */
    public function get_completed_criteria_sql() {
        global $DB;

        $join = '';
        $where = '';
        $params = array();

        $users = array();
        
        if ($this->method == BADGE_CRITERIA_AGGREGATION_ANY) {
            foreach($this->params as $param) {
                $aid = $param['achievement'];
                $achievement_info = $this->get_achievement_info($aid);

                if($achievement_info->groupmode) {
                    $sql = "SELECT DISTINCT(groupid)
                        FROM {achievements_groups_log}
                        WHERE achievementid = :achievementid";
                    $params['achievementid'] = $achievement_info->id;
                    $completed_groupids = $DB->get_fieldset_sql($sql, $params);

                    foreach($completed_groupids as $completed_groupid) {
                        $users = array_merge($users, groups_get_members($completed_groupid));
                    }
                }
                else {
                    $sql = "SELECT DISTINCT(userid)
                        FROM {achievements_log}
                        WHERE achievementid = :achievementid";
                    $params['achievementid'] = $achievement_info->id;
                    $completed_userids = $DB->get_fieldset_sql($sql, $params);

                    foreach($completed_userids as $completed_userid) {
                        $users[$completed_userid] = null;
                    }
                }
            }
        } else {
            $foreach_first = true;
            foreach($this->params as $param) {
                $current_users = array();
                $aid = $param['achievement'];
                $achievement_info = $this->get_achievement_info($aid);

                if($achievement_info->groupmode) {
                    $sql = "SELECT DISTINCT(groupid)
                        FROM {achievements_groups_log}
                        WHERE achievementid = :achievementid";
                    $params['achievementid'] = $achievement_info->id;
                    $completed_groupids = $DB->get_fieldset_sql($sql, $params);

                    foreach($completed_groupids as $completed_groupid) {
                        $current_users = array_merge($current_users, groups_get_members($completed_groupid));
                    }
                }
                else {
                    $sql = "SELECT DISTINCT(userid)
                        FROM {achievements_log}
                        WHERE achievementid = :achievementid";
                    $params['achievementid'] = $achievement_info->id;
                    $completed_userids = $DB->get_fieldset_sql($sql, $params);

                    foreach($completed_userids as $completed_userid) {
                        $current_users[$completed_userid] = null;
                    }
                }

                if($foreach_first) {
                    $users = $current_users;
                    $foreach_first = false;
                }
                else {
                    $users = array_intersect_key($users, $current_users);
                }
            }
        }

        if(!empty($users)) {
            $users = array_keys($users);
            $where = " AND u.id IN (" . implode(', ', $users) . ") ";
        }

        return array($join, $where, $params);
    }
}
