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
 * Contract tests between the wizard and local_catquiz.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\adapter\local_catquiz_adapter;
use block_catquiz_feedbackwizard\local\service\test_config_writer;

/**
 * Contract tests between the wizard and local_catquiz.
 *
 * A setting written under a key the engine never reads has no effect at all,
 * and nothing in an ordinary unit test notices: asserting that our own array
 * contains our own key passes just as happily for an invented name. This
 * happened once in this plugin — feedbackactioncoursetarget_N was written,
 * stored and never read by anybody.
 *
 * These tests therefore check the other side of the contract: every settings
 * key the writer emits must actually appear in the installed engine's source.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\service\test_config_writer
 */
final class engine_contract_test extends \advanced_testcase {
    /**
     * Return the engine source directory, or skip.
     *
     * @return string
     */
    protected function engine_dir(): string {
        global $CFG;

        if (!local_catquiz_adapter::is_available()) {
            $this->markTestSkipped('local_catquiz is not installed in this environment.');
        }

        $dir = $CFG->dirroot . '/local/catquiz';
        if (!is_dir($dir)) {
            $this->markTestSkipped('local_catquiz source not found at ' . $dir);
        }

        return $dir;
    }

    /**
     * Return every PHP source line of the engine as one blob.
     *
     * @param string $dir
     * @return string
     */
    protected function engine_source(string $dir): string {
        $source = '';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $source .= file_get_contents($file->getPathname());
            }
        }

        return $source;
    }

    /**
     * Every settings key the wizard writes must be read by the engine.
     *
     * @return void
     */
    public function test_every_written_key_exists_in_the_engine(): void {
        $this->resetAfterTest();
        $dir = $this->engine_dir();
        $source = $this->engine_source($dir);

        $scaleid = 7;
        $mapped = test_config_writer::apply_wizard_state([], [
            'mainscaleid' => $scaleid,
            'reportingstrategy' => 'main_only',
            'feedbackrangecount' => 1,
            'feedbacklabel_1' => 'Support',
            'feedbacklower_1' => -3.0,
            'feedbackupper_1' => 3.0,
            'feedbacktext_1' => 'Text',
            'feedbackactioncourseenabled_1' => 1,
            'feedbackactioncoursetarget_1' => [42],
            'feedbackactionmessage_1' => 1,
            'feedbackactiongroupenabled_1' => 1,
            'feedbackactiongrouptarget_1' => 'Support A',
        ]);

        // Our own state snapshot is ours to define, so it is exempt.
        unset($mapped['catquiz_wizard']);

        $missing = [];
        foreach (array_keys($mapped) as $key) {
            // Reduce "feedback_scaleid_limit_lower_7_1" to the literal prefix
            // the engine builds its key from.
            $prefix = preg_replace('/(_\d+)+$/', '_', $key);
            if ($prefix === $key) {
                $prefix = $key;
            }
            if (strpos($source, $prefix) === false) {
                $missing[] = $key . ' (searched for "' . $prefix . '")';
            }
        }

        $this->assertSame(
            [],
            $missing,
            "The engine's source contains no reference to these keys, so writing them has no effect:\n"
                . implode("\n", $missing)
        );
    }

    /**
     * The stored feedback text must be free of placeholders.
     *
     * local_catquiz displays it verbatim, so anything left here is shown to
     * the student as literal braces.
     *
     * @return void
     */
    public function test_stored_feedback_text_contains_no_placeholders(): void {
        global $DB;

        $this->resetAfterTest();
        $this->engine_dir();

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
            'componentid' => 4243,
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

        test_config_writer::write_to_test($testid, [
            'mainscaleid' => $scaleid,
            'reportingstrategy' => 'main_only',
            'feedbackrangecount' => 1,
            'feedbacklabel_1' => 'Needs support',
            'feedbacklower_1' => -3.0,
            'feedbackupper_1' => 3.0,
            'feedbacktext_1' => 'In {{test.name}} ({{course.fullname}}) you reached '
                . '{{result.ranklabel}} on {{result.scalename}}.',
        ]);

        $settings = json_decode((string)$DB->get_field('local_catquiz_tests', 'json', ['id' => $testid]), true);
        $stored = $settings['feedbackeditor_scaleid_' . $scaleid . '_1'];
        $stored = is_array($stored) ? ($stored['text'] ?? '') : (string)$stored;

        $this->assertStringNotContainsString('{{', $stored, 'a placeholder would be shown verbatim to the student');
        $this->assertSame(
            'In Placement test (Mechanics 1) you reached Needs support on Reading.',
            $stored
        );
    }
}
