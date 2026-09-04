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
$string['action:downloadpattern'] = 'Download settings pattern (JSON)';
$string['action:group'] = 'Group enrolment';
$string['action:text'] = 'Text feedback';
$string['capability:use'] = 'Use multistage CAT wizard';
$string['capability:writeconfig'] = 'Write CAT test configuration through the Settings Wizard';
$string['catquiz_feedbackwizard:addinstance'] = 'Add a new CATQuiz wizard block';
$string['catquiz_feedbackwizard:use'] = 'Use the CATQuiz wizard';
$string['catquiz_feedbackwizard:writeconfig'] = 'Write CAT test configuration through the Settings Wizard';
$string['clonescope:conditions'] = 'Reuse only conditions and limits';
$string['clonescope:full'] = 'Reuse the full configuration';
$string['clonescope:structure'] = 'Reuse only scales and subscales';
$string['error:feedbackactioncoursetargetrequired'] = 'Enter at least one target course identifier for this range.';
$string['error:feedbackactiongrouptargetrequired'] = 'Enter at least one target group identifier for this range.';
$string['error:feedbackinvalidrange'] = 'Each range must end above its lower boundary.';
$string['error:feedbackrangegap'] = 'Neighbouring feedback ranges must meet without gaps.';
$string['error:invalidstep'] = 'Invalid wizard step.';
$string['error:localcatquizunavailable'] = 'The local_catquiz write interface is not available. Check that local_catquiz is installed and up to date.';
$string['error:matchingcategoryrequired'] = 'Choose a course category for matching.';
$string['error:matchingcsvinvalid'] = 'Enter at least one valid CSV matching rule.';
$string['error:matchingpatternrequired'] = 'Enter a matching pattern.';
$string['error:matchingregexinvalid'] = 'The matching regular expression is invalid.';
$string['error:matchingtargetrequired'] = 'Enter a matching target value.';
$string['error:minlargerthanmax'] = 'The minimum question count cannot be greater than the maximum question count.';
$string['error:patternexportdenied'] = 'You may only export your own wizard draft in this course.';
$string['error:patternfilerequired'] = 'Choose a settings pattern file to import.';
$string['error:patterninvalidjson'] = 'The uploaded file is not valid JSON.';
$string['error:patternmissingsection'] = 'The settings pattern is missing the "{$a}" section.';
$string['error:patternunsupportedversion'] = 'Settings pattern version {$a} is not supported by this plugin version.';
$string['error:patternwrongformat'] = 'The uploaded file is not a CATQuiz settings pattern.';
$string['error:permissiondenied'] = 'You do not have permission to use this wizard.';
$string['error:reportingsubscalesrequired'] = 'Choose at least one subscale for this reporting strategy.';
$string['error:sameclonesource'] = 'The source test must be different from the selected target test.';
$string['error:testnotincourse'] = 'The selected CAT test does not belong to this course.';
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
$string['field:patternexport'] = 'Settings pattern';
$string['field:patternfile'] = 'Settings pattern file';
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
$string['message:courseprovisioningdisabled'] = 'Automatic course creation is disabled by the site administrator, so course follow-up actions are not offered.';
$string['message:feedbacktokeninfo'] = 'You can already use placeholders such as '
    . '{{result.ranklabel}}, {{result.scalename}}, {{test.name}}, and '
    . '{{course.fullname}} in feedback texts.';
$string['message:groupautocreatedisabled'] = 'Automatic group creation is disabled by the site administrator, so group follow-up actions are not offered.';
$string['message:matchingcsvtemplate'] = 'CSV format: coursefield, operator, pattern, targettype, targetvalue';
$string['message:nosubscalesavailable'] = 'No subscales are available for the currently selected main scale yet.';
$string['message:notestsavailable'] = 'No CAT tests were found in this course.';
$string['message:patternimportinfo'] = 'Imported values are used as defaults. Scale references are checked against this site and dropped when they do not exist here.';
$string['message:reviewsummary'] = 'Review the normalised CAT settings, feedback ranges, '
    . 'and reporting setup before writing them back to the selected test.';
