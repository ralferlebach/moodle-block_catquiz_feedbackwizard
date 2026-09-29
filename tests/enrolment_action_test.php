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
 * Integration tests for the enrolment actions.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\adapter\local_catquiz_adapter;
use block_catquiz_feedbackwizard\local\service\test_config_writer;

/**
 * Integration tests for the enrolment actions.
 *
 * These tests deliberately do not assert on our own JSON keys. A key we
 * invented would satisfy such an assertion just as well, which is exactly how
 * the previous, ineffective implementation passed its tests. Instead the
 * configuration written by the wizard is handed to local_catquiz's own code,
 * and the assertion is on the outcome: is the student enrolled, is the student
 * a member of the group.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\service\test_config_writer
 */
final class enrolment_action_test extends \advanced_testcase {
    /**
     * Skip when the engine is not installed, so a green run without it cannot
     * be mistaken for a verified write path.
     *
     * @return void
     */
    protected function require_engine(): void {
        if (!local_catquiz_adapter::is_available()) {
            $this->markTestSkipped('local_catquiz is not installed in this environment.');
        }
        if (!class_exists('\local_catquiz\catquiz')) {
            $this->markTestSkipped('local_catquiz\catquiz is not available.');
        }
        if (!class_exists('\local_catquiz\teststrategy\feedback_helper')) {
            $this->markTestSkipped('local_catquiz feedback_helper is not available.');
        }
    }

