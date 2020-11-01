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
 * Feedback block.
 *
 * @package    block_feedback
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

include_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filelib.php');
include_once($CFG->dirroot . '/blocks/current_online_classes/lib.php');


class block_current_online_classes extends block_list {
    
    function init() {
        $this->title = get_string('current_online_classes', 'block_current_online_classes');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $OUTPUT, $DB;
        
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = "";    
        
        $icon = $OUTPUT->pix_icon('i/course', get_string('course'));

        $adminseesall = true;
        if (isset($CFG->block_course_list_adminview)) {
           if ( $CFG->block_course_list_adminview == 'own'){
               $adminseesall = false;
           }
        }

        $allcourselink =
            (has_capability('moodle/course:update', context_system::instance())
            || empty($CFG->block_course_list_hideallcourseslink)) &&
            core_course_category::user_top();

        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {    // Just print My Courses
            if ($courses = enrol_get_my_courses()) {
                foreach ($courses as $course) {
                    $attendaceId = $DB->get_record('attendance', ['course' => $course->id])->id;
                    $startTimes = $DB->get_records('attendance_sessions', ['attendanceid' => $attendaceId]);
                    foreach($startTimes as $startTime) {
                        if($startTime->sessdate < (time() + 300) && time() < ($startTime->sessdate + $startTime->duration)) {
                            $bbbId = $DB->get_record('modules', ['name' => 'bigbluebuttonbn'])->id;
                            $activityId = $DB->get_record('course_modules', ['course' => $course->id, 'module' => $bbbId])->id;
                            if($activityId == null) {
                                $coursecontext = context_course::instance($course->id);
                                $url = get_course_image($coursecontext->id);
                                $this->content->items[]="<div class='card dashboard-card' style='width: 300px; position: relative; display: flex;'><img src='" . $url . "' style='width: 300px;'></div>";
                                $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                                $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                                    "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".$icon.format_string(get_course_display_name_for_list($course)). "</a>";
                            } else {
                                $coursecontext = context_course::instance($course->id);
                                $url = get_course_image($coursecontext->id);
                                $this->content->items[]="<div class='card dashboard-card' style='width: 300px; position: relative; display: flex;'><img src='" . $url . "' style='width: 300px;'></div>";
                                $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                                $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                                "href=\"$CFG->wwwroot/mod/bigbluebuttonbn/view.php?id=$activityId\">".$icon.format_string(get_course_display_name_for_list($course)). "</a>";
                            }
                        }    
                    }
                }
                $this->title = get_string('current_online_classes', 'block_current_online_classes');
            }
            $this->get_remote_courses();
            if ($this->content->items) { // make sure we don't return an empty list
                return $this->content;
            }
        }
        $this->content->footer = get_string('no_class', 'block_current_online_classes');
        return $this->content;
    }

    function get_remote_courses() {
        global $CFG, $USER, $OUTPUT;

        if (!is_enabled_auth('mnet')) {
            // no need to query anything remote related
            return;
        }

        $icon = $OUTPUT->pix_icon('i/mnethost', get_string('host', 'mnet'));

        // shortcut - the rest is only for logged in users!
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses','mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
                $this->content->items[]="<a title=\"" . format_string($course->shortname, true) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$course->hostid}&amp;wantsurl=/course/view.php?id={$course->remoteid}\">"
                    .$icon. format_string(get_course_display_name_for_list($course)) . "</a>";
            }
            // if we listed courses, we are done
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotehosts', 'mnet');
            $this->content->icons[] = '';
            foreach($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'].get_string('courseson','mnet').'<a title="'.$somehost['name'].'" href="'.$somehost['url'].'">'.$icon.$somehost['name'].'</a>';
            }
            // if we listed hosts, done
            return true;
        }

        return false;
    }
}