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
 * PHPUnit tests for the CATQuiz wizard writer.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \block_catquiz_feedbackwizard\local\service\test_config_writer
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\test_config_writer;

/**
 * PHPUnit tests for the CATQuiz wizard writer.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class test_config_writer_test extends \advanced_testcase {
    /**
     * Test mapping wizard state back to local_catquiz JSON.
     *
     * @covers ::apply_wizard_state
     * @return void
     */
    public function test_apply_wizard_state(): void {
        $jsondata = [
            'catquiz_subscalecheckbox_3' => 1,
            'something_else' => 'kept',
        ];

        $wizardstate = [
            'wizardmode' => 'edit',
            'scenario' => 'placement',
            'mainscaleid' => 9,
            'subscaleids' => [11, 12],
            'minquestioncount' => 4,
            'questioncount' => 20,
            'questioncountpersubscale' => 5,
            'timelimitenabled' => 1,
            'timelimitminutes' => 45,
            'precisionmode' => 'high',
            'testgoal' => 'placement',
            'completionenabled' => 1,
        ];

        $mapped = test_config_writer::apply_wizard_state($jsondata, $wizardstate);

        $this->assertSame('kept', $mapped['something_else']);
        $this->assertSame(9, $mapped['catscaleid']);
        $this->assertSame(9, $mapped['catquiz_catscales']);
        $this->assertArrayNotHasKey('catquiz_subscalecheckbox_3', $mapped);
        $this->assertSame(1, $mapped['catquiz_subscalecheckbox_11']);
        $this->assertSame(1, $mapped['catquiz_subscalecheckbox_12']);
        $this->assertSame(4, $mapped['maxquestionsgroup']['catquiz_minquestions']);
        $this->assertSame(20, $mapped['maxquestionsgroup']['catquiz_maxquestions']);
        $this->assertSame(5, $mapped['maxquestionsscalegroup']['catquiz_maxquestionspersubscale']);
        $this->assertSame(1, $mapped['catquiz_includetimelimit']);
        $this->assertSame(45, $mapped['catquiz_timelimitgroup']['catquiz_maxtimeperattempt']);
        $this->assertSame('min', $mapped['catquiz_timelimitgroup']['catquiz_timeselect_attempt']);
        $this->assertSame(0.2, $mapped['catquiz_standarderrorgroup']['catquiz_standarderror_min']);
        $this->assertSame(1, $mapped['completion']);
        $this->assertSame(1, $mapped['completionview']);
        $this->assertSame('placement', $mapped['catquiz_wizard']['scenario']);
        $this->assertSame('placement', $mapped['catquiz_wizard']['testgoal']);
    }
    /**
     * Test applying a predefined scenario preset.
     *
     * @covers ::apply_wizard_state
     * @return void
     */
    public function test_apply_wizard_state_from_scenario_preset(): void {
        $wizardstate = [
            'wizardmode' => 'scenario',
            'scenario' => 'final',
            'mainscaleid' => 4,
            'subscaleids' => [],
            'questioncount' => 30,
            'timelimitenabled' => 1,
            'timelimitminutes' => 60,
            'precisionmode' => 'medium',
            'testgoal' => 'final',
            'completionenabled' => 1,
        ];

        $mapped = test_config_writer::apply_wizard_state([], $wizardstate);

        $this->assertSame('final', $mapped['catquiz_wizard']['scenario']);
        $this->assertSame('final', $mapped['catquiz_wizard']['testgoal']);
        $this->assertSame(1, $mapped['completion']);
        $this->assertSame(60, $mapped['catquiz_timelimitgroup']['catquiz_maxtimeperattempt']);
    }

}
