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
 * Unit tests for the settings pattern import and export.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\pattern_export_service;
use block_catquiz_feedbackwizard\local\service\pattern_import_service;

/**
 * Unit tests for the settings pattern import and export.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\service\pattern_export_service
 * @covers \block_catquiz_feedbackwizard\local\service\pattern_import_service
 */
final class pattern_service_test extends \advanced_testcase {
    /**
     * Return a representative wizard state.
     *
     * @return array
     */
    protected function build_state(): array {
        return [
            'scenario' => 'placement',
            'testgoal' => 'placement',
            'precisionmode' => 'high',
            'minquestioncount' => 5,
            'questioncount' => 20,
            'questioncountpersubscale' => 3,
            'timelimitenabled' => 1,
            'timelimitminutes' => 45,
            'completionenabled' => 1,
            'mainscaleid' => 0,
            'subscaleids' => [],
            'reportingstrategy' => 'main_only',
            'feedbackrangecount' => 2,
            'feedbacklabel_1' => 'Needs support',
            'feedbacklower_1' => -3.0,
            'feedbackupper_1' => 0.0,
            'feedbacktext_1' => 'Please review {{result.scalename}}.',
            'feedbacktemplateformat_1' => 'mustache',
            'feedbacklabel_2' => 'Ready',
            'feedbacklower_2' => 0.0,
            'feedbackupper_2' => 3.0,
            'feedbacktext_2' => 'Well done.',
            'feedbacktemplateformat_2' => 'plain',
            'matchingmode' => 'rule',
            'matchingcategoryid' => 0,
            'matchingcoursefield' => 'shortname',
            'matchingoperator' => 'startswith',
            'matchingpattern' => 'REMEDIAL',
            'matchingtargettype' => 'course',
            'matchingtargetvalue' => 'REMEDIAL-01',
            'matchingcsv' => '',
        ];
    }

    /**
     * An exported pattern must carry the format marker and version.
     *
     * @return void
     */
    public function test_export_has_format_envelope(): void {
        $this->resetAfterTest();

        $pattern = pattern_export_service::build_pattern($this->build_state(), 'my-pattern');

        $this->assertSame(pattern_export_service::FORMAT, $pattern['format']);
        $this->assertSame(pattern_export_service::VERSION, $pattern['version']);
        $this->assertSame('my-pattern', $pattern['meta']['name']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $pattern['exported_at']);
    }

    /**
     * A pattern must not carry instance or user references.
     *
     * @return void
     */
    public function test_export_drops_instance_references(): void {
        $this->resetAfterTest();

        $state = $this->build_state();
        $state['selectedtest'] = 42;
        $state['testid'] = 42;
        $state['draftid'] = 7;
        $state['courseid'] = 13;
        $state['sourcetestid'] = 99;

        $json = pattern_export_service::to_json(pattern_export_service::build_pattern($state));

        foreach (['selectedtest', 'draftid', 'courseid', 'sourcetestid'] as $key) {
            $this->assertStringNotContainsString($key, $json);
        }
    }

    /**
     * Exporting and importing again must preserve the configuration.
     *
     * @return void
     */
    public function test_roundtrip_preserves_configuration(): void {
        $this->resetAfterTest();

        $state = $this->build_state();
        $json = pattern_export_service::to_json(pattern_export_service::build_pattern($state));
        $restored = pattern_import_service::to_wizard_state(pattern_import_service::parse($json));

        $compare = [
            'scenario', 'testgoal', 'precisionmode', 'minquestioncount', 'questioncount',
            'questioncountpersubscale', 'timelimitenabled', 'timelimitminutes', 'completionenabled',
            'reportingstrategy', 'feedbackrangecount',
            'feedbacklabel_1', 'feedbacktext_1', 'feedbacktemplateformat_1',
            'feedbacklabel_2', 'feedbacktext_2', 'feedbacktemplateformat_2',
            'matchingmode', 'matchingcoursefield', 'matchingoperator',
            'matchingpattern', 'matchingtargettype', 'matchingtargetvalue',
        ];

        foreach ($compare as $key) {
            $this->assertEquals($state[$key], $restored[$key], "Mismatch for {$key}");
        }

        $this->assertEqualsWithDelta(-3.0, (float)$restored['feedbacklower_1'], 0.001);
        $this->assertEqualsWithDelta(3.0, (float)$restored['feedbackupper_2'], 0.001);
    }

