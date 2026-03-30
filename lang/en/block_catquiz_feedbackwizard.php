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
 * Language File for block_catquiz_feedbackwizard (English).
 *
 * @package     block_catquiz_feedbackwizard
 */
$string['pluginname'] = 'CATQuiz Wizard';
$string['openwizard'] = 'Open CATQuiz wizard';
$string['privacy:metadata:block_catquiz_feedbackwizard'] = 'Stores multistage wizard drafts and submissions.';
$string['privacy:metadata:block_catquiz_feedbackwizard:userid'] = 'The user ID who created the draft/submission.';
$string['privacy:metadata:block_catquiz_feedbackwizard:courseid'] = 'The course ID where the submission was created.';
$string['privacy:metadata:block_catquiz_feedbackwizard:testid'] = 'The local_catquiz_tests ID selected in the wizard.';
$string['privacy:metadata:block_catquiz_feedbackwizard:datajson'] = 'The partial wizard data stored as JSON.';
$string['capability:use'] = 'Use CATQuiz wizard';

$string['step01:title'] = '10 Select CAT test';
$string['step01:description'] = 'Choose the CAT test to configure. The wizard references local_catquiz_tests.id as testid.';
$string['step02:title'] = '20 Choose configuration mode';
$string['step03:title'] = '23 Choose scenario';
$string['step04:title'] = '30 Select main scale and subscales';
$string['step05:title'] = '40 Define conditions';
$string['step06:title'] = '100 Review and save draft';

$string['field:testid'] = 'CAT test';
$string['field:wizardmode'] = 'Mode';
$string['field:sourcetestid'] = 'Source test';
$string['field:importjson'] = 'Import external configuration instead of copying from another local test';
$string['field:scenario'] = 'Scenario';
$string['field:scenario_notes'] = 'Scenario notes';
$string['field:selectedtest'] = 'Selected CAT test';
$string['field:mainscaleid'] = 'Main scale ID';
$string['field:subscaleids'] = 'Subscale IDs';
$string['field:subscaleids_help'] =
    'Enter subscale IDs as a comma-separated list. ' .
    'A dedicated picker can replace this in a later patch.';
$string['field:goal'] = 'Test goal';
$string['field:timelimitminutes'] = 'Time limit in minutes';
$string['field:questioncount'] = 'Question count';
$string['field:precisionmode'] = 'Precision';
$string['label:scaleid'] = 'Scale {$a}';

$string['mode:new'] = 'Set up the selected CAT test from scratch';
$string['mode:clone'] = 'Copy settings from another CAT test';
$string['mode:edit'] = 'Edit the existing settings of the selected CAT test';
$string['mode:import'] = 'Prepare an import/export based setup';

$string['scenario:learning_diagnostics'] = 'Learning diagnostics (Digi-Tutor)';
$string['scenario:placement_test'] = 'Placement test at course entry';
$string['scenario:checkup_fulladaptive'] = 'Progress test, fully adaptive (Check-Up)';
$string['scenario:final_partialadaptive'] = 'Final test across all topics, partially adaptive';
$string['scenario:strength_profile'] = 'Identify personal strengths';
$string['scenario:other'] = 'Other (no preset support)';

$string['goal:orientation'] = 'Orientation';
$string['goal:placement'] = 'Placement';
$string['goal:progress'] = 'Progress diagnostics';
$string['goal:completion'] = 'Completion or exit test';
$string['goal:strengths'] = 'Strength profile';

$string['precision:low'] = 'Low';
$string['precision:medium'] = 'Medium';
$string['precision:high'] = 'High';

$string['readiness:notconfigured'] = 'Not configured';
$string['readiness:partial'] = 'Partially configured';
$string['readiness:configured'] = 'Configured';

$string['review:courseid'] = 'Course ID: {$a}';
$string['review:test'] = 'CAT test: {$a}';
$string['review:writeback'] =
    'Saving this step writes the current wizard state back into local_catquiz_tests.json.';

$string['submitprevious'] = 'Back';
$string['submitnext'] = 'Continue';
$string['submitfinal'] = 'Apply configuration';
$string['savedprogress'] = 'Wizard progress saved.';
$string['submissionsuccess'] = 'The CAT test configuration was written to local_catquiz_tests.';
$string['error:permissiondenied'] = 'You do not have permission to use this wizard.';
$string['error:invalidstep'] = 'Invalid wizard step.';
$string['error:nonnegative'] = 'Please enter a non-negative value.';
$string['error:questioncount'] = 'Question count must be at least 1.';

$string['notestsfound'] = 'No CAT tests were found for this course in local_catquiz_tests.';
$string['notavailable'] = 'Not available';
$string['unnamedtest'] = 'Unnamed test';
$string['link:edittests'] = 'Open test administration';

$string['catquiz_feedbackwizard:addinstance'] = 'Add a new CATQuiz wizard block';
$string['catquiz_feedbackwizard:use'] = 'Use the CATQuiz wizard';

