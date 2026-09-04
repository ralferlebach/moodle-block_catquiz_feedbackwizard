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
 * Dynamic wizard form for CATQuiz configuration.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\form;

use block_catquiz_feedbackwizard\catquiz_data;
use block_catquiz_feedbackwizard\local\service\feature_settings_service;
use block_catquiz_feedbackwizard\local\service\matching_config_service;
use block_catquiz_feedbackwizard\local\service\pattern_export_service;
use block_catquiz_feedbackwizard\local\service\pattern_import_service;
use block_catquiz_feedbackwizard\local\service\scenario_preset_service;
use block_catquiz_feedbackwizard\local\service\test_config_normalizer;
use block_catquiz_feedbackwizard\local\service\test_config_writer;
use block_catquiz_feedbackwizard\persistent\draft as draft_persistent;
use context_course;
use core_form\dynamic_form;
use html_writer;
use moodle_url;

/**
 * Multi-step wizard form for CATQuiz setup.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wizard extends dynamic_form {
    /** @var int Number of wizard steps. */
    const MAXSTEPS = 6;

    /** @var array|null Cached draft state for the current request. */
    protected $draftstatecache = null;

    /**
     * Return context for the current submission.
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        if ($courseid < 1) {
            return \context_system::instance();
        }
        return context_course::instance($courseid);
    }

    /**
     * Check access for the current submission.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('block/catquiz_feedbackwizard:use', $this->get_context_for_dynamic_submission());
    }

    /**
     * Preload data for the current submission.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $USER;

        $draftid = $this->optional_param('draftid', 0, PARAM_INT);
        if ($draftid > 0) {
            $draft = new draft_persistent($draftid);
            if ((int)$draft->get('userid') !== (int)$USER->id) {
                return;
            }
            $data = json_decode((string)$draft->get('datajson'), true);
            if (is_array($data)) {
                $data['draftid'] = $draftid;
                $this->set_data((object)$data);
            }
            return;
        }

        $testid = $this->optional_param('testid', 0, PARAM_INT);
        $wizardmode = $this->optional_param('wizardmode', '', PARAM_ALPHA);
        $sourcetestid = $this->optional_param('sourcetestid', 0, PARAM_INT);
        $recordid = $wizardmode === 'clone' ? $sourcetestid : $testid;

        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        if ($recordid > 0 && in_array($wizardmode, ['edit', 'clone'], true)) {
            $record = catquiz_data::get_test_by_id($recordid, $courseid);
            if ($record) {
                $sourceid = $wizardmode === 'clone' ? $recordid : 0;
                $defaults = test_config_normalizer::build_wizard_defaults($record, $wizardmode, $sourceid);
                if ($wizardmode === 'clone') {
                    $defaults['selectedtest'] = $testid;
                    $defaults['testid'] = $testid;
                    $defaults['sourcetestid'] = $recordid;
                }
                $this->set_data((object)$defaults);
            }
        }
    }

    /**
     * Build the wizard form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $step = $this->optional_param('step', 1, PARAM_INT);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $draftid = $this->optional_param('draftid', 0, PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'step', $step);
        $mform->setType('step', PARAM_INT);

        $mform->addElement('hidden', 'draftid', $draftid);
        $mform->setType('draftid', PARAM_INT);

        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_ALPHA);

        switch ($step) {
            case 1:
                $this->add_test_selection_step($mform, $courseid);
                break;
            case 2:
                $this->add_mode_selection_step($mform, $courseid);
                break;
            case 3:
                $this->add_configuration_step($mform);
                break;
            case 4:
                $this->add_feedback_step($mform);
                break;
            case 5:
                $this->add_matching_step($mform);
                break;
            case 6:
                $this->add_review_step($mform);
                break;
            default:
                throw new \moodle_exception('error:invalidstep', 'block_catquiz_feedbackwizard');
        }
    }

    /**
     * Add the test selection step.
     *
     * @param \MoodleQuickForm $mform
     * @param int $courseid
     * @return void
     */
    protected function add_test_selection_step(\MoodleQuickForm $mform, int $courseid): void {
        $mform->addElement('header', 'step1header', get_string('step01:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step1description', '', get_string('step01:description', 'block_catquiz_feedbackwizard'));

        $tests = catquiz_data::get_tests_by_courseid($courseid);
        $options = [];
        foreach ($tests as $test) {
            $options[(int)$test->id] = catquiz_data::get_test_display_name($test);
        }

        $mform->addElement('select', 'selectedtest', get_string('field:selectedtest', 'block_catquiz_feedbackwizard'), $options);
        $mform->setType('selectedtest', PARAM_INT);
        if (!empty($options)) {
            $mform->addRule('selectedtest', get_string('required'), 'required', null, 'client');
        } else {
            $mform->freeze('selectedtest');
            $mform->addElement(
                'static',
                'notestsavailable',
                '',
                get_string('message:notestsavailable', 'block_catquiz_feedbackwizard')
            );
        }
    }

    /**
     * Add the mode selection step.
     *
     * @param \MoodleQuickForm $mform
     * @param int $courseid
     * @return void
     */
    protected function add_mode_selection_step(\MoodleQuickForm $mform, int $courseid): void {
        $mform->addElement('header', 'step2header', get_string('step02:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step2description', '', get_string('step02:description', 'block_catquiz_feedbackwizard'));

        $mform->addElement(
            'select',
            'wizardmode',
            get_string('field:wizardmode', 'block_catquiz_feedbackwizard'),
            [
                'edit' => get_string('mode:edit', 'block_catquiz_feedbackwizard'),
                'clone' => get_string('mode:clone', 'block_catquiz_feedbackwizard'),
                'scenario' => get_string('mode:scenario', 'block_catquiz_feedbackwizard'),
                'import' => get_string('mode:import', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('wizardmode', PARAM_ALPHA);

        $selectedtest = (int)$this->get_state_value('selectedtest', 0, PARAM_INT);
        $candidates = catquiz_data::get_clone_candidates($courseid, $selectedtest);
        $cloneoptions = [0 => get_string('choosedots')];
        foreach ($candidates as $candidate) {
            $cloneoptions[(int)$candidate->id] = catquiz_data::get_test_display_name($candidate);
        }

        $mform->addElement(
            'select',
            'sourcetestid',
            get_string('field:sourcetestid', 'block_catquiz_feedbackwizard'),
            $cloneoptions
        );
        $mform->setType('sourcetestid', PARAM_INT);
        $mform->disabledIf('sourcetestid', 'wizardmode', 'neq', 'clone');

        $mform->addElement(
            'select',
            'clonescope',
            get_string('field:clonescope', 'block_catquiz_feedbackwizard'),
            [
                'full' => get_string('clonescope:full', 'block_catquiz_feedbackwizard'),
                'structure' => get_string('clonescope:structure', 'block_catquiz_feedbackwizard'),
                'conditions' => get_string('clonescope:conditions', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('clonescope', PARAM_ALPHA);
        $mform->disabledIf('clonescope', 'wizardmode', 'neq', 'clone');

        $mform->addElement(
            'select',
            'scenario',
            get_string('field:scenario', 'block_catquiz_feedbackwizard'),
            [
                '' => get_string('choosedots'),
                'learning_diagnostics' => get_string('scenario:learning_diagnostics', 'block_catquiz_feedbackwizard'),
                'placement' => get_string('scenario:placement', 'block_catquiz_feedbackwizard'),
                'checkup' => get_string('scenario:checkup', 'block_catquiz_feedbackwizard'),
                'final' => get_string('scenario:final', 'block_catquiz_feedbackwizard'),
                'strength' => get_string('scenario:strength', 'block_catquiz_feedbackwizard'),
                'other' => get_string('scenario:other', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('scenario', PARAM_ALPHANUMEXT);
        $mform->disabledIf('scenario', 'wizardmode', 'neq', 'scenario');

        $mform->addElement(
            'filepicker',
            'patternfile',
            get_string('field:patternfile', 'block_catquiz_feedbackwizard'),
            null,
            [
                'maxbytes' => feature_settings_service::get_pattern_import_maxbytes(),
                'accepted_types' => ['.json'],
            ]
        );
        $mform->hideIf('patternfile', 'wizardmode', 'neq', 'import');

        $mform->addElement(
            'static',
            'patternfileinfo',
            '',
            get_string('message:patternimportinfo', 'block_catquiz_feedbackwizard')
        );
        $mform->hideIf('patternfileinfo', 'wizardmode', 'neq', 'import');
    }

    /**
     * Add the configuration step.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    protected function add_configuration_step(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'step3header', get_string('step03:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step3description', '', get_string('step03:description', 'block_catquiz_feedbackwizard'));

        $scaleoptions = [0 => get_string('choosedots')] + catquiz_data::get_main_scale_options();
        $mform->addElement(
            'select',
            'mainscaleid',
            get_string('field:mainscaleid', 'block_catquiz_feedbackwizard'),
            $scaleoptions
        );
        $mform->setType('mainscaleid', PARAM_INT);

        $mainscaleid = (int)$this->get_state_value('mainscaleid', 0, PARAM_INT);
        $subscaleoptions = catquiz_data::get_subscale_options($mainscaleid);
        if (!empty($subscaleoptions)) {
            $select = $mform->addElement(
                'select',
                'subscaleids',
                get_string('field:subscaleids', 'block_catquiz_feedbackwizard'),
                $subscaleoptions,
                ['multiple' => 'multiple', 'size' => min(count($subscaleoptions), 10)]
            );
            $mform->setType('subscaleids', PARAM_INT);
            $select->setMultiple(true);
        } else {
            $mform->addElement(
                'static',
                'subscaleidsinfo',
                get_string('field:subscaleids', 'block_catquiz_feedbackwizard'),
                get_string('message:nosubscalesavailable', 'block_catquiz_feedbackwizard')
            );
        }

        $mform->addElement('text', 'minquestioncount', get_string('field:minquestioncount', 'block_catquiz_feedbackwizard'));
        $mform->setType('minquestioncount', PARAM_INT);

        $mform->addElement('text', 'questioncount', get_string('field:questioncount', 'block_catquiz_feedbackwizard'));
        $mform->setType('questioncount', PARAM_INT);

        $mform->addElement(
            'text',
            'questioncountpersubscale',
            get_string('field:questioncountpersubscale', 'block_catquiz_feedbackwizard')
        );
        $mform->setType('questioncountpersubscale', PARAM_INT);

        $mform->addElement(
            'advcheckbox',
            'timelimitenabled',
            get_string('field:timelimitenabled', 'block_catquiz_feedbackwizard')
        );
        $mform->setType('timelimitenabled', PARAM_INT);

        $mform->addElement('text', 'timelimitminutes', get_string('field:timelimitminutes', 'block_catquiz_feedbackwizard'));
        $mform->setType('timelimitminutes', PARAM_INT);
        $mform->disabledIf('timelimitminutes', 'timelimitenabled', 'notchecked');

        $mform->addElement(
            'select',
            'precisionmode',
            get_string('field:precisionmode', 'block_catquiz_feedbackwizard'),
            [
                'low' => get_string('precision:low', 'block_catquiz_feedbackwizard'),
                'medium' => get_string('precision:medium', 'block_catquiz_feedbackwizard'),
                'high' => get_string('precision:high', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('precisionmode', PARAM_ALPHA);

        $mform->addElement(
            'select',
            'testgoal',
            get_string('field:testgoal', 'block_catquiz_feedbackwizard'),
            [
                '' => get_string('choosedots'),
                'orientation' => get_string('goal:orientation', 'block_catquiz_feedbackwizard'),
                'placement' => get_string('goal:placement', 'block_catquiz_feedbackwizard'),
                'diagnostics' => get_string('goal:diagnostics', 'block_catquiz_feedbackwizard'),
                'final' => get_string('goal:final', 'block_catquiz_feedbackwizard'),
                'strength' => get_string('goal:strength', 'block_catquiz_feedbackwizard'),
                'other' => get_string('goal:other', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('testgoal', PARAM_ALPHANUMEXT);

        $mform->addElement(
            'advcheckbox',
            'completionenabled',
            get_string('field:completionenabled', 'block_catquiz_feedbackwizard')
        );
        $mform->setType('completionenabled', PARAM_INT);
    }

    /**
     * Add the fixed feedback step.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    protected function add_feedback_step(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'step4header', get_string('step04:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step4description', '', get_string('step04:description', 'block_catquiz_feedbackwizard'));

        $state = $this->load_draft_state();
        $mainscaleid = (int)($state['mainscaleid'] ?? 0);
        $rangecount = $this->optional_param(
            'feedbackrangecount',
            (int)($state['feedbackrangecount'] ?? 3),
            PARAM_INT
        );
        $state['feedbackrangecount'] = $rangecount;
        $range = catquiz_data::get_scale_range($mainscaleid);
        $feedbackdefaults = test_config_normalizer::build_feedback_defaults_from_wizard_state(
            $state,
            (float)$range['min'],
            (float)$range['max']
        );

        $mform->addElement(
            'select',
            'feedbackrangecount',
            get_string('field:feedbackrangecount', 'block_catquiz_feedbackwizard'),
            [2 => '2', 3 => '3', 4 => '4', 5 => '5']
        );
        $mform->setType('feedbackrangecount', PARAM_INT);
        $mform->setDefault('feedbackrangecount', $feedbackdefaults['feedbackrangecount']);

        $mform->addElement(
            'select',
            'reportingstrategy',
            get_string('field:reportingstrategy', 'block_catquiz_feedbackwizard'),
            [
                'main_only' => get_string('reporting:main_only', 'block_catquiz_feedbackwizard'),
                'subscales_only' => get_string('reporting:subscales_only', 'block_catquiz_feedbackwizard'),
                'main_and_subscales_separate' =>
                    get_string('reporting:main_and_subscales_separate', 'block_catquiz_feedbackwizard'),
                'subscales_with_parents_without_main' =>
                    get_string('reporting:subscales_with_parents_without_main', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('reportingstrategy', PARAM_ALPHAEXT);
        $mform->setDefault('reportingstrategy', $feedbackdefaults['reportingstrategy']);

        $mform->addElement(
            'static',
            'feedbacktokeninfo',
            '',
            get_string('message:feedbacktokeninfo', 'block_catquiz_feedbackwizard')
        );

        if (!feature_settings_service::is_course_provisioning_enabled()) {
            $mform->addElement(
                'static',
                'courseprovisioningdisabled',
                '',
                get_string('message:courseprovisioningdisabled', 'block_catquiz_feedbackwizard')
            );
        }
        if (!feature_settings_service::is_group_autocreate_enabled()) {
            $mform->addElement(
                'static',
                'groupautocreatedisabled',
                '',
                get_string('message:groupautocreatedisabled', 'block_catquiz_feedbackwizard')
            );
        }

        $rangecount = (int)$feedbackdefaults['feedbackrangecount'];
        for ($index = 1; $index <= $rangecount; $index++) {
            $mform->addElement(
                'header',
                'feedbackrangeheader_' . $index,
                get_string('field:feedbackrangeheader', 'block_catquiz_feedbackwizard', $index)
            );
            $mform->setExpanded('feedbackrangeheader_' . $index, true);

            $mform->addElement(
                'text',
                'feedbacklabel_' . $index,
                get_string('field:feedbacklabel', 'block_catquiz_feedbackwizard', $index)
            );
            $mform->setType('feedbacklabel_' . $index, PARAM_TEXT);
            $mform->setDefault('feedbacklabel_' . $index, $feedbackdefaults['feedbacklabel_' . $index] ?? '');

            $mform->addElement(
                'text',
                'feedbacklower_' . $index,
                get_string('field:feedbacklower', 'block_catquiz_feedbackwizard', $index)
            );
            $mform->setType('feedbacklower_' . $index, PARAM_FLOAT);
            $mform->setDefault('feedbacklower_' . $index, $feedbackdefaults['feedbacklower_' . $index] ?? 0);

            $mform->addElement(
                'text',
                'feedbackupper_' . $index,
                get_string('field:feedbackupper', 'block_catquiz_feedbackwizard', $index)
            );
            $mform->setType('feedbackupper_' . $index, PARAM_FLOAT);
            $mform->setDefault('feedbackupper_' . $index, $feedbackdefaults['feedbackupper_' . $index] ?? 0);

            $mform->addElement(
                'textarea',
                'feedbacktext_' . $index,
                get_string('field:feedbacktext', 'block_catquiz_feedbackwizard', $index),
                ['rows' => 4, 'cols' => 80]
            );
            $mform->setType('feedbacktext_' . $index, PARAM_RAW);
            $mform->setDefault('feedbacktext_' . $index, $feedbackdefaults['feedbacktext_' . $index] ?? '');

            $mform->addElement(
                'select',
                'feedbacktemplateformat_' . $index,
                get_string('field:feedbacktemplateformat', 'block_catquiz_feedbackwizard', $index),
                [
                    'mustache' => get_string('templateformat:mustache', 'block_catquiz_feedbackwizard'),
                    'plain' => get_string('templateformat:plain', 'block_catquiz_feedbackwizard'),
                ]
            );
            $mform->setType('feedbacktemplateformat_' . $index, PARAM_ALPHA);
            $mform->setDefault(
                'feedbacktemplateformat_' . $index,
                $feedbackdefaults['feedbacktemplateformat_' . $index] ?? 'mustache'
            );

            if (feature_settings_service::is_course_provisioning_enabled()) {
                $mform->addElement(
                    'advcheckbox',
                    'feedbackactioncourseenabled_' . $index,
                    get_string('field:feedbackactioncourseenabled', 'block_catquiz_feedbackwizard', $index)
                );
                $mform->setType('feedbackactioncourseenabled_' . $index, PARAM_INT);
                $mform->setDefault(
                    'feedbackactioncourseenabled_' . $index,
                    $feedbackdefaults['feedbackactioncourseenabled_' . $index] ?? 0
                );

                $mform->addElement(
                    'text',
                    'feedbackactioncoursetarget_' . $index,
                    get_string('field:feedbackactioncoursetarget', 'block_catquiz_feedbackwizard', $index)
                );
                $mform->setType('feedbackactioncoursetarget_' . $index, PARAM_TEXT);
                $mform->setDefault(
                    'feedbackactioncoursetarget_' . $index,
                    $feedbackdefaults['feedbackactioncoursetarget_' . $index] ?? ''
                );
                $mform->disabledIf(
                    'feedbackactioncoursetarget_' . $index,
                    'feedbackactioncourseenabled_' . $index,
                    'notchecked'
                );
            }

            if (feature_settings_service::is_group_autocreate_enabled()) {
                $mform->addElement(
                    'advcheckbox',
                    'feedbackactiongroupenabled_' . $index,
                    get_string('field:feedbackactiongroupenabled', 'block_catquiz_feedbackwizard', $index)
                );
                $mform->setType('feedbackactiongroupenabled_' . $index, PARAM_INT);
                $mform->setDefault(
                    'feedbackactiongroupenabled_' . $index,
                    $feedbackdefaults['feedbackactiongroupenabled_' . $index] ?? 0
                );

                $mform->addElement(
                    'text',
                    'feedbackactiongrouptarget_' . $index,
                    get_string('field:feedbackactiongrouptarget', 'block_catquiz_feedbackwizard', $index)
                );
                $mform->setType('feedbackactiongrouptarget_' . $index, PARAM_TEXT);
                $mform->setDefault(
                    'feedbackactiongrouptarget_' . $index,
                    $feedbackdefaults['feedbackactiongrouptarget_' . $index] ?? ''
                );
                $mform->disabledIf(
                    'feedbackactiongrouptarget_' . $index,
                    'feedbackactiongroupenabled_' . $index,
                    'notchecked'
                );
            }
        }
    }

    /**
     * Add the matching step.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    protected function add_matching_step(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'step5header', get_string('step05:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step5description', '', get_string('step05:description', 'block_catquiz_feedbackwizard'));

        $defaults = test_config_normalizer::build_matching_defaults($this->load_draft_state());

        $mform->addElement(
            'select',
            'matchingmode',
            get_string('field:matchingmode', 'block_catquiz_feedbackwizard'),
            [
                'none' => get_string('matchingmode:none', 'block_catquiz_feedbackwizard'),
                'rule' => get_string('matchingmode:rule', 'block_catquiz_feedbackwizard'),
                'csv' => get_string('matchingmode:csv', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('matchingmode', PARAM_ALPHA);
        $mform->setDefault('matchingmode', $defaults['matchingmode']);

        $mform->addElement(
            'select',
            'matchingcategoryid',
            get_string('field:matchingcategoryid', 'block_catquiz_feedbackwizard'),
            catquiz_data::get_course_category_options()
        );
        $mform->setType('matchingcategoryid', PARAM_INT);
        $mform->setDefault('matchingcategoryid', $defaults['matchingcategoryid']);
        $mform->hideIf('matchingcategoryid', 'matchingmode', 'eq', 'none');

        $mform->addElement(
            'select',
            'matchingcoursefield',
            get_string('field:matchingcoursefield', 'block_catquiz_feedbackwizard'),
            [
                'shortname' => get_string('matchingcoursefield:shortname', 'block_catquiz_feedbackwizard'),
                'fullname' => get_string('matchingcoursefield:fullname', 'block_catquiz_feedbackwizard'),
                'idnumber' => get_string('matchingcoursefield:idnumber', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('matchingcoursefield', PARAM_ALPHA);
        $mform->setDefault('matchingcoursefield', $defaults['matchingcoursefield']);
        $mform->hideIf('matchingcoursefield', 'matchingmode', 'neq', 'rule');

        $mform->addElement(
            'select',
            'matchingoperator',
            get_string('field:matchingoperator', 'block_catquiz_feedbackwizard'),
            [
                'contains' => get_string('matchingoperator:contains', 'block_catquiz_feedbackwizard'),
                'startswith' => get_string('matchingoperator:startswith', 'block_catquiz_feedbackwizard'),
                'equals' => get_string('matchingoperator:equals', 'block_catquiz_feedbackwizard'),
                'regex' => get_string('matchingoperator:regex', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('matchingoperator', PARAM_ALPHA);
        $mform->setDefault('matchingoperator', $defaults['matchingoperator']);
        $mform->hideIf('matchingoperator', 'matchingmode', 'neq', 'rule');

        $mform->addElement('text', 'matchingpattern', get_string('field:matchingpattern', 'block_catquiz_feedbackwizard'));
        $mform->setType('matchingpattern', PARAM_TEXT);
        $mform->setDefault('matchingpattern', $defaults['matchingpattern']);
        $mform->hideIf('matchingpattern', 'matchingmode', 'neq', 'rule');

        $mform->addElement(
            'select',
            'matchingtargettype',
            get_string('field:matchingtargettype', 'block_catquiz_feedbackwizard'),
            [
                'catscale' => get_string('matchingtargettype:catscale', 'block_catquiz_feedbackwizard'),
                'course' => get_string('matchingtargettype:course', 'block_catquiz_feedbackwizard'),
                'group' => get_string('matchingtargettype:group', 'block_catquiz_feedbackwizard'),
            ]
        );
        $mform->setType('matchingtargettype', PARAM_ALPHA);
        $mform->setDefault('matchingtargettype', $defaults['matchingtargettype']);
        $mform->hideIf('matchingtargettype', 'matchingmode', 'neq', 'rule');

        $mform->addElement(
            'text',
            'matchingtargetvalue',
            get_string('field:matchingtargetvalue', 'block_catquiz_feedbackwizard')
        );
        $mform->setType('matchingtargetvalue', PARAM_TEXT);
        $mform->setDefault('matchingtargetvalue', $defaults['matchingtargetvalue']);
        $mform->hideIf('matchingtargetvalue', 'matchingmode', 'neq', 'rule');

        $mform->addElement(
            'static',
            'matchingcsvinfo',
            '',
            get_string('message:matchingcsvtemplate', 'block_catquiz_feedbackwizard')
        );
        $mform->hideIf('matchingcsvinfo', 'matchingmode', 'neq', 'csv');

        $mform->addElement(
            'textarea',
            'matchingcsv',
            get_string('field:matchingcsv', 'block_catquiz_feedbackwizard'),
            ['rows' => 8, 'cols' => 80]
        );
        $mform->setType('matchingcsv', PARAM_RAW);
        $mform->setDefault('matchingcsv', $defaults['matchingcsv']);
        $mform->hideIf('matchingcsv', 'matchingmode', 'neq', 'csv');
    }

    /**
     * Add the review step.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    protected function add_review_step(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'step6header', get_string('step06:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step6description', '', get_string('step06:description', 'block_catquiz_feedbackwizard'));
        $mform->addElement(
            'static',
            'reviewsummary',
            get_string('field:reviewsummary', 'block_catquiz_feedbackwizard'),
            $this->build_review_summary()
        );

        $draftid = $this->optional_param('draftid', 0, PARAM_INT);
        if ($draftid > 0) {
            $exporturl = new moodle_url('/blocks/catquiz_feedbackwizard/export.php', [
                'draftid' => $draftid,
                'courseid' => $this->optional_param('courseid', 0, PARAM_INT),
                'sesskey' => sesskey(),
            ]);
            $mform->addElement(
                'static',
                'patternexport',
                get_string('field:patternexport', 'block_catquiz_feedbackwizard'),
                html_writer::link(
                    $exporturl,
                    get_string('action:downloadpattern', 'block_catquiz_feedbackwizard'),
                    ['target' => '_blank', 'rel' => 'noopener']
                )
            );
        }
    }

    /**
     * Validate submitted data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = [];
        $step = (int)($data['step'] ?? 1);
        $courseid = (int)($data['courseid'] ?? 0);

        if ($step === 1 && empty($data['selectedtest'])) {
            $errors['selectedtest'] = get_string('required');
        }

        if (
            !empty($data['selectedtest'])
            && !catquiz_data::test_belongs_to_course((int)$data['selectedtest'], $courseid)
        ) {
            $errors['selectedtest'] = get_string('error:testnotincourse', 'block_catquiz_feedbackwizard');
        }

        if ($step === 2) {
            if (($data['wizardmode'] ?? '') === 'clone' && empty($data['sourcetestid'])) {
                $errors['sourcetestid'] = get_string('required');
            }
            if (
                !empty($data['sourcetestid'])
                && !catquiz_data::test_belongs_to_course((int)$data['sourcetestid'], $courseid)
            ) {
                $errors['sourcetestid'] = get_string('error:testnotincourse', 'block_catquiz_feedbackwizard');
            }
            if (
                ($data['wizardmode'] ?? '') === 'clone'
                && !empty($data['sourcetestid'])
                && (int)$data['sourcetestid'] === (int)($data['selectedtest'] ?? 0)
            ) {
                $errors['sourcetestid'] = get_string('error:sameclonesource', 'block_catquiz_feedbackwizard');
            }
            if (($data['wizardmode'] ?? '') === 'scenario' && empty($data['scenario'])) {
                $errors['scenario'] = get_string('required');
            }
            if (($data['wizardmode'] ?? '') === 'import') {
                $error = $this->validate_pattern_upload();
                if ($error !== '') {
                    $errors['patternfile'] = $error;
                }
            }
        }

        if ($step === 3) {
            if (empty($data['mainscaleid'])) {
                $errors['mainscaleid'] = get_string('required');
            }
            if (!empty($data['minquestioncount']) && (int)$data['minquestioncount'] < 0) {
                $errors['minquestioncount'] = get_string('err_numeric', 'form');
            }
            if (!empty($data['questioncount']) && (int)$data['questioncount'] < 1) {
                $errors['questioncount'] = get_string('err_positive', 'form');
            }
            if (!empty($data['questioncountpersubscale']) && (int)$data['questioncountpersubscale'] < 0) {
                $errors['questioncountpersubscale'] = get_string('err_numeric', 'form');
            }
            if (!empty($data['timelimitenabled']) && empty($data['timelimitminutes'])) {
                $errors['timelimitminutes'] = get_string('required');
            }
            if (!empty($data['timelimitminutes']) && (int)$data['timelimitminutes'] < 1) {
                $errors['timelimitminutes'] = get_string('err_positive', 'form');
            }
            if (
                !empty($data['minquestioncount'])
                && !empty($data['questioncount'])
                && (int)$data['minquestioncount'] > (int)$data['questioncount']
            ) {
                $errors['questioncount'] = get_string('error:minlargerthanmax', 'block_catquiz_feedbackwizard');
            }
        }

        if ($step === 4) {
            $rangecount = test_config_normalizer::normalise_feedback_range_count((int)($data['feedbackrangecount'] ?? 0));
            $reportingstrategy = (string)($data['reportingstrategy'] ?? '');
            $subscaleids = array_filter(array_map('intval', (array)($data['subscaleids'] ?? [])));
            if ($reportingstrategy === '') {
                $errors['reportingstrategy'] = get_string('required');
            }
            if (
                in_array($reportingstrategy, ['subscales_only', 'subscales_with_parents_without_main'], true)
                && empty($subscaleids)
            ) {
                $errors['reportingstrategy'] = get_string('error:reportingsubscalesrequired', 'block_catquiz_feedbackwizard');
            }

            $previousupper = null;
            for ($index = 1; $index <= $rangecount; $index++) {
                $label = trim((string)($data['feedbacklabel_' . $index] ?? ''));
                $text = trim((string)($data['feedbacktext_' . $index] ?? ''));
                $lower = $data['feedbacklower_' . $index] ?? null;
                $upper = $data['feedbackupper_' . $index] ?? null;

                if ($label === '') {
                    $errors['feedbacklabel_' . $index] = get_string('required');
                }
                if ($text === '') {
                    $errors['feedbacktext_' . $index] = get_string('required');
                }
                if ($lower === null || $lower === '') {
                    $errors['feedbacklower_' . $index] = get_string('required');
                }
                if ($upper === null || $upper === '') {
                    $errors['feedbackupper_' . $index] = get_string('required');
                }
                if ($lower !== null && $lower !== '' && $upper !== null && $upper !== '' && (float)$lower >= (float)$upper) {
                    $errors['feedbackupper_' . $index] = get_string('error:feedbackinvalidrange', 'block_catquiz_feedbackwizard');
                }
                if ($previousupper !== null && $lower !== null && $lower !== '') {
                    if (abs((float)$previousupper - (float)$lower) > 0.001) {
                        $errors['feedbacklower_' . $index] = get_string('error:feedbackrangegap', 'block_catquiz_feedbackwizard');
                    }
                }
                if ($upper !== null && $upper !== '') {
                    $previousupper = (float)$upper;
                }

                if (
                    !empty($data['feedbackactioncourseenabled_' . $index])
                    && trim((string)($data['feedbackactioncoursetarget_' . $index] ?? '')) === ''
                ) {
                    $errors['feedbackactioncoursetarget_' . $index] =
                        get_string('error:feedbackactioncoursetargetrequired', 'block_catquiz_feedbackwizard');
                }
                if (
                    !empty($data['feedbackactiongroupenabled_' . $index])
                    && trim((string)($data['feedbackactiongrouptarget_' . $index] ?? '')) === ''
                ) {
                    $errors['feedbackactiongrouptarget_' . $index] =
                        get_string('error:feedbackactiongrouptargetrequired', 'block_catquiz_feedbackwizard');
                }
            }
        }


        if ($step === 5) {
            $matchingmode = matching_config_service::normalise_mode((string)($data['matchingmode'] ?? 'none'));
            if ($matchingmode !== 'none' && empty($data['matchingcategoryid'])) {
                $errors['matchingcategoryid'] = get_string('error:matchingcategoryrequired', 'block_catquiz_feedbackwizard');
            }
            if ($matchingmode === 'rule') {
                if (trim((string)($data['matchingpattern'] ?? '')) === '') {
                    $errors['matchingpattern'] = get_string('error:matchingpatternrequired', 'block_catquiz_feedbackwizard');
                }
                if (trim((string)($data['matchingtargetvalue'] ?? '')) === '') {
                    $errors['matchingtargetvalue'] = get_string('error:matchingtargetrequired', 'block_catquiz_feedbackwizard');
                }
                if (
                    ($data['matchingoperator'] ?? '') === 'regex'
                    && matching_config_service::has_invalid_regex(trim((string)($data['matchingpattern'] ?? '')))
                ) {
                    $errors['matchingpattern'] = get_string('error:matchingregexinvalid', 'block_catquiz_feedbackwizard');
                }
            }
            if (
                $matchingmode === 'csv'
                && matching_config_service::count_csv_rules((string)($data['matchingcsv'] ?? '')) < 1
            ) {
                $errors['matchingcsv'] = get_string('error:matchingcsvinvalid', 'block_catquiz_feedbackwizard');
            }
        }

        return $errors;
    }

    /**
     * Process dynamic submission.
     *
     * @return object
     */
    public function process_dynamic_submission(): object {
        global $USER;

        $data = (object)$this->get_data();
        $step = (int)($data->step ?? 1);
        $courseid = (int)($data->courseid ?? 0);
        $draftid = (int)($data->draftid ?? 0);
        $action = (string)($data->action ?? 'next');
        $selectedtest = (int)($data->selectedtest ?? 0);

        // The capability was checked against $courseid, so every test id we act on
        // must belong to that same course. Otherwise the wizard would be a way to
        // read and overwrite CAT configurations of unrelated courses.
        if ($selectedtest > 0 && !catquiz_data::test_belongs_to_course($selectedtest, $courseid)) {
            throw new \moodle_exception('error:testnotincourse', 'block_catquiz_feedbackwizard');
        }
        $sourcetestid = (int)($data->sourcetestid ?? 0);
        if ($sourcetestid > 0 && !catquiz_data::test_belongs_to_course($sourcetestid, $courseid)) {
            throw new \moodle_exception('error:testnotincourse', 'block_catquiz_feedbackwizard');
        }

        if ($draftid > 0) {
            $draft = new draft_persistent($draftid);
        } else {
            $draft = new draft_persistent(0, (object)[
                'userid' => (int)$USER->id,
                'courseid' => $courseid,
                'testid' => $selectedtest,
                'status' => 'draft',
                'step' => $step,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $current = [];
        if ($draft->get('datajson')) {
            $decoded = json_decode((string)$draft->get('datajson'), true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        $tomerge = (array)$data;
        unset($tomerge['step'], $tomerge['draftid'], $tomerge['courseid'], $tomerge['sesskey'], $tomerge['id'], $tomerge['action']);
        $merged = feature_settings_service::sanitise_wizard_state(array_merge($current, $tomerge));

        if ($step === 2) {
            $wizardmode = (string)($data->wizardmode ?? 'edit');
            if ($wizardmode === 'scenario') {
                $scenario = (string)($data->scenario ?? '');
                if ($scenario !== '') {
                    $defaults = scenario_preset_service::get_preset($scenario);
                    $merged = array_merge($defaults, $merged);
                    $merged['selectedtest'] = $selectedtest;
                    $merged['testid'] = $selectedtest;
                }
            } else if ($wizardmode === 'edit' && $selectedtest > 0) {
                $record = catquiz_data::get_test_by_id($selectedtest, $courseid);
                if ($record) {
                    $defaults = test_config_normalizer::build_wizard_defaults($record, 'edit');
                    $merged = array_merge($defaults, $merged);
                    $merged['selectedtest'] = $selectedtest;
                    $merged['testid'] = $selectedtest;
                }
            } else if ($wizardmode === 'import') {
                $json = (string)$this->get_file_content('patternfile');
                if ($json !== '') {
                    $pattern = pattern_import_service::parse($json);
                    $imported = pattern_import_service::to_wizard_state($pattern);
                    // Imported values are defaults: anything the user already
                    // typed in this session keeps precedence.
                    $merged = array_merge($imported, $merged);
                    $merged['selectedtest'] = $selectedtest;
                    $merged['testid'] = $selectedtest;
                    $merged['patternwarnings'] = pattern_import_service::get_warnings();
                    $merged['patternname'] = (string)($pattern['meta']['name'] ?? '');
                }
            } else if ($wizardmode === 'clone') {
                $targetrecord = catquiz_data::get_test_by_id($selectedtest, $courseid);
                $sourcerecord = catquiz_data::get_test_by_id($sourcetestid, $courseid);
                if ($targetrecord && $sourcerecord) {
                    $targetdefaults = test_config_normalizer::build_wizard_defaults($targetrecord, 'clone', (int)$sourcerecord->id);
                    $sourcedefaults = test_config_normalizer::build_wizard_defaults($sourcerecord, 'clone', (int)$sourcerecord->id);
                    $clonescope = (string)($data->clonescope ?? 'full');
                    $defaults = test_config_normalizer::merge_clone_defaults($targetdefaults, $sourcedefaults, $clonescope);
                    $merged = array_merge($defaults, $merged);
                    $merged['selectedtest'] = $selectedtest;
                    $merged['testid'] = $selectedtest;
                    $merged['sourcetestid'] = (int)$sourcerecord->id;
                    $merged['clonescope'] = $clonescope;
                }
            }
        }

        $draft->set('testid', $selectedtest);
        $draft->set('datajson', json_encode($merged));
        $draft->set('step', $step);
        $draft->set('timemodified', time());
        $draft->save();

        if ($action === 'back' && $step > 1) {
            return (object)[
                'status' => 'continue',
                'message' => get_string('savedprogress', 'block_catquiz_feedbackwizard'),
                'nextstep' => $step - 1,
                'draftid' => $draft->get('id'),
            ];
        }

        if ($step < self::MAXSTEPS) {
            return (object)[
                'status' => 'continue',
                'message' => get_string('savedprogress', 'block_catquiz_feedbackwizard'),
                'nextstep' => $step + 1,
                'draftid' => $draft->get('id'),
            ];
        }

        require_capability(
            'block/catquiz_feedbackwizard:writeconfig',
            $this->get_context_for_dynamic_submission()
        );
        test_config_writer::write_to_test($selectedtest, $merged);
        $draft->set('status', 'submitted');
        $draft->set('timemodified', time());
        $draft->save();

        return (object)[
            'status' => 'submitted',
            'message' => get_string('submissionsuccess', 'block_catquiz_feedbackwizard'),
            'recordid' => $draft->get('id'),
        ];
    }

    /**
     * Build a compact review summary from current draft data.
     *
     * @return string
     */
    protected function build_review_summary(): string {
        $data = $this->load_draft_state();
        if (empty($data)) {
            return get_string('message:reviewsummary', 'block_catquiz_feedbackwizard');
        }

        $summary = [];
        $summary[] = get_string('field:selectedtest', 'block_catquiz_feedbackwizard') . ': #' . (int)($data['selectedtest'] ?? 0);
        $summary[] = get_string('field:wizardmode', 'block_catquiz_feedbackwizard') . ': ' .
            s((string)($data['wizardmode'] ?? 'edit'));
        if (($data['wizardmode'] ?? '') === 'clone') {
            $summary[] = get_string('field:clonescope', 'block_catquiz_feedbackwizard') . ': ' .
                s((string)($data['clonescope'] ?? 'full'));
        }
        $summary[] = get_string('field:mainscaleid', 'block_catquiz_feedbackwizard') . ': ' . (int)($data['mainscaleid'] ?? 0);
        $summary[] = get_string('field:subscaleids', 'block_catquiz_feedbackwizard') . ': ' .
            count((array)($data['subscaleids'] ?? []));
        $summary[] = get_string('field:questioncount', 'block_catquiz_feedbackwizard') . ': ' . (int)($data['questioncount'] ?? 0);
        $summary[] = get_string('field:timelimitminutes', 'block_catquiz_feedbackwizard') . ': ' .
            (int)($data['timelimitminutes'] ?? 0);
        $summary[] = get_string('field:precisionmode', 'block_catquiz_feedbackwizard') . ': ' .
            s((string)($data['precisionmode'] ?? 'medium'));
        $summary[] = get_string('field:testgoal', 'block_catquiz_feedbackwizard') . ': ' .
            s((string)($data['testgoal'] ?? ''));
        $summary[] = get_string('field:completionenabled', 'block_catquiz_feedbackwizard') . ': ' .
            (!empty($data['completionenabled']) ? get_string('yes') : get_string('no'));
        $summary[] = get_string('field:reportingstrategy', 'block_catquiz_feedbackwizard') . ': ' .
            s($this->get_reporting_strategy_label((string)($data['reportingstrategy'] ?? 'main_only')));
        $summary[] = get_string('field:feedbackrangecount', 'block_catquiz_feedbackwizard') . ': ' .
            (int)($data['feedbackrangecount'] ?? 0);

        $range = catquiz_data::get_scale_range((int)($data['mainscaleid'] ?? 0));
        $feedbackfields = test_config_normalizer::build_feedback_defaults_from_wizard_state(
            $data,
            (float)$range['min'],
            (float)$range['max']
        );
        $rangecount = (int)($feedbackfields['feedbackrangecount'] ?? 0);
        for ($index = 1; $index <= $rangecount; $index++) {
            $summary[] = get_string('field:feedbackrangeheader', 'block_catquiz_feedbackwizard', $index) . ': ' .
                s((string)($feedbackfields['feedbacklabel_' . $index] ?? '')) . ' [' .
                s((string)($feedbackfields['feedbacklower_' . $index] ?? '')) . ' - ' .
                s((string)($feedbackfields['feedbackupper_' . $index] ?? '')) . ']';
            $summary[] = get_string('field:feedbacktemplateformat', 'block_catquiz_feedbackwizard', $index) . ': ' .
                s($this->get_template_format_label((string)($feedbackfields['feedbacktemplateformat_' . $index] ?? 'mustache')));
            $summary[] = get_string('field:feedbackactionsummary', 'block_catquiz_feedbackwizard', $index) . ': ' .
                s($this->describe_feedback_actions($feedbackfields, $index));
        }

        $summary[] = get_string('field:matchingsummary', 'block_catquiz_feedbackwizard') . ': ' .
            s($this->describe_matching_configuration($data));

        foreach ((array)($data['patternwarnings'] ?? []) as $warning) {
            $summary[] = get_string('field:reviewwarning', 'block_catquiz_feedbackwizard') . ': ' . s((string)$warning);
        }

        foreach ($this->build_review_warnings($data) as $warning) {
            $summary[] = get_string('field:reviewwarning', 'block_catquiz_feedbackwizard') . ': ' . s($warning);
        }
        $summary[] = get_string('field:readiness', 'block_catquiz_feedbackwizard') . ': ' .
            s($this->calculate_readiness_label($data));

        return html_writer::alist($summary);
    }

    /**
     * Calculate a simple readiness label for the review step.
     *
     * @param array $data
     * @return string
     */
    protected function calculate_readiness_label(array $data): string {
        $warnings = $this->build_review_warnings($data);
        if (empty($data['mainscaleid']) || empty($data['questioncount'])) {
            return get_string('readiness:incomplete', 'block_catquiz_feedbackwizard');
        }
        if (!empty($warnings)) {
            return get_string('readiness:warnings', 'block_catquiz_feedbackwizard');
        }
        return get_string('readiness:ready', 'block_catquiz_feedbackwizard');
    }

    /**
     * Build review warnings for the current wizard state.
     *
     * @param array $data
     * @return array
     */
    protected function build_review_warnings(array $data): array {
        $warnings = [];
        $subscalecount = count((array)($data['subscaleids'] ?? []));
        if ($subscalecount < 1) {
            $warnings[] = get_string('warning:nosubscalesselected', 'block_catquiz_feedbackwizard');
        }

        $questioncount = (int)($data['questioncount'] ?? 0);
        if (($data['precisionmode'] ?? 'medium') === 'high' && $questioncount > 0 && $questioncount < 15) {
            $warnings[] = get_string('warning:highprecisionlowquestions', 'block_catquiz_feedbackwizard');
        }

        $timelimitenabled = !empty($data['timelimitenabled']);
        $timelimitminutes = (int)($data['timelimitminutes'] ?? 0);
        if ($timelimitenabled && $timelimitminutes > 0 && $timelimitminutes < 10) {
            $warnings[] = get_string('warning:shorttimelimit', 'block_catquiz_feedbackwizard');
        }

        $reportingstrategy = (string)($data['reportingstrategy'] ?? 'main_only');
        if (
            in_array($reportingstrategy, ['subscales_only', 'subscales_with_parents_without_main'], true)
            && empty($data['subscaleids'])
        ) {
            $warnings[] = get_string('warning:reportingsubscaleswithoutselection', 'block_catquiz_feedbackwizard');
        }

        $range = catquiz_data::get_scale_range((int)($data['mainscaleid'] ?? 0));
        $feedbackfields = test_config_normalizer::build_feedback_defaults_from_wizard_state(
            $data,
            (float)$range['min'],
            (float)$range['max']
        );
        $rangecount = (int)($feedbackfields['feedbackrangecount'] ?? 0);
        $previousupper = null;
        for ($index = 1; $index <= $rangecount; $index++) {
            $lower = isset($feedbackfields['feedbacklower_' . $index]) ? (float)$feedbackfields['feedbacklower_' . $index] : null;
            $upper = isset($feedbackfields['feedbackupper_' . $index]) ? (float)$feedbackfields['feedbackupper_' . $index] : null;
            if ($lower !== null && $upper !== null && $lower >= $upper) {
                $warnings[] = get_string('warning:feedbackrangesneedreview', 'block_catquiz_feedbackwizard');
                break;
            }
            if ($previousupper !== null && $lower !== null && abs($previousupper - $lower) > 0.001) {
                $warnings[] = get_string('warning:feedbackrangesneedreview', 'block_catquiz_feedbackwizard');
                break;
            }
            if (trim((string)($feedbackfields['feedbacktext_' . $index] ?? '')) === '') {
                $warnings[] = get_string('warning:feedbacktextmissing', 'block_catquiz_feedbackwizard');
                break;
            }
            $previousupper = $upper;
        }

        $matchingmode = matching_config_service::normalise_mode((string)($data['matchingmode'] ?? 'none'));
        if ($matchingmode === 'rule') {
            if (
                empty($data['matchingcategoryid'])
                || trim((string)($data['matchingpattern'] ?? '')) === ''
                || trim((string)($data['matchingtargetvalue'] ?? '')) === ''
            ) {
                $warnings[] = get_string('warning:matchingconfigincomplete', 'block_catquiz_feedbackwizard');
            }
        }
        if (
            $matchingmode === 'csv'
            && matching_config_service::count_csv_rules((string)($data['matchingcsv'] ?? '')) < 1
        ) {
            $warnings[] = get_string('warning:matchingconfigincomplete', 'block_catquiz_feedbackwizard');
        }

        return $warnings;
    }

    /**
     * Return a summary for the configured matching setup.
     *
     * @param array $data
     * @return string
     */
    protected function describe_matching_configuration(array $data): string {
        $mode = matching_config_service::normalise_mode((string)($data['matchingmode'] ?? 'none'));
        if ($mode === 'none') {
            return get_string('matchingmode:none', 'block_catquiz_feedbackwizard');
        }
        if ($mode === 'csv') {
            return get_string('matchingmode:csv', 'block_catquiz_feedbackwizard') . ' (' .
                matching_config_service::count_csv_rules((string)($data['matchingcsv'] ?? '')) . ')';
        }

        $parts = [get_string('matchingmode:rule', 'block_catquiz_feedbackwizard')];
        $parts[] = (string)($data['matchingcoursefield'] ?? 'shortname');
        $parts[] = (string)($data['matchingoperator'] ?? 'contains');
        $parts[] = trim((string)($data['matchingpattern'] ?? ''));
        $parts[] = '→';
        $parts[] = (string)($data['matchingtargettype'] ?? 'catscale');
        $parts[] = trim((string)($data['matchingtargetvalue'] ?? ''));
        return implode(' ', array_filter($parts, static function($value): bool {
            return $value !== '';
        }));
    }

    /**
     * Return a display label for one template format.
     *
     * @param string $templateformat
     * @return string
     */
    protected function get_template_format_label(string $templateformat): string {
        $key = 'templateformat:' . $templateformat;
        $manager = get_string_manager();
        if ($manager->string_exists($key, 'block_catquiz_feedbackwizard')) {
            return get_string($key, 'block_catquiz_feedbackwizard');
        }
        return $templateformat;
    }

    /**
     * Return a summary label for configured range actions.
     *
     * @param array $feedbackfields
     * @param int $index
     * @return string
     */
    protected function describe_feedback_actions(array $feedbackfields, int $index): string {
        $actions = [get_string('action:text', 'block_catquiz_feedbackwizard')];
        if (!empty($feedbackfields['feedbackactioncourseenabled_' . $index])) {
            $target = trim((string)($feedbackfields['feedbackactioncoursetarget_' . $index] ?? ''));
            $actions[] = get_string('action:course', 'block_catquiz_feedbackwizard') .
                ($target !== '' ? ' (' . $target . ')' : '');
        }
        if (!empty($feedbackfields['feedbackactiongroupenabled_' . $index])) {
            $target = trim((string)($feedbackfields['feedbackactiongrouptarget_' . $index] ?? ''));
            $actions[] = get_string('action:group', 'block_catquiz_feedbackwizard') .
                ($target !== '' ? ' (' . $target . ')' : '');
        }
        return implode(', ', $actions);
    }

    /**
     * Validate the uploaded settings pattern.
     *
     * @return string An error message, or an empty string when the file is fine.
     */
    protected function validate_pattern_upload(): string {
        $json = (string)$this->get_file_content('patternfile');

        if (trim($json) === '') {
            return get_string('error:patternfilerequired', 'block_catquiz_feedbackwizard');
        }

        try {
            pattern_import_service::parse($json);
        } catch (\moodle_exception $e) {
            return $e->getMessage();
        }

        return '';
    }

    /**
     * Load the current draft state.
     *
     * @return array
     */
    protected function load_draft_state(): array {
        if ($this->draftstatecache !== null) {
            return $this->draftstatecache;
        }

        $draftid = $this->optional_param('draftid', 0, PARAM_INT);
        if ($draftid < 1) {
            $this->draftstatecache = [];
            return $this->draftstatecache;
        }

        $draft = new draft_persistent($draftid);
        $data = json_decode((string)$draft->get('datajson'), true);
        $this->draftstatecache = is_array($data) ? $data : [];
        return $this->draftstatecache;
    }

    /**
     * Return one wizard state value.
     *
     * The modal only carries courseid, step and draftid as request arguments, so
     * values collected in earlier steps have to be read back from the draft when
     * the form definition is built.
     *
     * @param string $name Wizard state key.
     * @param mixed $default Value used when neither request nor draft carry the key.
     * @param string $type A PARAM_* constant used for the request lookup.
     * @return mixed
     */
    protected function get_state_value(string $name, $default, string $type) {
        $submitted = $this->optional_param($name, null, $type);
        if ($submitted !== null && $submitted !== '') {
            return $submitted;
        }

        $state = $this->load_draft_state();
        if (array_key_exists($name, $state) && $state[$name] !== '') {
            return $state[$name];
        }

        return $default;
    }

    /**
     * Return a display label for the selected reporting strategy.
     *
     * @param string $reportingstrategy
     * @return string
     */
    protected function get_reporting_strategy_label(string $reportingstrategy): string {
        $key = 'reporting:' . $reportingstrategy;
        $manager = get_string_manager();
        if ($manager->string_exists($key, 'block_catquiz_feedbackwizard')) {
            return get_string($key, 'block_catquiz_feedbackwizard');
        }
        return $reportingstrategy;
    }

    /**
     * Return the page URL for this submission.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        return new moodle_url('/course/view.php', ['id' => $courseid ?: SITEID]);
    }
}
