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
 * Adds the wizard block to the seeded course.
 *
 * Development helper for the interactive Playwright walkthrough. Not part of
 * the automated test suites; see tests/e2e/README.md.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
global $DB;
$course = $DB->get_record('course', ['shortname' => 'E2E1'], '*', MUST_EXIST);
$ctx = context_course::instance($course->id);
$teacher = $DB->get_record('user', ['username' => 'teacher1'], '*', MUST_EXIST);
\core\session\manager::set_user($teacher);

$DB->delete_records('block_instances', ['blockname' => 'catquiz_feedbackwizard', 'parentcontextid' => $ctx->id]);

$page = new moodle_page();
$page->set_context($ctx);
$page->set_course($course);
$page->set_pagelayout('course');
$page->set_pagetype('course-view-' . $course->format);
$page->blocks->add_region('side-pre');
$page->blocks->load_blocks();
$page->blocks->add_block_at_end_of_default_region('catquiz_feedbackwizard');

purge_all_caches();
foreach ($DB->get_records('block_instances', ['parentcontextid' => $ctx->id]) as $bi) {
    echo "instance {$bi->id}: {$bi->blockname} region={$bi->defaultregion} pagetype={$bi->pagetypepattern}\n";
}
