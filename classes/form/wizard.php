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
 * Plugin version and other meta-data are defined here.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
    
    namespace block_catquiz_feedbackwizard\form;

    use moodle_url; 
    use context_course; 
    use core_form\dynamic_form; 
    use block_catquiz_feedbackwizard\persistent\draft as draft_persistent;

    /**
     *
     */
    class wizard extends dynamic_form {

        /**
         * @return \context
         */
        protected function get_context_for_dynamic_submission(): \context {
            
            $courseid = $this->optional_param('courseid', 0, PARAM_INT);
            
            if (!$courseid) {
                // Fallback to system if not provided.
                return \context_system::instance();
            }
            return context_course::instance($courseid);
        }

        /**
         * @return void
         */
        protected function check_access_for_dynamic_submission(): void {
            require_capability('block/catquiz_feedbackwizard:use', $this->get_context_for_dynamic_submission());
        }

        /**
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
                retrun;
            }
            $this->set_data((object)$data);
        }

        /**
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

            switch ($step) {
                case 1:
                $mform->addElement('header', 'h1', get_string('step1title', 'block_catquiz_feedbackwizard'));
                $mform->addElement('text', 'title', get_string('field:title', 'block_catquiz_feedbackwizard'));
                $mform->setType('title', PARAM_TEXT);
                $mform->addRule('title', get_string('required'), 'required', null, 'client');
                $mform->addElement('select', 'category', get_string('field:category', 'block_catquiz_feedbackwizard'), [
                'general' => 'General',
                'news' => 'News',
                'assignment' => 'Assignment',
                ]);
                $mform->setType('category', PARAM_ALPHANUMEXT);
                
                # $this->add_action_buttons(true, get_string('submitnext', 'block_catquiz_feedbackwizard'));
                break;
                
                case 2:
                $mform->addElement('header', 'h2', get_string('step2title', 'block_catquiz_feedbackwizard'));
                $mform->addElement('editor', 'description', get_string('field:description', 'block_catquiz_feedbackwizard'));
                $mform->setType('description', PARAM_RAW);
                
                $fileoptions = [
                'maxbytes' => 0,
                'maxfiles' => 5,
                'subdirs' => 0,
                'accepted_types' => '*',
                ];
                $mform->addElement('filemanager', 'attachments', get_string('field:attachments', 'block_catquiz_feedbackwizard'), null, $fileoptions);
                
                # $this->add_action_buttons(true, get_string('submitnext', 'block_catquiz_feedbackwizard'));
                break;
                
                case 3:
                $mform->addElement('header', 'h3', get_string('step3title', 'block_catquiz_feedbackwizard'));
                // A simple review display. In a real implementation, you may recompose from stored draft data.
                $mform->addElement('static', 'review', '', 'Please review your data and click Submit.');
                # $this->add_action_buttons(true, get_string('submitfinal', 'block_catquiz_feedbackwizard'));
                break;
                
                default:
                throw new \moodle_exception('error:invalidstep', 'block_catquiz_feedbackwizard');
            }
        }

        /**
         * @param $data
         * @param $files
         * @return array
         */
        public function validation($data, $files): array {
            $errors = [];
            $step = (int)($data['step'] ?? 1);
            
            if ($step === 1) {
                if (empty(trim($data['title'] ?? ''))) {
                    $errors['title'] = get_string('required');
                }
            }
            // Add step 2 validations if required.
            return $errors;
        }

        /**
         * @return object
         */
        public function process_dynamic_submission() {
            global $USER;
            
            $data = (object)$this->get_data();
            $step = (int)($data->step ?? 1);
            $courseid = (int)$data->courseid;
            $draftid = (int)($data->draftid ?? 0);
            
            // Load or create draft holder for this flow.
            if ($draftid > 0) {
                $draft = new draft_persistent($draftid);
            } else {
                $draft = new draft_persistent(0, (object)[
                    'userid' => $USER->id,
                    'courseid' => $courseid,
                    'status' => 'draft',
                    'step' => $step,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ]);
            }
            
            // Merge new data with previous datajson.
            $current = [];
            if ($draft->get('datajson')) {
                $decoded = json_decode($draft->get('datajson'), true);
                if (is_array($decoded)) {
                    $current = $decoded;
                }
            }
            
            // Remove internal fields and merge.
            $tomerge = (array)$data;
            unset($tomerge['step'], $tomerge['draftid'], $tomerge['courseid'], $tomerge['sesskey'], $tomerge['id']);
            
            // For editor fields, dynamic_form provides arrays; store as-is or extract text as needed.
            $merged = array_merge($current, $tomerge);
            $draft->set('datajson', json_encode($merged));
            $draft->set('step', $step);
            $draft->set('timemodified', time());
            $draft->save();
            
            if ($step < 3) {
                // Tell the JS to reload the form for the next step.
                return (object)[
                'status' => 'continue',
                'message' => get_string('savedprogress', 'block_catquiz_feedbackwizard'),
                'nextstep' => $step + 1,
                'draftid' => $draft->get('id'),
                ];
            }
            
            // Final processing on step 3.
            // Example: Persist final data somewhere meaningful, send events, etc.
            $draft->set('status', 'submitted');
            $draft->set('timemodified', time());
            $draft->save();
            
            // Return final response; modal JS will close and show a success message.
            return (object)[
                'status' => 'submitted',
                'message' => get_string('submissionsuccess', 'block_catquiz_feedbackwizard'),
                'recordid' => $draft->get('id'),
            ];
        }

        /**
         * @return moodle_url
         */
        protected function get_page_url_for_dynamic_submission(): moodle_url {
            $courseid = $this->optional_param('courseid', 0, PARAM_INT);
            return new moodle_url('/course/view.php', ['id' => $courseid ?: SITEID]);
        }
    }    