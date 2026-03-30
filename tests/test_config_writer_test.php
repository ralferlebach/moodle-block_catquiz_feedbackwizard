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

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\test_config_writer;

/**
 * PHPUnit coverage for the CATQuiz wizard test config writer.
 *
 * @package             block_catquiz_feedbackwizard
 * @coversDefaultClass  \block_catquiz_feedbackwizard\local\service\test_config_writer
 * @copyright           2026 OpenAI
 * @license             https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class test_config_writer_test extends \basic_testcase {
    /**
     * Test parsing a comma-separated list of subscale IDs.
     *
     * @covers ::parse_subscale_ids
     * @return void
     */
    public function test_parse_subscale_ids(): void {
        $actual = test_config_writer::parse_subscale_ids('5, 2, 5, abc, 9');

        $this->assertSame([2, 5, 9], $actual);
    }

    /**
     * Test applying wizard state to an existing CAT test JSON payload.
     *
     * @covers ::apply_wizard_state
     * @return void
     */
    public function test_apply_wizard_state(): void {
        $baseconfig = [
            'existingvalue' => 'keep-me',
            'catquiz_subscalecheckbox_12' => '1',
        ];

        $state = [
            'mode' => 'edit',
            'scenario' => 'placement_test',
            'goal' => 'placement',
            'mainscaleid' => 7,
            'subscaleids' => '11,12',
            'precisionmode' => 'high',
            'questioncount' => 18,
            'timelimitminutes' => 35,
        ];

        $actual = test_config_writer::apply_wizard_state($baseconfig, $state);

        $this->assertSame('keep-me', $actual['existingvalue']);
        $this->assertSame('1', $actual['catquiz_subscalecheckbox_11']);
        $this->assertSame('1', $actual['catquiz_subscalecheckbox_12']);
        $this->assertSame(18, $actual['maxquestionsgroup']['catquiz_maxquestions']);
        $this->assertSame(1, $actual['catquiz_includetimelimit']);
        $this->assertSame(7, $actual[test_config_writer::WIZARDKEY]['mainscaleid']);
        $this->assertSame('placement_test', $actual[test_config_writer::WIZARDKEY]['scenario']);
    }
}
