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

    /**
     * Saving must invalidate the engine's quiz settings caches.
     *
     * This is one of the two reasons the adapter exists. A direct
     * $DB->update_record() writes the same row but skips the purge in
     * testenvironment::save_or_update(), so the engine keeps serving the old
     * settings until the cache expires — a failure nobody sees.
     *
     * @return void
     */
    public function test_save_purges_the_engine_cache(): void {
        global $DB;

        $this->resetAfterTest();

        if (!local_catquiz_adapter::is_available()) {
            $this->markTestSkipped('local_catquiz is not installed in this environment.');
        }

        $testid = (int)$DB->insert_record('local_catquiz_tests', (object)[
            'componentid' => 4244,
            'component' => 'mod_adaptivequiz',
            'catscaleid' => 0,
            'contextid' => 0,
            'courseid' => 0,
            'name' => 'Cache test',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'json' => json_encode([]),
            'status' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Both caches declare changesinquizsettings as an invalidation event.
        $scales = \cache::make('local_catquiz', 'cattest_active_scales');
        $numitems = \cache::make('local_catquiz', 'catscales_num_items');
        $scales->set('probe', 'stale');
        $numitems->set('probe', 'stale');
        $this->assertSame('stale', $scales->get('probe'), 'precondition: cache is populated');
        $this->assertSame('stale', $numitems->get('probe'), 'precondition: cache is populated');

        local_catquiz_adapter::save_test_configuration($testid, ['catquiz_catscales' => 1]);

        $this->assertFalse(
            $scales->get('probe'),
            'cattest_active_scales must be invalidated when settings are saved'
        );
        $this->assertFalse(
            $numitems->get('probe'),
            'catscales_num_items must be invalidated when settings are saved'
        );
    }

    /**
     * The engine itself still does not move the context — pinned on purpose.
     *
     * This drives local_catquiz directly, bypassing our adapter, so it measures
     * the engine's behaviour and nothing else. It fails once the engine is
     * fixed, which is the point: the workaround in the adapter must not outlive
     * its cause. local_catquiz issue #127, see
     * docs/design/issue-catquiz-contextid-on-scale-change.md.
     *
     * @return void
     */
    public function test_engine_still_ignores_the_scale_change_defect(): void {
        global $DB;

        $this->resetAfterTest();
        [$first, $second, $testid] = $this->prepare_context_scenario(4246);

        $class = local_catquiz_adapter::TESTENVIRONMENT_CLASS;
        $environment = new $class((object)[
            'id' => $testid,
            'catscaleid' => (int)$second->id,
            'contextid' => (int)$second->contextid,
            'json' => json_encode(['catquiz_catscales' => (int)$second->id]),
        ]);
        $environment->save_or_update();

        $stored = $DB->get_record('local_catquiz_tests', ['id' => $testid], 'id, contextid');

        $this->assertEquals(
            (int)$first->contextid,
            (int)$stored->contextid,
            'If this fails, local_catquiz now moves the context itself. Remove '
                . 'local_catquiz_adapter::correct_context_after_scale_change() and this test.'
        );
    }

    /**
     * Going through the adapter must leave the context on the new scale.
     *
     * @return void
     */
    public function test_adapter_moves_the_context_to_the_new_scale(): void {
        global $DB;

        $this->resetAfterTest();
        [, $second, $testid] = $this->prepare_context_scenario(4247);

        local_catquiz_adapter::save_test_configuration(
            $testid,
            ['catquiz_catscales' => (int)$second->id],
            (int)$second->id
        );

        $stored = $DB->get_record('local_catquiz_tests', ['id' => $testid], 'id, catscaleid, contextid');

        $this->assertEquals((int)$second->id, (int)$stored->catscaleid);
        $this->assertEquals(
            (int)$second->contextid,
            (int)$stored->contextid,
            'after a scale change the test must point at the new scale context'
        );
    }

    /**
     * Build two scales in different contexts plus a test on the first one.
     *
     * @param int $componentid
     * @return array [$first, $second, $testid]
     */
    protected function prepare_context_scenario(int $componentid): array {
        global $DB;

        if (!local_catquiz_adapter::is_available()) {
            $this->markTestSkipped('local_catquiz is not installed in this environment.');
        }

        $makescale = function (string $name) use ($DB): \stdClass {
            $id = \local_catquiz\data\dataapi::create_catscale(
                new \local_catquiz\data\catscale_structure([
                    'name' => $name,
                    'description' => '',
                    'parentid' => 0,
                    'minscalevalue' => -3.0,
                    'maxscalevalue' => 3.0,
                    'contextid' => 0,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ])
            );
            return $DB->get_record('local_catquiz_catscales', ['id' => $id]);
        };

        $first = $makescale('Context scale A ' . $componentid);
        $second = $makescale('Context scale B ' . $componentid);

        if ((int)$first->contextid === (int)$second->contextid) {
            $this->markTestSkipped('Both scales share a context, so there is nothing to observe.');
        }

        $testid = (int)$DB->insert_record('local_catquiz_tests', (object)[
            'componentid' => $componentid,
            'component' => 'mod_adaptivequiz',
            'catscaleid' => (int)$first->id,
            'contextid' => (int)$first->contextid,
            'courseid' => 0,
            'name' => 'Context test',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'json' => json_encode([]),
            'status' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return [$first, $second, $testid];
    }
}
