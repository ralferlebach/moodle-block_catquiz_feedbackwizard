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
 * Unit tests for the local_catquiz adapter.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\adapter\local_catquiz_adapter;

/**
 * Unit tests for the local_catquiz adapter.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\adapter\local_catquiz_adapter
 */
final class local_catquiz_adapter_test extends \advanced_testcase {
    /**
     * The adapter must report availability based on the local_catquiz API class.
     *
     * @return void
     */
    public function test_is_available_matches_local_catquiz_presence(): void {
        $this->resetAfterTest();

        $this->assertEquals(
            class_exists(local_catquiz_adapter::TESTENVIRONMENT_CLASS),
            local_catquiz_adapter::is_available()
        );
    }

    /**
     * Saving without a valid test id is a programming error, not a user error.
     *
     * @return void
     */
    public function test_save_requires_a_valid_test_id(): void {
        $this->resetAfterTest();

        $this->expectException(\coding_exception::class);
        local_catquiz_adapter::save_test_configuration(0, ['catscaleid' => 1]);
    }

    /**
     * A configuration written through the adapter must come back from the database.
     *
     * @return void
     */
    public function test_save_test_configuration_persists_json(): void {
        global $DB;

        $this->resetAfterTest();

        if (!local_catquiz_adapter::is_available()) {
            $this->markTestSkipped('local_catquiz is not installed in this environment.');
        }

        $testid = (int)$DB->insert_record('local_catquiz_tests', (object)[
            'componentid' => 1,
            'component' => 'mod_adaptivequiz',
            'catscaleid' => 0,
            'name' => 'Adapter test',
            'json' => json_encode(['catquiz_catscales' => 0]),
            'courseid' => 0,
            'status' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        local_catquiz_adapter::save_test_configuration($testid, ['catquiz_catscales' => 42]);

        $stored = json_decode((string)$DB->get_field('local_catquiz_tests', 'json', ['id' => $testid]), true);
        $this->assertEquals(42, $stored['catquiz_catscales']);
    }
}
