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
 * Plugin version and other meta-data are defined here.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_catquiz_feedbackwizard\persistent;

use core\persistent;

/**
 *
 */
class draft extends persistent {

    /**
     *
     */
    const TABLE = 'block_catquiz_feedbackwizard';

    /**
     * @return array[]
     */
    protected static function define_properties() {
        return [
        'userid' => [
            'type' => PARAM_INT,
            ],
        'courseid' => [
            'type' => PARAM_INT,
            ],
        'status' => [
            'type' => PARAM_ALPHA,
            'default' => 'draft',
            ],
        'step' => [
            'type' => PARAM_INT,
            'default' => 1,
            ],
        'datajson' => [
                'type' => PARAM_RAW, // JSON string.
                'null' => NULL_ALLOWED,
                ],
        'timecreated' => [
            'type' => PARAM_INT,
            'default' => 0,
            ],
        'timemodified' => [
            'type' => PARAM_INT,
            'default' => 0,
            ],
        ];
    }
}
