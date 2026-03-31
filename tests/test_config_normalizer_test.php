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
 * PHPUnit tests for the CATQuiz wizard normalizer.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \block_catquiz_feedbackwizard\local\service\test_config_normalizer
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\test_config_normalizer;

/**
 * PHPUnit tests for the CATQuiz wizard normalizer.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class test_config_normalizer_test extends \advanced_testcase {
    /**
     * Test extracting subscale ids from saved JSON keys.
     *
     * @covers ::extract_subscale_ids
     * @return void
     */
    public function test_extract_subscale_ids(): void {
        $jsondata = [
            'catquiz_subscalecheckbox_11' => 1,
            'catquiz_subscalecheckbox_12' => 0,
            'catquiz_subscalecheckbox_13' => '1',
        ];

        $this->assertSame([11, 13], test_config_normalizer::extract_subscale_ids($jsondata));
    }

    /**
     * Test building wizard defaults from an existing local_catquiz test record.
     *
     * @covers ::build_wizard_defaults
     * @return void
     */
    public function test_build_wizard_defaults(): void {
        $record = (object) [
            'id' => 99,
            'catscaleid' => 7,
            'json' => json_encode([
                'catquiz_catscales' => 8,
                'catquiz_subscalecheckbox_21' => 1,
                'catquiz_subscalecheckbox_22' => 1,
                'maxquestionsgroup' => [
                    'catquiz_minquestions' => 5,
                    'catquiz_maxquestions' => 17,
                ],
                'maxquestionsscalegroup' => [
                    'catquiz_maxquestionspersubscale' => 4,
                ],
                'catquiz_includetimelimit' => 1,
                'catquiz_timelimitgroup' => [
                    'catquiz_timeselect_attempt' => 'min',
                    'catquiz_maxtimeperattempt' => 30,
                ],
                'catquiz_standarderrorgroup' => [
                    'catquiz_standarderror_min' => 0.2,
                ],
                'numberoffeedbackoptionsselect' => 2,
                'feedback_scaleid_limit_lower_8_1' => -3.0,
                'feedback_scaleid_limit_upper_8_1' => 0.0,
                'feedback_scaleid_limit_lower_8_2' => 0.0,
                'feedback_scaleid_limit_upper_8_2' => 3.0,
                'feedbackeditor_scaleid_8_1' => ['text' => 'Needs support'],
                'feedbackeditor_scaleid_8_2' => ['text' => 'Doing well'],
                'catquiz_scalereportcheckbox_8' => 1,
                'catquiz_wizard' => [
                    'feedbackranges' => [
                        [
                            'label' => 'Support',
                            'lower' => -3.0,
                            'upper' => 0.0,
                            'text' => 'Needs support',
                            'templateformat' => 'plain',
                            'actioncourseenabled' => 1,
                            'actioncoursetarget' => 'COURSE-A',
                            'actiongroupenabled' => 1,
                            'actiongrouptarget' => 'GROUP-A',
                        ],
                        [
                            'label' => 'Success',
                            'lower' => 0.0,
                            'upper' => 3.0,
                            'text' => 'Doing well',
                            'templateformat' => 'mustache',
                            'actioncourseenabled' => 0,
                            'actioncoursetarget' => '',
                            'actiongroupenabled' => 0,
                            'actiongrouptarget' => '',
                        ],
                    ],
                ],
            ]),
        ];

        $defaults = test_config_normalizer::build_wizard_defaults($record, 'edit');

        $this->assertSame(99, $defaults['selectedtest']);
        $this->assertSame(8, $defaults['mainscaleid']);
        $this->assertSame([21, 22], $defaults['subscaleids']);
        $this->assertSame(5, $defaults['minquestioncount']);
        $this->assertSame(17, $defaults['questioncount']);
        $this->assertSame(4, $defaults['questioncountpersubscale']);
        $this->assertSame(1, $defaults['timelimitenabled']);
        $this->assertSame(30, $defaults['timelimitminutes']);
        $this->assertSame('high', $defaults['precisionmode']);
        $this->assertSame('', $defaults['testgoal']);
        $this->assertSame(0, $defaults['completionenabled']);
        $this->assertSame(2, $defaults['feedbackrangecount']);
        $this->assertSame('fixed', $defaults['feedbackmode']);
        $this->assertSame('text_only', $defaults['feedbackdisplaymode']);
        $this->assertSame('main_only', $defaults['reportingstrategy']);
        $this->assertSame('Support', $defaults['feedbacklabel_1']);
        $this->assertSame(-3.0, $defaults['feedbacklower_1']);
        $this->assertSame('Needs support', $defaults['feedbacktext_1']);
        $this->assertSame('plain', $defaults['feedbacktemplateformat_1']);
        $this->assertSame(1, $defaults['feedbackactioncourseenabled_1']);
        $this->assertSame('COURSE-A', $defaults['feedbackactioncoursetarget_1']);
        $this->assertSame(1, $defaults['feedbackactiongroupenabled_1']);
        $this->assertSame('GROUP-A', $defaults['feedbackactiongrouptarget_1']);
    }

    /**
     * Test extracting a precision mode from standard error settings.
     *
     * @covers ::extract_precision_mode
     * @return void
     */
    public function test_extract_precision_mode(): void {
        $jsondata = [
            'catquiz_standarderrorgroup' => [
                'catquiz_standarderror_min' => 0.55,
            ],
        ];

        $this->assertSame('low', test_config_normalizer::extract_precision_mode($jsondata));
        $this->assertSame('high', test_config_normalizer::extract_precision_mode([], ['precisionmode' => 'high']));
    }

    /**
     * Test extracting wizard-owned goal and completion defaults.
     *
     * @covers ::extract_completion_enabled
     * @covers ::extract_test_goal
     * @return void
     */
    public function test_extract_wizard_goal_and_completion_defaults(): void {
        $jsondata = [
            'completion' => 0,
        ];
        $wizarddata = [
            'testgoal' => 'placement',
            'completionenabled' => 1,
        ];

        $this->assertSame('placement', test_config_normalizer::extract_test_goal($wizarddata));
        $this->assertSame(1, test_config_normalizer::extract_completion_enabled($jsondata, $wizarddata));
    }

    /**
     * Test merging clone defaults for different copy scopes.
     *
     * @covers ::merge_clone_defaults
     * @return void
     */
    public function test_merge_clone_defaults(): void {
        $target = [
            'mainscaleid' => 2,
            'subscaleids' => [21],
            'questioncount' => 10,
            'timelimitenabled' => 0,
            'precisionmode' => 'low',
            'testgoal' => '',
        ];
        $source = [
            'mainscaleid' => 7,
            'subscaleids' => [31, 32],
            'questioncount' => 25,
            'timelimitenabled' => 1,
            'timelimitminutes' => 40,
            'precisionmode' => 'high',
            'testgoal' => 'placement',
        ];

        $structure = test_config_normalizer::merge_clone_defaults($target, $source, 'structure');
        $conditions = test_config_normalizer::merge_clone_defaults($target, $source, 'conditions');

        $this->assertSame(7, $structure['mainscaleid']);
        $this->assertSame([31, 32], $structure['subscaleids']);
        $this->assertSame(10, $structure['questioncount']);
        $this->assertSame(2, $conditions['mainscaleid']);
        $this->assertSame(25, $conditions['questioncount']);
        $this->assertSame('placement', $conditions['testgoal']);
    }

    /**
     * Test building default fixed feedback ranges.
     *
     * @covers ::build_default_feedback_ranges
     * @covers ::normalise_feedback_range_count
     * @return void
     */
    public function test_build_default_feedback_ranges(): void {
        $ranges = test_config_normalizer::build_default_feedback_ranges(4, -4.0, 4.0);

        $this->assertCount(4, $ranges);
        $this->assertSame('Range 1', $ranges[0]['label']);
        $this->assertSame(-4.0, $ranges[0]['lower']);
        $this->assertSame(-2.0, $ranges[0]['upper']);
        $this->assertSame(3, test_config_normalizer::normalise_feedback_range_count(8));
    }

    /**
     * Test parsing CSV feedback ranges and variable presets.
     *
     * @covers ::parse_csv_feedback_ranges
     * @covers ::build_variable_feedback_ranges
     * @return void
     */
    public function test_parse_csv_feedback_ranges_and_variable_presets(): void {
        $csv = "label,lower,upper,text\nSupport,-3,0,Needs support\nSuccess,0,3,Doing well";
        $ranges = test_config_normalizer::parse_csv_feedback_ranges($csv);
        $variable = test_config_normalizer::build_variable_feedback_ranges(3, -3.0, 3.0, 'focus_low');

        $this->assertCount(2, $ranges);
        $this->assertSame('Support', $ranges[0]['label']);
        $this->assertSame(-3.0, $ranges[0]['lower']);
        $this->assertCount(3, $variable);
        $this->assertLessThan(0.0, $variable[0]['upper']);
        $this->assertSame(3.0, $variable[2]['upper']);
    }

}