$string['mode:clone'] = 'Copy configuration from another CAT test';
$string['mode:edit'] = 'Edit the selected CAT test';
$string['mode:import'] = 'Import a settings pattern';
$string['mode:scenario'] = 'Start from a predefined scenario';
$string['openwizard'] = 'Start the CATQuiz wizard';
$string['pluginname'] = 'CATQuiz Wizard';
$string['precision:high'] = 'High';
$string['precision:low'] = 'Low';
$string['precision:medium'] = 'Medium';
$string['privacy:metadata:block_catquiz_feedbackwizard'] = 'Stores wizard drafts and submissions.';
$string['privacy:metadata:block_catquiz_feedbackwizard:courseid'] = 'The course ID where the draft was created.';
$string['privacy:metadata:block_catquiz_feedbackwizard:datajson'] = 'The partial wizard data stored as JSON.';
$string['privacy:metadata:block_catquiz_feedbackwizard:status'] = 'The processing status of the wizard draft.';
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
$string['settings:ai_feedback_systemprompt'] = 'AI system prompt';
$string['settings:ai_feedback_systemprompt_desc'] = 'System prompt used when feedback texts are sent for language refinement. Leave empty to use the built-in default. Do not put personal data into this prompt.';
$string['settings:allowed_target_categories'] = 'Allowed target categories';
$string['settings:allowed_target_categories_desc'] = 'Comma separated list of course category ids that may be used as provisioning targets. Leave empty to allow every category.';
$string['settings:dataretention'] = 'Data retention';
$string['settings:dataretention_desc'] = 'The Settings Wizard keeps only short-lived working data. Unfinished drafts are removed automatically once they exceed the lifetime configured here.';
$string['settings:draft_ttl_hours'] = 'Draft lifetime (hours)';
$string['settings:draft_ttl_hours_desc'] = 'Number of hours an unfinished wizard draft is kept before it is deleted automatically.';
$string['settings:enable_ai_feedback_refinement'] = 'Enable AI refinement of feedback texts';
$string['settings:enable_ai_feedback_refinement_desc'] = 'Allows teachers to send feedback texts to the Moodle AI subsystem for language refinement. Disabled by default.';
$string['settings:enable_courseprovisioning'] = 'Enable course provisioning';
$string['settings:enable_courseprovisioning_desc'] = 'Allows the wizard to create missing target courses or to start a course request. Disabled by default.';
$string['settings:enable_groupautocreate'] = 'Enable group creation';
$string['settings:enable_groupautocreate_desc'] = 'Allows the wizard to create missing target groups in target courses. Disabled by default.';
$string['settings:optionalfeatures'] = 'Optional features';
$string['settings:optionalfeatures_desc'] = 'These features change courses, groups or feedback texts beyond the CAT test itself. All of them are switched off by default and have to be enabled deliberately.';
$string['settings:pattern_export_include_feedback_texts'] = 'Include feedback texts in exports';
$string['settings:pattern_export_include_feedback_texts_desc'] = 'If enabled, exported settings patterns contain the feedback texts themselves and not only the range structure.';
$string['settings:pattern_import_maxfilesize'] = 'Maximum pattern file size (bytes)';
$string['settings:pattern_import_maxfilesize_desc'] = 'Largest settings pattern file accepted by the import step.';
$string['settings:patterns'] = 'Settings patterns';
$string['settings:patterns_desc'] = 'Options for importing and exporting reusable settings patterns.';
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
$string['task:cleanupdrafts'] = 'Delete expired CATQuiz Settings Wizard drafts';
$string['templateformat:mustache'] = 'Mustache template';
$string['templateformat:plain'] = 'Plain text';
$string['warning:feedbackrangesneedreview'] = 'The feedback range boundaries should be checked before saving.';
$string['warning:feedbacktextmissing'] = 'At least one feedback text is still empty.';
$string['warning:highprecisionlowquestions'] = 'High precision usually needs a higher maximum question count.';
$string['warning:matchingconfigincomplete'] = 'The configured matching setup is incomplete and should be reviewed.';
$string['warning:nosubscalesselected'] = 'No subscales have been selected yet.';
$string['warning:patterncategorynotallowed'] = 'The target category of the imported pattern is not allowed on this site and was removed.';
$string['warning:patternscalemissing'] = 'The scale "{$a}" from the imported pattern does not exist on this site and was skipped.';
$string['warning:patternwithouttexts'] = 'The imported pattern was exported without feedback texts, so the texts have to be entered again.';
$string['warning:reportingsubscaleswithoutselection'] = 'The chosen reporting strategy expects selected subscales.';
$string['warning:shorttimelimit'] = 'The configured time limit is very short for a CAT test.';
