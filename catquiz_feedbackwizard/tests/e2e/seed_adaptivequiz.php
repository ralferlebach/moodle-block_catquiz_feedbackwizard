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
 * Seeds an adaptive quiz instance using the catquiz cat model.
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
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/lib.php');

global $DB;
$course = $DB->get_record('course', ['shortname' => 'E2E1'], '*', MUST_EXIST);
$scale = $DB->get_record('local_catquiz_catscales', ['name' => 'E2E Reading'], '*', MUST_EXIST);

if ($DB->record_exists('adaptivequiz', ['course' => $course->id, 'name' => 'E2E Placement test'])) {
    echo "adaptivequiz already present\n";
} else {
    $module = $DB->get_record('modules', ['name' => 'adaptivequiz'], '*', MUST_EXIST);
    $data = (object)[
        'course' => $course->id, 'modulename' => 'adaptivequiz', 'module' => $module->id,
        'name' => 'E2E Placement test', 'intro' => 'Seeded', 'introformat' => FORMAT_HTML,
        'attemptfeedback' => '', 'attemptfeedbackformat' => FORMAT_HTML,
        'section' => 1, 'visible' => 1, 'cmidnumber' => '',
        'catmodel' => 'catquiz',
        'startinglevel' => 0, 'stopingcondition' => 0,
        'minimumquestions' => 5, 'maximumquestions' => 20,
        'standarderror' => 5, 'lowestlevel' => 1, 'highestlevel' => 100,
        'grademethod' => 1, 'attempts' => 0, 'showabilitymeasure' => 0,
        'catquiz_catscales' => (int)$scale->id,
        'catquiz_subscalecheckbox_' . $scale->id => 1,
        'catquizcatscales' => (int)$scale->id,
        'catmodel_catquiz_selectcontext' => (int)$scale->contextid,
        'maxquestionsgroup' => ['catquiz_maxquestions' => 20, 'catquiz_minquestions' => 5],
        'catquiz_standarderror_min' => 0.1, 'catquiz_standarderror_max' => 1.0,
        'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
        'completion' => 0, 'completionview' => 0, 'completionexpected' => 0,
    ];
    $moduleinfo = add_moduleinfo($data, $course);
    echo "adaptivequiz cmid={$moduleinfo->coursemodule} instance={$moduleinfo->instance}\n";
}

$aq = $DB->get_record('adaptivequiz', ['course' => $course->id, 'name' => 'E2E Placement test'], '*', MUST_EXIST);
$test = $DB->get_record('local_catquiz_tests', ['componentid' => $aq->id, 'component' => 'mod_adaptivequiz']);
echo $test ? "local_catquiz_tests id={$test->id} courseid={$test->courseid} catscaleid={$test->catscaleid}\n"
           : "NO local_catquiz_tests row created\n";