    /**
     * Create a CAT test record the writer can act on.
     *
     * @param int $courseid Course the test belongs to.
     * @param int $scaleid Main scale.
     * @return int
     */
    protected function create_test_record(int $courseid, int $scaleid): int {
        global $DB;

        return (int)$DB->insert_record('local_catquiz_tests', (object)[
            'componentid' => 4242,
            'component' => 'mod_adaptivequiz',
            'catscaleid' => $scaleid,
            'contextid' => 0,
            'courseid' => $courseid,
            'name' => 'Enrolment integration test',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'json' => json_encode([]),
            'status' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Create a CAT scale through the engine API.
     *
     * @return int
     */
    protected function create_scale(): int {
        $structure = new \local_catquiz\data\catscale_structure([
            'name' => 'Integration scale',
            'description' => '',
            'parentid' => 0,
            'minscalevalue' => -3.0,
            'maxscalevalue' => 3.0,
            'contextid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return (int)\local_catquiz\data\dataapi::create_catscale($structure);
    }

    /**
     * Run the engine's own selection logic against the stored configuration.
     *
     * This mirrors attemptfeedback::get_courses_to_enrol() and
     * get_groups_to_enrol(): the range is resolved by the engine's
     * feedback_helper, and the keys are read exactly as the engine reads them.
     *
     * @param int $testid
     * @param int $scaleid
     * @param float $ability The result that decides which range applies.
     * @return array [$coursestoenrol, $groupstoenrol]
     */
    protected function engine_selection(int $testid, int $scaleid, float $ability): array {
        global $DB;

        $quizsettings = json_decode((string)$DB->get_field('local_catquiz_tests', 'json', ['id' => $testid]), true);

        $index = \local_catquiz\teststrategy\feedback_helper::get_feedback_range_index(
            $quizsettings,
            $scaleid,
            $ability
        );

        if ($index === null) {
            return [[], []];
        }

        $courses = array_filter(
            (array)($quizsettings['catquiz_courses_' . $scaleid . '_' . $index] ?? []),
            static function ($value): bool {
                return $value != 0;
            }
        );

        $groups = (string)($quizsettings['catquiz_group_' . $scaleid . '_' . $index] ?? '');

        return [
            [$scaleid => [
                'range' => $index,
                'show_message' => !empty($quizsettings['enrolment_message_checkbox_' . $scaleid . '_' . $index]),
                'course_ids' => array_values($courses),
            ]],
            [$scaleid => $groups === '' ? [] : explode(',', $groups)],
        ];
    }

    /**
     * A result inside the range must actually enrol the student and add them
     * to the named group.
     *
     * @return void
     */
    public function test_result_in_range_enrols_the_student(): void {
        $this->resetAfterTest();
        $this->require_engine();

        $generator = $this->getDataGenerator();
        $owner = $generator->create_course(['shortname' => 'OWNER']);
        $target = $generator->create_course(['shortname' => 'TARGET']);
        $student = $generator->create_user();
        $group = $generator->create_group(['courseid' => $target->id, 'name' => 'Support A']);

        $scaleid = $this->create_scale();
        $testid = $this->create_test_record((int)$owner->id, $scaleid);

        test_config_writer::write_to_test($testid, [
            'mainscaleid' => $scaleid,
            'reportingstrategy' => 'main_only',
            'feedbackrangecount' => 2,
            'feedbacklabel_1' => 'Needs support',
            'feedbacklower_1' => -3.0,
            'feedbackupper_1' => 0.0,
            'feedbacktext_1' => 'Please take the support course.',
            'feedbackactioncourseenabled_1' => 1,
            'feedbackactioncoursetarget_1' => [(int)$target->id],
            'feedbackactionmessage_1' => 1,
            'feedbackactiongroupenabled_1' => 1,
            'feedbackactiongrouptarget_1' => 'Support A',
            'feedbacklabel_2' => 'Ready',
            'feedbacklower_2' => 0.0,
            'feedbackupper_2' => 3.0,
            'feedbacktext_2' => 'Well done.',
        ]);

        $context = \context_course::instance($target->id);
        $this->assertFalse(is_enrolled($context, $student->id), 'precondition: not enrolled yet');
        $this->assertFalse(groups_is_member($group->id, $student->id), 'precondition: not a member yet');

        // An ability of -1.5 falls into range 1.
        [$courses, $groups] = $this->engine_selection($testid, $scaleid, -1.5);
        \local_catquiz\catquiz::enrol_and_create_message_array(
            $courses,
            $groups,
            'Enrolment integration test',
            $scaleid,
            (int)$student->id
        );

        $this->assertTrue(
            is_enrolled($context, $student->id),
            'the student should be enrolled into the target course'
        );
        $this->assertTrue(
            groups_is_member($group->id, $student->id),
            'the student should have been added to the named group'
        );
    }

    /**
     * A result in a range without actions must leave the student alone.
     *
     * @return void
     */
    public function test_result_in_other_range_does_not_enrol(): void {
        $this->resetAfterTest();
        $this->require_engine();

        $generator = $this->getDataGenerator();
        $owner = $generator->create_course(['shortname' => 'OWNER2']);
        $target = $generator->create_course(['shortname' => 'TARGET2']);
        $student = $generator->create_user();
        $group = $generator->create_group(['courseid' => $target->id, 'name' => 'Support A']);

        $scaleid = $this->create_scale();
        $testid = $this->create_test_record((int)$owner->id, $scaleid);

        test_config_writer::write_to_test($testid, [
            'mainscaleid' => $scaleid,
            'reportingstrategy' => 'main_only',
            'feedbackrangecount' => 2,
            'feedbacklabel_1' => 'Needs support',
            'feedbacklower_1' => -3.0,
            'feedbackupper_1' => 0.0,
            'feedbacktext_1' => 'Please take the support course.',
            'feedbackactioncourseenabled_1' => 1,
            'feedbackactioncoursetarget_1' => [(int)$target->id],
            'feedbackactiongroupenabled_1' => 1,
            'feedbackactiongrouptarget_1' => 'Support A',
            'feedbacklabel_2' => 'Ready',
            'feedbacklower_2' => 0.0,
            'feedbackupper_2' => 3.0,
            'feedbacktext_2' => 'Well done.',
        ]);

        // An ability of 2.0 falls into range 2, which carries no actions.
        [$courses, $groups] = $this->engine_selection($testid, $scaleid, 2.0);
        \local_catquiz\catquiz::enrol_and_create_message_array(
            $courses,
            $groups,
            'Enrolment integration test',
            $scaleid,
            (int)$student->id
        );

        $this->assertFalse(
            is_enrolled(\context_course::instance($target->id), $student->id),
            'a range without actions must not enrol anybody'
        );
        $this->assertFalse(groups_is_member($group->id, $student->id));
    }

    /**
     * With the feature disabled, no enrolment may happen even if the form data
     * carries targets.
     *
     * @return void
     */
    public function test_disabled_feature_prevents_enrolment(): void {
        $this->resetAfterTest();
        $this->require_engine();

        $generator = $this->getDataGenerator();
        $owner = $generator->create_course(['shortname' => 'OWNER3']);
        $target = $generator->create_course(['shortname' => 'TARGET3']);
        $student = $generator->create_user();

        $scaleid = $this->create_scale();
        $testid = $this->create_test_record((int)$owner->id, $scaleid);

        // The gating service is what strips this; enable_courseprovisioning is
        // off by default, so the targets must not survive into the engine keys.
        $state = \block_catquiz_feedbackwizard\local\service\feature_settings_service::sanitise_wizard_state([
            'mainscaleid' => $scaleid,
            'reportingstrategy' => 'main_only',
            'feedbackrangecount' => 1,
            'feedbacklabel_1' => 'Needs support',
            'feedbacklower_1' => -3.0,
            'feedbackupper_1' => 3.0,
            'feedbacktext_1' => 'Text',
            'feedbackactioncourseenabled_1' => 1,
            'feedbackactioncoursetarget_1' => [(int)$target->id],
        ]);

        test_config_writer::write_to_test($testid, $state);

        [$courses, $groups] = $this->engine_selection($testid, $scaleid, 0.0);
        \local_catquiz\catquiz::enrol_and_create_message_array(
            $courses,
            $groups,
            'Enrolment integration test',
            $scaleid,
            (int)$student->id
        );

        $this->assertFalse(
            is_enrolled(\context_course::instance($target->id), $student->id),
            'a disabled feature must not reach the engine'
        );
    }
}
