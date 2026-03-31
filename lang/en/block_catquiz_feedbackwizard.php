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

$string['action:course'] = 'Course enrolment';
$string['action:group'] = 'Group enrolment';
$string['action:text'] = 'Text feedback';
$string['capability:use'] = 'Use multistage CAT wizard';
$string['catquiz_feedbackwizard:addinstance'] = 'Add a new CATQuiz wizard block';
$string['catquiz_feedbackwizard:use'] = 'Use the CATQuiz wizard';
$string['clonescope:conditions'] = 'Reuse only conditions and limits';
$string['clonescope:full'] = 'Reuse the full configuration';
$string['clonescope:structure'] = 'Reuse only scales and subscales';
$string['error:feedbackactioncoursetargetrequired'] = 'Enter at least one target course identifier for this range.';
$string['error:feedbackactiongrouptargetrequired'] = 'Enter at least one target group identifier for this range.';
$string['error:feedbackinvalidrange'] = 'Each range must end above its lower boundary.';
$string['error:feedbackrangegap'] = 'Neighbouring feedback ranges must meet without gaps.';
$string['error:invalidstep'] = 'Invalid wizard step.';
$string['error:matchingcategoryrequired'] = 'Choose a course category for matching.';
$string['error:matchingcsvinvalid'] = 'Enter at least one valid CSV matching rule.';
$string['error:matchingpatternrequired'] = 'Enter a matching pattern.';
$string['error:matchingregexinvalid'] = 'The matching regular expression is invalid.';
$string['error:matchingtargetrequired'] = 'Enter a matching target value.';
$string['error:minlargerthanmax'] = 'The minimum question count cannot be greater than the maximum question count.';
$string['error:permissiondenied'] = 'You do not have permission to use this wizard.';
$string['error:reportingsubscalesrequired'] = 'Choose at least one subscale for this reporting strategy.';
$string['error:sameclonesource'] = 'The source test must be different from the selected target test.';
$string['field:clonescope'] = 'Copy scope';
$string['field:completionenabled'] = 'Enable activity completion';
$string['field:feedbackactioncourseenabled'] = 'Range {$a} course enrolment action';
$string['field:feedbackactioncoursetarget'] = 'Range {$a} course target identifiers';
$string['field:feedbackactiongroupenabled'] = 'Range {$a} group enrolment action';
$string['field:feedbackactiongrouptarget'] = 'Range {$a} group target identifiers';
$string['field:feedbackactionsummary'] = 'Range {$a} actions';
$string['field:feedbacklabel'] = 'Range {$a} label';
$string['field:feedbacklower'] = 'Range {$a} lower boundary';
$string['field:feedbackrangecount'] = 'Number of fixed ranges';
$string['field:feedbackrangeheader'] = 'Range {$a}';
$string['field:feedbacktemplateformat'] = 'Range {$a} text template format';
$string['field:feedbacktext'] = 'Range {$a} feedback text';
$string['field:feedbackupper'] = 'Range {$a} upper boundary';
$string['field:mainscaleid'] = 'Main scale';
$string['field:matchingcategoryid'] = 'Matching category';
$string['field:matchingcoursefield'] = 'Course field';
$string['field:matchingcsv'] = 'CSV matching rules';
$string['field:matchingmode'] = 'Matching mode';
$string['field:matchingoperator'] = 'Matching operator';
$string['field:matchingpattern'] = 'Match pattern';
$string['field:matchingsummary'] = 'Matching configuration';
$string['field:matchingtargettype'] = 'Matching target type';
$string['field:matchingtargetvalue'] = 'Matching target value';
$string['field:minquestioncount'] = 'Minimum question count';
$string['field:precisionmode'] = 'Precision';
$string['field:questioncount'] = 'Maximum question count';
$string['field:questioncountpersubscale'] = 'Maximum question count per subscale';
$string['field:readiness'] = 'Readiness';
$string['field:reportingstrategy'] = 'Reporting strategy';
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
$string['message:feedbacktokeninfo'] = 'You can already use placeholders such as '
    . '{{result.ranklabel}}, {{result.scalename}}, {{test.name}}, and '
    . '{{course.fullname}} in feedback texts.';
$string['message:matchingcsvtemplate'] = 'CSV format: coursefield, operator, pattern, targettype, targetvalue';
$string['message:nosubscalesavailable'] = 'No subscales are available for the currently selected main scale yet.';
$string['message:notestsavailable'] = 'No CAT tests were found in this course.';
$string['message:reviewsummary'] = 'Review the normalised CAT settings, feedback ranges, '
    . 'and reporting setup before writing them back to the selected test.';
$string['matchingcoursefield:fullname'] = 'Course full name';
$string['matchingcoursefield:idnumber'] = 'Course ID number';
$string['matchingcoursefield:shortname'] = 'Course short name';
$string['matchingmode:csv'] = 'CSV matching rules';
$string['matchingmode:none'] = 'No matching';
$string['matchingmode:rule'] = 'One matching rule';
$string['matchingoperator:contains'] = 'Contains';
$string['matchingoperator:equals'] = 'Equals';
$string['matchingoperator:regex'] = 'Regular expression';
$string['matchingoperator:startswith'] = 'Starts with';
$string['matchingtargettype:catscale'] = 'CAT scale';
$string['matchingtargettype:course'] = 'Course';
$string['matchingtargettype:group'] = 'Group';
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
$string['readiness:incomplete'] = 'Incomplete';
$string['readiness:ready'] = 'Ready to save';
$string['readiness:warnings'] = 'Ready with warnings';
$string['reporting:main_and_subscales_separate'] = 'Report main scale and selected subscales separately';
$string['reporting:main_only'] = 'Report only the main scale';
$string['reporting:subscales_only'] = 'Report only selected subscales';
$string['reporting:subscales_with_parents_without_main'] = 'Report selected subscales and '
    . 'their direct parent scales without the main scale';
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
$string['step03:description'] = 'Adjust scale selection and core CAT test settings before moving on to feedback configuration.';
$string['step03:title'] = 'Edit test settings';
$string['step04:description'] = 'Define fixed feedback ranges, choose the reporting '
    . 'strategy, and enter template-ready text for each range.';
$string['step04:title'] = 'Configure feedback ranges';
$string['step05:description'] = 'Configure course-to-target matching rules or paste CSV matching definitions.';
$string['step05:title'] = 'Configure matching';
$string['step06:description'] = 'Review the normalised state and write the settings back to the selected CAT test.';
$string['step06:title'] = 'Confirm and save';
$string['submissionsuccess'] = 'The CAT test configuration has been updated successfully.';
$string['submitfinal'] = 'Finish';
$string['submitnext'] = 'Continue';
$string['submitprevious'] = 'Back';
$string['templateformat:mustache'] = 'Mustache template';
$string['templateformat:plain'] = 'Plain text';
$string['warning:feedbackrangesneedreview'] = 'The feedback range boundaries should be checked before saving.';
$string['warning:feedbacktextmissing'] = 'At least one feedback text is still empty.';
$string['warning:highprecisionlowquestions'] = 'High precision usually needs a higher maximum question count.';
$string['warning:matchingconfigincomplete'] = 'The configured matching setup is incomplete and should be reviewed.';
$string['warning:nosubscalesselected'] = 'No subscales have been selected yet.';
$string['warning:reportingsubscaleswithoutselection'] = 'The chosen reporting strategy expects selected subscales.';
$string['warning:shorttimelimit'] = 'The configured time limit is very short for a CAT test.';
