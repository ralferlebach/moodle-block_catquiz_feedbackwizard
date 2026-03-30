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
 * Draft persistent class for CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 */

namespace block_catquiz_feedbackwizard\persistent;

use core\persistent;

/**
 * Persistent class for managing wizard draft data.
 *
 * @package     block_catquiz_feedbackwizard
 */
class draft extends persistent {
    /** @var string Database table for draft data. */
    public const TABLE = 'block_catquiz_feedbackwizard';

    /**
     * Define the properties for this persistent class.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'userid' => [
                'type' => PARAM_INT,
            ],
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'testid' => [
                'type' => PARAM_INT,
                'default' => 0,
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
                'type' => PARAM_RAW,
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
