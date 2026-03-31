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
 * PHPUnit tests for the feedback template service.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \block_catquiz_feedbackwizard\local\service\feedback_template_service
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\feedback_template_service;

/**
 * PHPUnit tests for the feedback template service.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    test
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class feedback_template_service_test extends \advanced_testcase {
    /**
     * Test extracting and validating tokens.
     *
     * @covers ::extract_tokens
     * @covers ::get_unknown_tokens
     * @return void
     */
    public function test_extract_tokens_and_unknown_tokens(): void {
        $text = 'Hello {{ result.ranklabel }} {{unknown.token}}';

        $this->assertSame(['result.ranklabel', 'unknown.token'], feedback_template_service::extract_tokens($text));
        $this->assertSame(['unknown.token'], feedback_template_service::get_unknown_tokens($text));
    }

    /**
     * Test simple preview rendering.
     *
     * @covers ::normalise_template_format
     * @covers ::render_preview
     * @return void
     */
    public function test_render_preview(): void {
        $mustache = 'Status: {{ result.ranklabel }}';

        $this->assertSame('Status: Support', feedback_template_service::render_preview($mustache, 'mustache'));
        $this->assertSame('Plain text', feedback_template_service::render_preview('Plain text', 'plain'));
        $this->assertSame('mustache', feedback_template_service::normalise_template_format('unsupported'));
    }
}
