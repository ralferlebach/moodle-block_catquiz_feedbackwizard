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
 * Block catquiz_quizsettingwizard is defined here.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Definition of the catquiz_feedbackwizard block.
 *
 * @package    block_catquiz_feedbackwizard
 * @copyright 2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_catquiz_feedbackwizard extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_catquiz_feedbackwizard');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = new stdClass();
            $this->content = 'Test text';
            return $this->content;
        }

        $this->content = new stdClass();

        $imagepath = $this->get_url('pic/cat_wizard_ready.jpg');
        $imagepath = $this->get_url('pic/cat_wizard_notests.jpg');
        $imagepath = $this->get_url('pic/cat_wizard_finished.jpg');

        $this->content->items = [];
        $this->content->items[] = '<a href="https://moodle.org">Moodle Website</a>';
        $this->content->items[] = '<a href="https://docs.moodle.org">Moodle Dokumentation</a>';
        $this->content->items[] = '<a href="https://moodle.org/community">Moodle Community</a>';

        $this->content->icons = [];
        $this->content->icons[] = '<img src="' . $this->get_url('icons/add.svg') . '" 
            style="width:16px; height:16px; vertical-align:middle;" />';
        $this->content->icons[] = '<img src="' . $this->get_url('icons/edit.svg') . '" 
            style="width:16px; height:16px; vertical-align:middle;" />';
        $this->content->icons[] = '<img src="' . $this->get_url('icons/delete.svg') . '" 
            style="width:16px; height:16px; vertical-align:middle;" />';

        $this->content->footer = 'FOOTER';

        if (!empty($this->config->text)) {
            $this->content->text = $this->config->text;
        } else {
            $this->content->text = "Test";
            $this->content->text = '<img src="' . $imagepath . '" alt="Beschreibung des Bildes" style="max-width:100%; height:auto;" />';
            $this->content->text .= '<p>Please define the content text in /blocks/catquiz_quizsettingwizard/block_catquiz_quizsettingwizard.php.</p>';
        }

        // Rendering der Items und Icons
        $this->content->text .= '<ul>';
        foreach ($this->content->items as $index => $item) {
            $icon = isset($this->content->icons[$index]) ? $this->content->icons[$index] : '';
            $this->content->text .= '<li>' . $icon . ' ' . $item . '</li>';
        }
        $this->content->text .= '</ul>';
        
        return $this->content;
    }

    public function _self_test() {

        return true; // Wenn alle Tests bestehen
    }
    
    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_catquiz_feedbackwizard');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Allow multiple instances in a single course?
     *
     * @return bool True if multiple instances are allowed, false otherwise.
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
     public function applicable_formats() {
        return array(
                'admin' => false,
                'site-index' => false,
                'course-view' => true,
                'mod' => false,
                'my' => false,
        );
    }
    
    private function get_url($relativepath) {
        global $CFG;
        return $CFG->wwwroot . '/blocks/catquiz_feedbackwizard/' . $relativepath;
    }
}
