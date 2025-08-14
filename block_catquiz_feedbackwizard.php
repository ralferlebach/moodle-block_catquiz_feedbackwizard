<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Main Block File.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    defined('MOODLE_INTERNAL') || die();

/**
 *
 */
class block_catquiz_feedbackwizard extends block_base {

    /**
     * @return void
     */
    public function init() {
            $this->title = get_string('pluginname', 'block_catquiz_feedbackwizard'); 
        }

    /**
     * @return true[]
     */
    public function applicable_formats() {
            return [
            'site' => true,
            'course-view' => true,
            ];
        }

    /**
     * @return false
     */
    public function instance_allow_multiple() {
            return false;
        }

    /**
     * @return stdClass
     */
    public function get_content() {
        
            global $COURSE, $OUTPUT;
            if ($this->content !== null) {
                return $this->content;
            }
            
            $this->content = new stdClass();
            $context = context_course::instance($COURSE->id ?? SITEID);
            if (!has_capability('block/catquiz_feedbackwizard:use', $context)) {
                $this->content->text = '';
                return $this->content;
            }
            
            $data = (object)[
            'buttonlabel' => get_string('openwizard', 'block_catquiz_feedbackwizard'),
            'courseid' => (int)($COURSE->id ?? SITEID),
            ];
            
            $this->content->text = $OUTPUT->render_from_template('block_catquiz_feedbackwizard/block', $data);
            
            // Load our AMD to wire up the modal form.
            $this->page->requires->js_call_amd('block_catquiz_feedbackwizard/main', 'init');
            
            return $this->content;
        }
    }