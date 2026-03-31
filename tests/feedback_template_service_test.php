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
     * Test rendering a preview from a supported token map.
     *
     * @covers ::render_preview
     * @return void
     */
    public function test_render_preview(): void {
        $preview = feedback_template_service::render_preview(
            'Hello {{user.firstname}}, you are in {{result.ranklabel}}.',
            'mustache'
        );

        $this->assertSame('Hello Alex, you are in Support.', $preview);
    }

    /**
     * Test normalising the template format selector.
     *
     * @covers ::normalise_template_format
     * @return void
     */
    public function test_normalise_template_format(): void {
        $this->assertSame('plain', feedback_template_service::normalise_template_format('plain'));
        $this->assertSame('mustache', feedback_template_service::normalise_template_format('markdown'));
    }
}
