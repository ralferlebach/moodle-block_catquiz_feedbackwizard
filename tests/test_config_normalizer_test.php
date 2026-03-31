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
}