    /**
     * With the setting off, feedback texts must not leave the site.
     *
     * @return void
     */
    public function test_export_can_omit_feedback_texts(): void {
        $this->resetAfterTest();

        set_config('pattern_export_include_feedback_texts', 0, 'block_catquiz_feedbackwizard');

        $pattern = pattern_export_service::build_pattern($this->build_state());
        $json = pattern_export_service::to_json($pattern);

        $this->assertStringNotContainsString('Well done.', $json);
        $this->assertEquals(0, $pattern['feedback']['includes_texts']);

        $warnings = [];
        pattern_import_service::to_wizard_state(pattern_import_service::parse($json));
        $warnings = pattern_import_service::get_warnings();
        $this->assertNotEmpty($warnings);
    }

    /**
     * Malformed input must be rejected with a clear message, not a fatal error.
     *
     * @return void
     */
    public function test_parse_rejects_invalid_json(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        pattern_import_service::parse('{not json');
    }

    /**
     * A foreign JSON document must be rejected.
     *
     * @return void
     */
    public function test_parse_rejects_foreign_format(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        pattern_import_service::parse(json_encode(['format' => 'something-else', 'version' => 1]));
    }

    /**
     * A newer format version must be refused rather than half understood.
     *
     * @return void
     */
    public function test_parse_rejects_future_version(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        pattern_import_service::parse(json_encode([
            'format' => pattern_export_service::FORMAT,
            'version' => pattern_export_service::VERSION + 1,
            'settings' => [],
            'feedback' => [],
        ]));
    }

    /**
     * A pattern without its mandatory sections must be refused.
     *
     * @return void
     */
    public function test_parse_requires_sections(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        pattern_import_service::parse(json_encode([
            'format' => pattern_export_service::FORMAT,
            'version' => pattern_export_service::VERSION,
            'settings' => [],
        ]));
    }

    /**
     * Scale ids that do not exist here must be dropped with a warning.
     *
     * @return void
     */
    public function test_unknown_scales_are_dropped_with_warning(): void {
        $this->resetAfterTest();

        $pattern = [
            'format' => pattern_export_service::FORMAT,
            'version' => pattern_export_service::VERSION,
            'settings' => ['questioncount' => 10],
            'scales' => [
                'main' => ['id' => 999999, 'name' => 'Ghost scale'],
                'subscales' => [['id' => 999998, 'name' => 'Ghost subscale']],
            ],
            'feedback' => ['rangecount' => 2, 'includes_texts' => 1, 'ranges' => []],
        ];

        $state = pattern_import_service::to_wizard_state($pattern);

        $this->assertEquals(0, $state['mainscaleid']);
        $this->assertSame([], $state['subscaleids']);
        $this->assertNotEmpty(pattern_import_service::get_warnings());
    }

    /**
     * Untrusted values must be normalised on the way in.
     *
     * @return void
     */
    public function test_import_normalises_untrusted_values(): void {
        $this->resetAfterTest();

        $state = pattern_import_service::to_wizard_state([
            'format' => pattern_export_service::FORMAT,
            'version' => pattern_export_service::VERSION,
            'settings' => [
                'precisionmode' => 'ludicrous',
                'questioncount' => -5,
            ],
            'feedback' => ['rangecount' => 99, 'includes_texts' => 1, 'ranges' => []],
            'routing' => ['mode' => 'nonsense', 'operator' => 'nonsense'],
        ]);

        $this->assertEquals('medium', $state['precisionmode']);
        $this->assertEquals(0, $state['questioncount']);
        $this->assertEquals(3, $state['feedbackrangecount']);
        $this->assertEquals('none', $state['matchingmode']);
        $this->assertEquals('contains', $state['matchingoperator']);
    }
}
