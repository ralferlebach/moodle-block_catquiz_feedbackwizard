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
 * Language strings for block_catquiz_feedbackwizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['capability:use'] = 'Use multistage CAT wizard';
$string['catquiz_feedbackwizard:addinstance'] = 'Add a new CATQuiz wizard block';
$string['catquiz_feedbackwizard:use'] = 'Use the CATQuiz wizard';
$string['error:invalidstep'] = 'Invalid wizard step.';
$string['error:minlargerthanmax'] = 'The minimum question count cannot be greater than the maximum question count.';
$string['error:permissiondenied'] = 'You do not have permission to use this wizard.';
$string['field:completionenabled'] = 'Enable activity completion';
$string['field:mainscaleid'] = 'Main scale';
$string['field:minquestioncount'] = 'Minimum question count';
$string['field:precisionmode'] = 'Precision';
$string['field:questioncount'] = 'Maximum question count';
$string['field:questioncountpersubscale'] = 'Maximum question count per subscale';
$string['field:reviewsummary'] = 'Review';
$string['field:reviewwarning'] = 'Warning';
$string['field:scenario'] = 'Scenario';
$string['field:selectedtest'] = 'CAT test';
$string['field:sourcetestid'] = 'Source test';
$string['field:subscaleids'] = 'Subscales';
$string['field:testgoal'] = 'Test goal';
$string['field:timelimitenabled'] = 'Enable a time limit';
$string['field:timelimitminutes'] = 'Time limit in minutes';
$string['field:wizardmode'] = 'Wizard mode';
$string['goal:diagnostics'] = 'Learning diagnostics';
$string['goal:final'] = 'Final assessment';
$string['goal:orientation'] = 'Orientation';
$string['goal:other'] = 'Other';
$string['goal:placement'] = 'Placement';
$string['goal:strength'] = 'Strength profile';
$string['message:nosubscalesavailable'] = 'No subscales are available for the currently selected main scale yet.';
$string['message:notestsavailable'] = 'No CAT tests were found in this course.';
$string['message:reviewsummary'] = 'Review the imported CAT settings and write them back to the selected test.';
$string['mode:clone'] = 'Copy configuration from another CAT test';
$string['mode:edit'] = 'Edit the selected CAT test';
$string['mode:scenario'] = 'Start from a predefined scenario';
$string['openwizard'] = 'Start the CATQuiz wizard';
$string['pluginname'] = 'CATQuiz Wizard';
$string['precision:high'] = 'High';
$string['precision:low'] = 'Low';
$string['precision:medium'] = 'Medium';
$string['privacy:metadata:block_catquiz_feedbackwizard'] = 'Stores wizard drafts and submissions.';
$string['privacy:metadata:block_catquiz_feedbackwizard:courseid'] = 'The course ID where the draft was created.';
$string['privacy:metadata:block_catquiz_feedbackwizard:datajson'] = 'The partial wizard data stored as JSON.';
$string['privacy:metadata:block_catquiz_feedbackwizard:testid'] = 'The selected CAT test ID.';
$string['privacy:metadata:block_catquiz_feedbackwizard:userid'] = 'The user ID who created the draft or submission.';
$string['savedprogress'] = 'Progress saved.';
$string['scenario:checkup'] = 'Learning progress test, fully adaptive';
$string['scenario:final'] = 'Final test across all topics, partially adaptive';
$string['scenario:learning_diagnostics'] = 'Learning diagnostics';
$string['scenario:other'] = 'Other';
$string['scenario:placement'] = 'Placement test at course start';
$string['scenario:strength'] = 'Identify personal strengths';
$string['step01:description'] = 'Select the CAT test that should be configured or reviewed.';
$string['step01:title'] = 'Choose CAT test';
$string['step02:description'] = 'Decide whether you want to edit the current test, clone another test, or start from a scenario.';
$string['step02:title'] = 'Choose setup mode';
$string['step03:description'] = 'Adjust scale selection and core CAT test settings before saving them back to local_catquiz.';
$string['step03:title'] = 'Edit test settings';
$string['step04:description'] = 'Review the normalised state and write the settings back to the selected CAT test.';
$string['step04:title'] = 'Confirm and save';
$string['submissionsuccess'] = 'The CAT test configuration has been updated successfully.';
$string['submitfinal'] = 'Finish';
$string['submitnext'] = 'Continue';
$string['submitprevious'] = 'Back';
$string['warning:highprecisionlowquestions'] = 'High precision usually needs a higher maximum question count.';
$string['warning:nosubscalesselected'] = 'No subscales have been selected yet.';
$string['warning:shorttimelimit'] = 'The configured time limit is very short for a CAT test.';
