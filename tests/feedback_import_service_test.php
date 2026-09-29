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
 * Tests for the feedback text import.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\adapter\local_catquiz_adapter;
use block_catquiz_feedbackwizard\local\service\feedback_import_service;
use block_catquiz_feedbackwizard\local\service\test_config_writer;

/**
 * Tests for the feedback text import.
 *
 * The decisive test is the last one: it follows an imported text all the way
 * into the key local_catquiz reads, with its placeholders resolved. Parsing
 * into an array proves nothing on its own.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\service\feedback_import_service
 */
final class feedback_import_service_test extends \advanced_testcase {
    /**
     * A document with a header row must parse into its data rows.
     *
     * @return void
     */
    public function test_parse_skips_the_header(): void {
        $rows = feedback_import_service::parse(feedback_import_service::get_template_example());

        $this->assertCount(2, $rows);
        $this->assertSame(1, $rows[0]['range']);
        $this->assertSame('Needs support', $rows[0]['label']);
        $this->assertStringContainsString('{{result.scalename}}', $rows[0]['text']);
    }

    /**
     * A text containing the separator must survive when quoted.
     *
     * @return void
     */
    public function test_parse_handles_quoted_separators(): void {
        $rows = feedback_import_service::parse('1,Label,"First, second and third."');

        $this->assertCount(1, $rows);
        $this->assertSame('First, second and third.', $rows[0]['text']);
    }

    /**
     * A missing range column must fall back to the row order.
     *
     * @return void
     */
    public function test_parse_numbers_rows_without_a_range(): void {
        $rows = feedback_import_service::parse(",First,Text one\n,Second,Text two");

        $this->assertSame([1, 2], array_column($rows, 'range'));
    }

    /**
     * A short row must be reported, not silently dropped.
     *
     * @return void
     */
    public function test_short_rows_are_reported(): void {
        $this->resetAfterTest();

        $rows = feedback_import_service::parse("1,OnlyTwoColumns");
        feedback_import_service::apply_to_state(['feedbackrangecount' => 2], $rows);

        $this->assertNotEmpty(feedback_import_service::get_messages());
    }

    /**
     * A row beyond the configured ranges must be reported and skipped.
     *
     * @return void
     */
    public function test_rows_beyond_the_range_count_are_skipped(): void {
        $this->resetAfterTest();

        $state = feedback_import_service::apply_to_state(
            ['feedbackrangecount' => 2],
            feedback_import_service::parse("1,A,Text A\n5,E,Text E")
        );

        $this->assertSame('Text A', $state['feedbacktext_1']);
        $this->assertArrayNotHasKey('feedbacktext_5', $state);
        $this->assertNotEmpty(array_filter(
            feedback_import_service::get_messages(),
            static function (string $message): bool {
                return strpos($message, 'range 5') !== false;
            }
        ));
    }

    /**
     * An unknown placeholder must be reported, because it would otherwise be
     * shown to the student as literal braces.
     *
     * @return void
     */
    public function test_unknown_placeholders_are_reported(): void {
        $this->resetAfterTest();

        feedback_import_service::apply_to_state(
            ['feedbackrangecount' => 1],
            feedback_import_service::parse('1,A,Hello {{user.email}}')
        );

        $found = false;
        foreach (feedback_import_service::get_messages() as $message) {
            if (strpos($message, 'user.email') !== false) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'an unknown placeholder must be reported');
    }

    /**
     * An empty label must keep the one already configured.
     *
     * @return void
     */
    public function test_empty_label_keeps_the_existing_one(): void {
        $this->resetAfterTest();

        $state = feedback_import_service::apply_to_state(
            ['feedbackrangecount' => 1, 'feedbacklabel_1' => 'Existing'],
            feedback_import_service::parse('1,,New text')
        );

        $this->assertSame('Existing', $state['feedbacklabel_1']);
        $this->assertSame('New text', $state['feedbacktext_1']);
    }

    /**
     * An imported text must reach the engine with its placeholders resolved.
     *
     * This is the test that measures the feature. Everything above only shows
     * that an array was built.
     *
     * @return void
     */
    public function test_imported_text_reaches_the_engine_resolved(): void {
        global $DB;

        $this->resetAfterTest();

        if (!local_catquiz_adapter::is_available()) {
            $this->markTestSkipped('local_catquiz is not installed in this environment.');
        }

        $course = $this->getDataGenerator()->create_course(['fullname' => 'Mechanics 1']);
        $scaleid = (int)\local_catquiz\data\dataapi::create_catscale(
            new \local_catquiz\data\catscale_structure([
                'name' => 'Reading',
                'description' => '',
                'parentid' => 0,
                'minscalevalue' => -3.0,
                'maxscalevalue' => 3.0,
                'contextid' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
            ])
        );

        $testid = (int)$DB->insert_record('local_catquiz_tests', (object)[
            'componentid' => 4248,
            'component' => 'mod_adaptivequiz',
            'catscaleid' => $scaleid,
            'contextid' => 0,
            'courseid' => (int)$course->id,
            'name' => 'Placement test',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'json' => json_encode([]),
            'status' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $state = [
            'mainscaleid' => $scaleid,
            'reportingstrategy' => 'main_only',
            'feedbackrangecount' => 1,
            'feedbacklower_1' => -3.0,
            'feedbackupper_1' => 3.0,
        ];

        $state = feedback_import_service::apply_to_state(
            $state,
            feedback_import_service::parse(
                '1,Needs support,"In {{test.name}} ({{course.fullname}}) you reached '
                    . '{{result.ranklabel}} on {{result.scalename}}."'
            )
        );

        test_config_writer::write_to_test($testid, $state);

        $settings = json_decode((string)$DB->get_field('local_catquiz_tests', 'json', ['id' => $testid]), true);
        $stored = $settings['feedbackeditor_scaleid_' . $scaleid . '_1'];
        $stored = is_array($stored) ? ($stored['text'] ?? '') : (string)$stored;

        $this->assertSame(
            'In Placement test (Mechanics 1) you reached Needs support on Reading.',
            $stored,
            'the imported text must arrive at the engine with every placeholder resolved'
        );
    }
}
