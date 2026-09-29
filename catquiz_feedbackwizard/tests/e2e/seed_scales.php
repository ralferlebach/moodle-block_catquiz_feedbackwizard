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
 * Seeds a course, a teacher and a CAT scale tree.
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
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/testing/generator/lib.php');

global $DB;

// 1. Course.
$course = $DB->get_record('course', ['shortname' => 'E2E1']);
if (!$course) {
    $course = create_course((object)[
        'fullname' => 'E2E CAT course', 'shortname' => 'E2E1',
        'category' => 1, 'format' => 'topics', 'numsections' => 3,
    ]);
}
echo "course id={$course->id}\n";

// 2. Teacher enrolled as editingteacher.
$teacher = $DB->get_record('user', ['username' => 'teacher1']);
if (!$teacher) {
    require_once($CFG->dirroot . '/user/lib.php');
    $teacher = (object)[
        'username' => 'teacher1', 'password' => 'Teacher123!', 'firstname' => 'Tina',
        'lastname' => 'Teacher', 'email' => 'teacher1@example.com',
        'auth' => 'manual', 'confirmed' => 1, 'mnethostid' => $CFG->mnet_localhost_id,
    ];
    $teacher->id = user_create_user($teacher, true, false);
}
$role = $DB->get_record('role', ['shortname' => 'editingteacher']);
$ctx = context_course::instance($course->id);
if (!is_enrolled($ctx, $teacher->id)) {
    $enrol = enrol_get_plugin('manual');
    $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', IGNORE_MULTIPLE);
    $enrol->enrol_user($instance, $teacher->id, $role->id);
}
echo "teacher id={$teacher->id}\n";

// 3. CAT context and scale via the local_catquiz API.
$scale = $DB->get_record('local_catquiz_catscales', ['name' => 'E2E Reading']);
if (!$scale) {
    $structure = new \local_catquiz\data\catscale_structure([
        'name' => 'E2E Reading', 'description' => 'Seeded for the interactive check',
        'parentid' => 0, 'minscalevalue' => -3.0, 'maxscalevalue' => 3.0,
        'contextid' => 0, 'timemodified' => time(), 'timecreated' => time(),
    ]);
    $scaleid = \local_catquiz\data\dataapi::create_catscale($structure);
    $scale = $DB->get_record('local_catquiz_catscales', ['id' => $scaleid]);
}
echo "scale id={$scale->id} contextid={$scale->contextid}\n";

// 4. Subscale so step 3 has something to offer.
$sub = $DB->get_record('local_catquiz_catscales', ['name' => 'E2E Reading - Vocabulary']);
if (!$sub) {
    $substructure = new \local_catquiz\data\catscale_structure([
        'name' => 'E2E Reading - Vocabulary', 'description' => 'Subscale',
        'parentid' => (int)$scale->id, 'minscalevalue' => -3.0, 'maxscalevalue' => 3.0,
        'contextid' => (int)$scale->contextid, 'timemodified' => time(), 'timecreated' => time(),
    ]);
    $subid = \local_catquiz\data\dataapi::create_catscale($substructure);
    $sub = $DB->get_record('local_catquiz_catscales', ['id' => $subid]);
}
echo "subscale id={$sub->id}\n";
