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
 * PHPUnit tests for scenario presets.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \block_catquiz_feedbackwizard\local\service\scenario_preset_service
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\scenario_preset_service;

/**
 * PHPUnit tests for scenario presets.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scenario_preset_service_test extends \advanced_testcase {
    /**
     * Test the placement scenario preset.
     *
     * @covers ::get_preset
     * @return void
     */
    public function test_get_preset_for_placement(): void {
        $preset = scenario_preset_service::get_preset('placement');

        $this->assertSame('scenario', $preset['wizardmode']);
        $this->assertSame('placement', $preset['scenario']);
        $this->assertSame(20, $preset['questioncount']);
        $this->assertSame(1, $preset['timelimitenabled']);
        $this->assertSame(45, $preset['timelimitminutes']);
        $this->assertSame('high', $preset['precisionmode']);
        $this->assertSame('placement', $preset['testgoal']);
        $this->assertSame(1, $preset['completionenabled']);
    }
}
