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
 * Plugin strings are defined here.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    string
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']               = 'CAT Feedback Wizard';
$string['status:ready-header']      = 'CAT quiz feedbacks all set up';
$string['status:ready-hint']        = 'All feedbacks for the selected CAT quizzes have been successfully set up. 
    You may now delete this block or hide it in order to make changes later on.';
$string['status:notests-header']    = 'There are no CAT quizzes in this course';
$string['status:notests-hint']      = 'Please add at least one adaptive quiz to this course with the catquiz model to start with.';
$string['status:ready-header']      = 'There are CAT quizzes in this course';
$string['status:ready-hint']        = 'Let\'s start with setting up fantastic feedbacks for them!';
