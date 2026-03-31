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
use block_catquiz_feedbackwizard\local\service\test_config_normalizer;
use block_catquiz_feedbackwizard\persistent\draft as draft_persistent;
use context_course;
use core_form\dynamic_form;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Multi-step wizard form for CATQuiz setup.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wizard extends dynamic_form {
    /** @var int Number of wizard steps. */
    const MAXSTEPS = 4;

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

        if ($testid > 0 && in_array($wizardmode, ['edit', 'clone'], true)) {
            $record = catquiz_data::get_test_by_id($wizardmode === 'clone' ? $sourcetestid : $testid);
            if ($record) {
                $defaults = test_config_normalizer::build_wizard_defaults(
                    $record,
                    $wizardmode,
                    $wizardmode === 'clone' ? (int)$record->id : $sourcetestid
                );
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
            $mform->addElement('static', 'notestsavailable', '', get_string('message:notestsavailable', 'block_catquiz_feedbackwizard'));
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

        $mform->addElement('select', 'wizardmode', get_string('field:wizardmode', 'block_catquiz_feedbackwizard'), [
            'edit' => get_string('mode:edit', 'block_catquiz_feedbackwizard'),
            'clone' => get_string('mode:clone', 'block_catquiz_feedbackwizard'),
            'scenario' => get_string('mode:scenario', 'block_catquiz_feedbackwizard'),
        ]);
        $mform->setType('wizardmode', PARAM_ALPHA);

        $selectedtest = $this->optional_param('selectedtest', 0, PARAM_INT);
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

        $mform->addElement('select', 'scenario', get_string('field:scenario', 'block_catquiz_feedbackwizard'), [
            '' => get_string('choosedots'),
            'learning_diagnostics' => get_string('scenario:learning_diagnostics', 'block_catquiz_feedbackwizard'),
            'placement' => get_string('scenario:placement', 'block_catquiz_feedbackwizard'),
            'checkup' => get_string('scenario:checkup', 'block_catquiz_feedbackwizard'),
            'final' => get_string('scenario:final', 'block_catquiz_feedbackwizard'),
            'strength' => get_string('scenario:strength', 'block_catquiz_feedbackwizard'),
            'other' => get_string('scenario:other', 'block_catquiz_feedbackwizard'),
        ]);
        $mform->setType('scenario', PARAM_ALPHANUMEXT);
        $mform->disabledIf('scenario', 'wizardmode', 'neq', 'scenario');
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
        $mform->addElement('select', 'mainscaleid', get_string('field:mainscaleid', 'block_catquiz_feedbackwizard'), $scaleoptions);
        $mform->setType('mainscaleid', PARAM_INT);

        $mainscaleid = $this->optional_param('mainscaleid', 0, PARAM_INT);
        $subscaleoptions = catquiz_data::get_subscale_options($mainscaleid);
        if (!empty($subscaleoptions)) {
            $select = $mform->addElement(
                'select',
                'subscaleids',
                get_string('field:subscaleids', 'block_catquiz_feedbackwizard'),
                $subscaleoptions,
                ['multiple' => 'multiple', 'size' => min(count($subscaleoptions), 8)]
            );
            $mform->setType('subscaleids', PARAM_INT);
            $select->setMultiple(true);
        } else {
            $mform->addElement('static', 'subscaleidsinfo', get_string('field:subscaleids', 'block_catquiz_feedbackwizard'),
                get_string('message:nosubscalesavailable', 'block_catquiz_feedbackwizard'));
        }

        $mform->addElement('text', 'questioncount', get_string('field:questioncount', 'block_catquiz_feedbackwizard'));
        $mform->setType('questioncount', PARAM_INT);

        $mform->addElement('text', 'timelimitminutes', get_string('field:timelimitminutes', 'block_catquiz_feedbackwizard'));
        $mform->setType('timelimitminutes', PARAM_INT);

        $mform->addElement('select', 'precisionmode', get_string('field:precisionmode', 'block_catquiz_feedbackwizard'), [
            'low' => get_string('precision:low', 'block_catquiz_feedbackwizard'),
            'medium' => get_string('precision:medium', 'block_catquiz_feedbackwizard'),
            'high' => get_string('precision:high', 'block_catquiz_feedbackwizard'),
        ]);
        $mform->setType('precisionmode', PARAM_ALPHA);
    }

    /**
     * Add the review step.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    protected function add_review_step(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'step4header', get_string('step04:title', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'step4description', '', get_string('step04:description', 'block_catquiz_feedbackwizard'));
        $mform->addElement('static', 'reviewsummary', get_string('field:reviewsummary', 'block_catquiz_feedbackwizard'),
            get_string('message:reviewsummary', 'block_catquiz_feedbackwizard'));
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

        if ($step === 1 && empty($data['selectedtest'])) {
            $errors['selectedtest'] = get_string('required');
        }

        if ($step === 2) {
            if (($data['wizardmode'] ?? '') === 'clone' && empty($data['sourcetestid'])) {
                $errors['sourcetestid'] = get_string('required');
            }
            if (($data['wizardmode'] ?? '') === 'scenario' && empty($data['scenario'])) {
                $errors['scenario'] = get_string('required');
            }
        }

        if ($step === 3) {
            if (empty($data['mainscaleid'])) {
                $errors['mainscaleid'] = get_string('required');
            }
            if (!empty($data['questioncount']) && (int)$data['questioncount'] < 1) {
                $errors['questioncount'] = get_string('err_positive', 'form');
            }
            if (!empty($data['timelimitminutes']) && (int)$data['timelimitminutes'] < 1) {
                $errors['timelimitminutes'] = get_string('err_positive', 'form');
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
        $merged = array_merge($current, $tomerge);

        if ($step === 2) {
            $wizardmode = (string)($data->wizardmode ?? 'edit');
            $recordid = $wizardmode === 'clone' ? (int)($data->sourcetestid ?? 0) : $selectedtest;
            if ($recordid > 0 && in_array($wizardmode, ['edit', 'clone'], true)) {
                $record = catquiz_data::get_test_by_id($recordid);
                if ($record) {
                    $defaults = test_config_normalizer::build_wizard_defaults($record, $wizardmode, $recordid);
                    $merged = array_merge($defaults, $merged);
                    $merged['selectedtest'] = $selectedtest;
                    $merged['testid'] = $selectedtest;
                    if ($wizardmode === 'clone') {
                        $merged['sourcetestid'] = $recordid;
                    }
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
     * Return the page URL for this submission.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        return new moodle_url('/course/view.php', ['id' => $courseid ?: SITEID]);
    }
}
