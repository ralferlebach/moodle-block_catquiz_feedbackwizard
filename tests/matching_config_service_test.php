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
 * PHPUnit tests for matching configuration helpers.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      lock_catquiz_feedbackwizard\local\service\matching_config_service
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\matching_config_service;

/**
 * PHPUnit tests for matching configuration helpers.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class matching_config_service_test extends \advanced_testcase {
    /**
     * Test parsing CSV-style matching rules.
     *
     * @covers ::parse_csv_rules
     * @return void
     */
    public function test_parse_csv_rules(): void {
        $csv = "coursefield,operator,pattern,targettype,targetvalue
"
            . "shortname,contains,MATH,catscale,17
"
            . "fullname,regex,^Deutsch.*,course,COURSE-A";

        $rules = matching_config_service::parse_csv_rules($csv);

        $this->assertCount(2, $rules);
        $this->assertSame('shortname', $rules[0]['coursefield']);
        $this->assertSame('contains', $rules[0]['operator']);
        $this->assertSame('MATH', $rules[0]['pattern']);
        $this->assertSame('course', $rules[1]['targettype']);
    }

    /**
     * Test invalid regex detection.
     *
     * @covers ::has_invalid_regex
     * @return void
     */
    public function test_has_invalid_regex(): void {
        $this->assertFalse(matching_config_service::has_invalid_regex('^ABC.*'));
        $this->assertTrue(matching_config_service::has_invalid_regex('([abc'));
    }
}
