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
 * Capability definitions for the catquiz feedback wizard block.
 *
 * This file defines the capabilities (permissions) that control access
 * to various features of the catquiz feedback wizard block.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Array of capabilities for the catquiz feedback wizard block.
 *
 * This array defines all the capabilities (permissions) available for this block,
 * including who can add instances and who can use the wizard functionality.
 *
 * @var array $capabilities Array of capability definitions
 */
$capabilities = [
    /**
     * Capability to add an instance of this block to course pages.
     *
     * This capability controls who can add the catquiz feedback wizard block
     * to course pages. It inherits permissions from the standard block management
     * capability.
     */
    'block/catquiz_feedbackwizard:addinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
    ],

    /**
     * Capability to use the catquiz feedback wizard functionality.
     *
     * This capability controls who can access and use the feedback wizard
     * features provided by this block. It has a personal risk level as it
     * may involve handling personal feedback data.
     */
    'block/catquiz_feedbackwizard:use' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
    ],
];
