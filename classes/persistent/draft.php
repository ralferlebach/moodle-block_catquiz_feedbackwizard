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
 * Draft persistent class for catquiz feedback wizard.
 *
 * This file contains the persistent class that handles database operations
 * for draft feedback wizard entries, allowing users to save progress
 * across multiple form steps.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\persistent;

use core\persistent;

/**
 * Persistent class for managing draft feedback wizard data.
 *
 * This class extends Moodle's persistent base class to provide
 * database operations for storing and retrieving draft feedback
 * wizard entries during the multi-step form process.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class draft extends persistent {

    /**
     * Database table name for storing draft data.
     *
     * @var string TABLE The name of the database table
     */
    const TABLE = 'block_catquiz_feedbackwizard';

    /**
     * Define the properties for this persistent class.
     *
     * Returns an array defining all database fields and their validation
     * rules for the draft feedback wizard entries.
     *
     * @return array Array of property definitions with validation rules
     */
    protected static function define_properties() {
        return [
            /**
             * User ID of the draft owner.
             */
            'userid' => [
                'type' => PARAM_INT,
            ],

            /**
             * Course ID where the draft was created.
             */
            'courseid' => [
                'type' => PARAM_INT,
            ],

            /**
             * Status of the draft entry (draft, submitted, etc.).
             */
            'status' => [
                'type' => PARAM_ALPHA,
                'default' => 'draft',
            ],

            /**
             * Current step in the wizard process.
             */
            'step' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],

            /**
             * JSON string containing the form data.
             */
            'datajson' => [
                'type' => PARAM_RAW, // JSON string.
                'null' => NULL_ALLOWED,
            ],

            /**
             * Timestamp when the draft was created.
             */
            'timecreated' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],

            /**
             * Timestamp when the draft was last modified.
             */
            'timemodified' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }
}
