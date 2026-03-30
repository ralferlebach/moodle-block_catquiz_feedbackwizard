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

namespace block_catquiz_feedbackwizard\form;

use block_catquiz_feedbackwizard\catquiz_data;
use block_catquiz_feedbackwizard\persistent\draft as draft_persistent;
use context_course;
use core_form\dynamic_form;
use moodle_url;

/**
 * Dynamic form for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 */
class wizard extends dynamic_form {
    /** @var int Number of implemented wizard steps. */
    public const MAXSTEPS = 6;

    /**
     * Get submission context.
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        if (!$courseid) {
            return \context_system::instance();
        }
        return context_course::instance($courseid);
    }

    /**
     * Check access.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('block/catquiz_feedbackwizard:use', $this->get_context_for_dynamic_submission());
    }

    /**
     * Populate data from stored draft JSON.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $USER;

        $draftid = $this->optional_param('draftid', 0, PARAM_INT);
        if (!$draftid) {
            return;
        }

        $draft = new draft_persistent($draftid);
        if ((int)$draft->get('userid') !== (int)$USER->id) {
            return;
        }

        $json = $draft->get('datajson');
        if (empty($json)) {
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return;
        }

        if (!array_key_exists('testid', $data)) {
            $data['testid'] = (int)$draft->get('testid');
        }

        $this->set_data((object)$data);
    }

    /**
     * Build the form for the current step.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $step = $this->optional_param('step', 1, PARAM_INT);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $draftid = $this->optional_param('draftid', 0, PARAM_INT);
        $testid = $this->optional_param('testid', 0, PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'step', $step);
        $mform->setType('step', PARAM_INT);
        $mform->addElement('hidden', 'draftid', $draftid);
        $mform->setType('draftid', PARAM_INT);
        if ($step !== 1) {
            $mform->addElement('hidden', 'testid', $testid);
            $mform->setType('testid', PARAM_INT);
        }
        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_ALPHA);

        switch ($step) {
            case 1:
                $this->definition_select_test($mform, $courseid);
                break;
            case 2:
                $this->definition_choose_mode($mform, $courseid, $testid);
                break;
            case 3:
                $this->definition_choose_scenario($mform);
                break;
            case 4:
                $this->definition_select_scales($mform, $testid);
                break;
            case 5:
                $this->definition_conditions($mform);
                break;
            case 6:
                $this->definition_review($mform, $courseid, $testid);
                break;
            default:
                throw new \moodle_exception('error:invalidstep', 'block_catquiz_feedbackwizard');
        }
    }

    /**
     * Step 1: Select CAT test.
     *
     * @param \MoodleQuickForm $mform
     * @param int $courseid
     * @return void
     */
    protected function definition_select_test(\MoodleQuickForm $mform, int $courseid): void {
        $mform->addElement('header', 'step01', get_string('step01:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step01desc', '', get_string('step01:description', 'block_catquiz_feedbackwizard'));

        $tests = catquiz_data::get_tests_by_courseid($courseid);
        if (empty($tests)) {
            $mform->addElement('static', 'notests', '', get_string('notestsfound', 'block_catquiz_feedbackwizard'));
            return;
        }

        $options = [];
        foreach ($tests as $test) {
            $label = format_string($test->name ?: get_string('unnamedtest', 'block_catquiz_feedbackwizard'));
            $label .= ' — ' . $test->readinesslabel;
            if (!empty($test->catscaleid)) {
                $label .= ' — ' . get_string('label:scaleid', 'block_catquiz_feedbackwizard', $test->catscaleid);
            }
            $options[$test->id] = $label;
        }

        $mform->addElement('select', 'testid', get_string('field:testid', 'block_catquiz_feedbackwizard'), $options);
        $mform->setType('testid', PARAM_INT);
        $mform->addRule('testid', get_string('required'), 'required', null, 'client');

        $details = [];
        foreach ($tests as $test) {
            $details[] = \html_writer::tag('li',
                s($test->name ?: get_string('unnamedtest', 'block_catquiz_feedbackwizard')) .
                ' — ' . s($test->readinesslabel) .
                ' — ' . \html_writer::link($test->editurl, get_string('link:edittests', 'block_catquiz_feedbackwizard'),
                    ['target' => '_blank', 'rel' => 'noopener']));
        }
        $mform->addElement('html', \html_writer::tag('ul', implode('', $details), ['class' => 'list-unstyled mt-2']));
    }

    /**
     * Step 2: Choose mode.
     *
     * @param \MoodleQuickForm $mform
     * @param int $courseid
     * @param int $testid
     * @return void
     */
    protected function definition_choose_mode(\MoodleQuickForm $mform, int $courseid, int $testid): void {
        $mform->addElement('header', 'step02', get_string('step02:title', 'block_catquiz_feedbackwizard'));

        $mform->addElement('radio', 'wizardmode', get_string('field:wizardmode', 'block_catquiz_feedbackwizard'),
            get_string('mode:new', 'block_catquiz_feedbackwizard'), 'new');
        $mform->addElement('radio', 'wizardmode', '', get_string('mode:clone', 'block_catquiz_feedbackwizard'), 'clone');
        $mform->addElement('radio', 'wizardmode', '', get_string('mode:edit', 'block_catquiz_feedbackwizard'), 'edit');
        $mform->addElement('radio', 'wizardmode', '', get_string('mode:import', 'block_catquiz_feedbackwizard'), 'import');
        $mform->setType('wizardmode', PARAM_ALPHA);
        $mform->setDefault('wizardmode', 'new');
        $mform->addRule('wizardmode', get_string('required'), 'required', null, 'client');

        $candidates = catquiz_data::get_clone_candidates($courseid, $testid);
        if (!empty($candidates)) {
            $options = [0 => get_string('none')];
            foreach ($candidates as $candidate) {
                $options[$candidate->id] = format_string($candidate->name) . ' — ' . $candidate->readinesslabel;
            }
            $mform->addElement('select', 'sourcetestid', get_string('field:sourcetestid', 'block_catquiz_feedbackwizard'), $options);
            $mform->setType('sourcetestid', PARAM_INT);
        }

        $mform->addElement('advcheckbox', 'importjson', get_string('field:importjson', 'block_catquiz_feedbackwizard'));
        $mform->setType('importjson', PARAM_BOOL);
    }

    /**
     * Step 3: Choose scenario.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    protected function definition_choose_scenario(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'step03', get_string('step03:title', 'block_catquiz_feedbackwizard'));
        $options = [
            'learning_diagnostics' => get_string('scenario:learning_diagnostics', 'block_catquiz_feedbackwizard'),
            'placement_test' => get_string('scenario:placement_test', 'block_catquiz_feedbackwizard'),
            'checkup_fulladaptive' => get_string('scenario:checkup_fulladaptive', 'block_catquiz_feedbackwizard'),
            'final_partialadaptive' => get_string('scenario:final_partialadaptive', 'block_catquiz_feedbackwizard'),
            'strength_profile' => get_string('scenario:strength_profile', 'block_catquiz_feedbackwizard'),
            'other' => get_string('scenario:other', 'block_catquiz_feedbackwizard'),
        ];
        $mform->addElement('select', 'scenario', get_string('field:scenario', 'block_catquiz_feedbackwizard'), $options);
        $mform->setType('scenario', PARAM_ALPHANUMEXT);
        $mform->setDefault('scenario', 'learning_diagnostics');

        $mform->addElement('textarea', 'scenario_notes', get_string('field:scenario_notes', 'block_catquiz_feedbackwizard'),
            ['rows' => 4, 'cols' => 80]);
        $mform->setType('scenario_notes', PARAM_TEXT);
    }

    /**
     * Step 4: Select scales.
     *
     * @param \MoodleQuickForm $mform
     * @param int $testid
     * @return void
     */
    protected function definition_select_scales(\MoodleQuickForm $mform, int $testid): void {
        $mform->addElement('header', 'step04', get_string('step04:title', 'block_catquiz_feedbackwizard'));

        $test = catquiz_data::get_test_by_id($testid);
        if ($test) {
            $mform->addElement('static', 'selectedtest', get_string('field:selectedtest', 'block_catquiz_feedbackwizard'),
                format_string($test->name) . ' — ' . s($test->readinesslabel));
            $mform->setDefault('mainscaleid', (int)($test->catscaleid ?? 0));
        }

        $mform->addElement('text', 'mainscaleid', get_string('field:mainscaleid', 'block_catquiz_feedbackwizard'));
        $mform->setType('mainscaleid', PARAM_INT);

        $mform->addElement('textarea', 'subscaleids', get_string('field:subscaleids', 'block_catquiz_feedbackwizard'),
            ['rows' => 3, 'cols' => 80]);
        $mform->setType('subscaleids', PARAM_TEXT);
        $mform->addHelpButton('subscaleids', 'field:subscaleids_help', 'block_catquiz_feedbackwizard');
    }

    /**
     * Step 5: Conditions.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    protected function definition_conditions(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'step05', get_string('step05:title', 'block_catquiz_feedbackwizard'));

        $goaloptions = [
            'orientation' => get_string('goal:orientation', 'block_catquiz_feedbackwizard'),
            'placement' => get_string('goal:placement', 'block_catquiz_feedbackwizard'),
            'progress' => get_string('goal:progress', 'block_catquiz_feedbackwizard'),
            'completion' => get_string('goal:completion', 'block_catquiz_feedbackwizard'),
            'strengths' => get_string('goal:strengths', 'block_catquiz_feedbackwizard'),
        ];
        $mform->addElement('select', 'goal', get_string('field:goal', 'block_catquiz_feedbackwizard'), $goaloptions);
        $mform->setType('goal', PARAM_ALPHA);
        $mform->setDefault('goal', 'orientation');

        $mform->addElement('text', 'timelimitminutes', get_string('field:timelimitminutes', 'block_catquiz_feedbackwizard'));
        $mform->setType('timelimitminutes', PARAM_INT);

        $mform->addElement('text', 'questioncount', get_string('field:questioncount', 'block_catquiz_feedbackwizard'));
        $mform->setType('questioncount', PARAM_INT);

        $precision = [
            'low' => get_string('precision:low', 'block_catquiz_feedbackwizard'),
            'medium' => get_string('precision:medium', 'block_catquiz_feedbackwizard'),
            'high' => get_string('precision:high', 'block_catquiz_feedbackwizard'),
        ];
        $mform->addElement('select', 'precisionmode', get_string('field:precisionmode', 'block_catquiz_feedbackwizard'), $precision);
        $mform->setType('precisionmode', PARAM_ALPHA);
        $mform->setDefault('precisionmode', 'medium');
    }

    /**
     * Step 6: Review.
     *
     * @param \MoodleQuickForm $mform
     * @param int $courseid
     * @param int $testid
     * @return void
     */
    protected function definition_review(\MoodleQuickForm $mform, int $courseid, int $testid): void {
        $mform->addElement('header', 'step06', get_string('step06:title', 'block_catquiz_feedbackwizard'));

        $test = catquiz_data::get_test_by_id($testid);
        $testlabel = $test ? format_string($test->name) : get_string('notavailable', 'block_catquiz_feedbackwizard');

        $summary = [
            get_string('review:courseid', 'block_catquiz_feedbackwizard', $courseid),
            get_string('review:test', 'block_catquiz_feedbackwizard', $testlabel),
            get_string('review:hint', 'block_catquiz_feedbackwizard'),
        ];
        $mform->addElement('static', 'reviewsummary', '', \html_writer::alist($summary));
    }

    /**
     * Validate the current step.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        $step = (int)($data['step'] ?? 1);
        $action = (string)($data['action'] ?? '');

        if ($action === 'back') {
            return $errors;
        }

        switch ($step) {
            case 1:
                if (empty($data['testid'])) {
                    $errors['testid'] = get_string('required');
                }
                break;
            case 2:
                if (empty($data['wizardmode'])) {
                    $errors['wizardmode'] = get_string('required');
                }
                if (($data['wizardmode'] ?? '') === 'clone' && empty($data['sourcetestid'])) {
                    $errors['sourcetestid'] = get_string('required');
                }
                break;
            case 4:
                if (empty($data['mainscaleid'])) {
                    $errors['mainscaleid'] = get_string('required');
                }
                break;
            case 5:
                if (!empty($data['timelimitminutes']) && (int)$data['timelimitminutes'] < 0) {
                    $errors['timelimitminutes'] = get_string('error:nonnegative', 'block_catquiz_feedbackwizard');
                }
                if (!empty($data['questioncount']) && (int)$data['questioncount'] < 1) {
                    $errors['questioncount'] = get_string('error:questioncount', 'block_catquiz_feedbackwizard');
                }
                break;
        }

        return $errors;
    }

    /**
     * Process the submitted step and persist the draft.
     *
     * @return object
     */
    public function process_dynamic_submission() {
        global $USER;

        $data = (object)$this->get_data();
        $step = (int)($data->step ?? 1);
        $courseid = (int)($data->courseid ?? 0);
        $draftid = (int)($data->draftid ?? 0);
        $testid = (int)($data->testid ?? 0);
        $action = (string)($data->action ?? 'next');

        if ($draftid > 0) {
            $draft = new draft_persistent($draftid);
        } else {
            $draft = new draft_persistent(0, (object)[
                'userid' => $USER->id,
                'courseid' => $courseid,
                'testid' => $testid,
                'status' => 'draft',
                'step' => $step,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $current = [];
        if ($draft->get('datajson')) {
            $decoded = json_decode($draft->get('datajson'), true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        $tomerge = (array)$data;
        unset($tomerge['step'], $tomerge['draftid'], $tomerge['courseid'], $tomerge['sesskey'], $tomerge['id'], $tomerge['action']);
        $merged = array_merge($current, $tomerge);
        $merged['mode'] = $tomerge['wizardmode'] ?? ($merged['wizardmode'] ?? 'new');

        $draft->set('testid', $testid);
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
     * Return the page URL.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        return new moodle_url('/course/view.php', ['id' => $courseid ?: SITEID]);
    }
}
