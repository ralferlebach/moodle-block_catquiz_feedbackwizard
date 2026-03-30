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
 * catquiz_feedbackwizard block class.
 *
 * @package    block_catquiz_feedbackwizard
 * @copyright  2025 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_catquiz_feedbackwizard extends block_base {
    /**
     * Initialise the block.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_catquiz_feedbackwizard');
    }

    /**
     * This block has global config.
     *
     * @return bool
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Instance of block can be added to all pages.
     *
     * @return array
     */
    public function applicable_formats(): array {
        return [
            'site-index' => true,
            'course-view' => true,
            'my' => false,
        ];
    }

    /**
     * The block has no special configuration.
     *
     * @return bool
     */
    public function instance_allow_config(): bool {
        return false;
    }

    /**
     * This block can be added multiple times.
     *
     * @return bool
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Gets the content for this block.
     *
     * @return stdClass
     */
    public function get_content(): stdClass {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!has_capability('block/catquiz_feedbackwizard:use', $this->context)) {
            return $this->content;
        }

        $PAGE->requires->js_call_amd(
            'block_catquiz_feedbackwizard/main',
            'init',
            [[
                'maxSteps' => \block_catquiz_feedbackwizard\form\wizard::MAXSTEPS,
            ]]
        );

        $courseid = 0;
        if (!empty($PAGE->course->id)) {
            $courseid = (int)$PAGE->course->id;
        }

        $data = [
            'courseid' => $courseid,
            'buttontext' => get_string('openwizard', 'block_catquiz_feedbackwizard'),
        ];

        $this->content->text = $this->render_from_template('block_catquiz_feedbackwizard/block', $data);

        return $this->content;
    }
}
