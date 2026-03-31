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
 * Normalises local_catquiz test JSON into wizard defaults.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

use block_catquiz_feedbackwizard\catquiz_data;

/**
 * Normalises local_catquiz test JSON into wizard defaults.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_config_normalizer {
    /** @var int Minimum supported fixed feedback ranges. */
    const MIN_FEEDBACK_RANGES = 2;

    /** @var int Maximum supported fixed feedback ranges. */
    const MAX_FEEDBACK_RANGES = 5;

    /**
     * Build wizard defaults from a local_catquiz_tests record.
     *
     * @param \stdClass $testrecord
     * @param string $mode
     * @param int $sourcetestid
     * @return array
     */
    public static function build_wizard_defaults(\stdClass $testrecord, string $mode = 'edit', int $sourcetestid = 0): array {
        $jsondata = self::decode_json((string)($testrecord->json ?? ''));
        $wizarddata = self::extract_existing_wizard_state($jsondata);
        $mainscaleid = self::extract_mainscaleid($testrecord, $jsondata, $wizarddata);
        $subscaleids = self::extract_subscale_ids($jsondata, $wizarddata);

        $defaults = [
            'selectedtest' => (int)$testrecord->id,
            'testid' => (int)$testrecord->id,
            'wizardmode' => $mode,
            'sourcetestid' => $sourcetestid,
            'clonescope' => (string)($wizarddata['clonescope'] ?? 'full'),
            'scenario' => (string)($wizarddata['scenario'] ?? ''),
            'mainscaleid' => $mainscaleid,
            'subscaleids' => $subscaleids,
            'minquestioncount' => self::extract_min_question_count($jsondata, $wizarddata),
            'questioncount' => self::extract_question_count($jsondata, $wizarddata),
            'questioncountpersubscale' => self::extract_question_count_per_subscale($jsondata, $wizarddata),
            'timelimitenabled' => self::extract_time_limit_enabled($jsondata, $wizarddata),
            'timelimitminutes' => self::extract_time_limit_minutes($jsondata, $wizarddata),
            'precisionmode' => self::extract_precision_mode($jsondata, $wizarddata),
            'testgoal' => self::extract_test_goal($wizarddata),
            'completionenabled' => self::extract_completion_enabled($jsondata, $wizarddata),
        ];

        return array_merge($defaults, self::build_feedback_defaults($jsondata, $wizarddata, $mainscaleid, $subscaleids));
    }

    /**
     * Merge clone defaults into a target wizard state.
     *
     * @param array $targetdefaults
     * @param array $sourcedefaults
     * @param string $scope
     * @return array
     */
    public static function merge_clone_defaults(array $targetdefaults, array $sourcedefaults, string $scope): array {
        if ($scope === 'full') {
            return array_merge($targetdefaults, $sourcedefaults);
        }

        $result = $targetdefaults;
        if ($scope === 'structure') {
            foreach (['mainscaleid', 'subscaleids'] as $field) {
                if (array_key_exists($field, $sourcedefaults)) {
                    $result[$field] = $sourcedefaults[$field];
                }
            }
            return $result;
        }

        if ($scope === 'conditions') {
            foreach (
                [
                    'minquestioncount',
                    'questioncount',
                    'questioncountpersubscale',
                    'timelimitenabled',
                    'timelimitminutes',
                    'precisionmode',
                    'testgoal',
                    'completionenabled',
                ] as $field
            ) {
                if (array_key_exists($field, $sourcedefaults)) {
                    $result[$field] = $sourcedefaults[$field];
                }
            }
        }

        return $result;
    }

    /**
     * Decode a JSON payload to an array.
     *
     * @param string $json
     * @return array
     */
    public static function decode_json(string $json): array {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Extract any wizard state already stored in the JSON.
     *
     * @param array $jsondata
     * @return array
     */
    public static function extract_existing_wizard_state(array $jsondata): array {
        if (!empty($jsondata['catquiz_wizard']) && is_array($jsondata['catquiz_wizard'])) {
            return $jsondata['catquiz_wizard'];
        }
        return [];
    }

    /**
     * Extract main scale id from the JSON or record.
     *
     * @param \stdClass $testrecord
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_mainscaleid(\stdClass $testrecord, array $jsondata, array $wizarddata = []): int {
        if (!empty($wizarddata['mainscaleid'])) {
            return (int)$wizarddata['mainscaleid'];
        }
        if (!empty($jsondata['catquiz_catscales'])) {
            return (int)$jsondata['catquiz_catscales'];
        }
        if (!empty($jsondata['catscaleid'])) {
            return (int)$jsondata['catscaleid'];
        }
        return (int)($testrecord->catscaleid ?? 0);
    }

    /**
     * Extract selected subscale ids.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return array
     */
    public static function extract_subscale_ids(array $jsondata, array $wizarddata = []): array {
        if (!empty($wizarddata['subscaleids'])) {
            return array_values(array_map('intval', (array)$wizarddata['subscaleids']));
        }

        $subscaleids = [];
        foreach ($jsondata as $key => $value) {
            if (strpos($key, 'catquiz_subscalecheckbox_') !== 0 || empty($value)) {
                continue;
            }
            $subscaleid = (int)substr($key, strlen('catquiz_subscalecheckbox_'));
            if ($subscaleid > 0) {
                $subscaleids[] = $subscaleid;
            }
        }

        $subscaleids = array_values(array_unique($subscaleids));
        sort($subscaleids);
        return $subscaleids;
    }

    /**
     * Extract minimum question count.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_min_question_count(array $jsondata, array $wizarddata = []): int {
        if (isset($wizarddata['minquestioncount'])) {
            return (int)$wizarddata['minquestioncount'];
        }
        return (int)($jsondata['maxquestionsgroup']['catquiz_minquestions'] ?? 0);
    }

    /**
     * Extract maximum question count.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_question_count(array $jsondata, array $wizarddata = []): int {
        if (!empty($wizarddata['questioncount'])) {
            return (int)$wizarddata['questioncount'];
        }
        if (!empty($jsondata['maxquestionsgroup']['catquiz_maxquestions'])) {
            return (int)$jsondata['maxquestionsgroup']['catquiz_maxquestions'];
        }
        if (!empty($jsondata['catquiz_maxquestions'])) {
            return (int)$jsondata['catquiz_maxquestions'];
        }
        return 0;
    }

    /**
     * Extract question count per subscale.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_question_count_per_subscale(array $jsondata, array $wizarddata = []): int {
        if (isset($wizarddata['questioncountpersubscale'])) {
            return (int)$wizarddata['questioncountpersubscale'];
        }
        return (int)($jsondata['maxquestionsscalegroup']['catquiz_maxquestionspersubscale'] ?? 0);
    }

    /**
     * Extract whether a time limit is enabled.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_time_limit_enabled(array $jsondata, array $wizarddata = []): int {
        if (isset($wizarddata['timelimitenabled'])) {
            return !empty($wizarddata['timelimitenabled']) ? 1 : 0;
        }
        return !empty($jsondata['catquiz_includetimelimit']) ? 1 : 0;
    }

    /**
     * Extract time limit in minutes.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_time_limit_minutes(array $jsondata, array $wizarddata = []): int {
        if (!empty($wizarddata['timelimitminutes'])) {
            return (int)$wizarddata['timelimitminutes'];
        }

        $group = $jsondata['catquiz_timelimitgroup'] ?? [];
        $time = (int)($group['catquiz_maxtimeperattempt'] ?? $jsondata['catquiz_maxtimeperattempt'] ?? 0);
        $unit = (string)($group['catquiz_timeselect_attempt'] ?? 'min');
        if ($time < 1) {
            return 0;
        }

        switch ($unit) {
            case 'h':
                return $time * 60;
            case 'sec':
                return (int)floor($time / 60);
            default:
                return $time;
        }
    }

    /**
     * Extract a simple precision mode from the JSON payload.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return string
     */
    public static function extract_precision_mode(array $jsondata, array $wizarddata = []): string {
        if (!empty($wizarddata['precisionmode'])) {
            return (string)$wizarddata['precisionmode'];
        }

        $minimum = $jsondata['catquiz_standarderrorgroup']['catquiz_standarderror_min'] ?? null;
        if ($minimum === null || $minimum === '') {
            return 'medium';
        }

        $minimum = (float)$minimum;
        if ($minimum <= 0.25) {
            return 'high';
        }
        if ($minimum <= 0.5) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Extract the configured test goal from wizard state.
     *
     * @param array $wizarddata
     * @return string
     */
    public static function extract_test_goal(array $wizarddata = []): string {
        return (string)($wizarddata['testgoal'] ?? '');
    }

    /**
     * Extract whether activity completion should be enabled.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_completion_enabled(array $jsondata, array $wizarddata = []): int {
        if (isset($wizarddata['completionenabled'])) {
            return !empty($wizarddata['completionenabled']) ? 1 : 0;
        }
        return !empty($jsondata['completion']) ? 1 : 0;
    }

    /**
     * Build flattened feedback defaults for the wizard form.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @param int $mainscaleid
     * @param array $subscaleids
     * @return array
     */
    public static function build_feedback_defaults(
        array $jsondata,
        array $wizarddata,
        int $mainscaleid,
        array $subscaleids
    ): array {
        $range = catquiz_data::get_scale_range($mainscaleid);
        $rangecount = self::normalise_feedback_range_count(
            (int)($wizarddata['feedbackrangecount'] ?? $jsondata['numberoffeedbackoptionsselect'] ?? 0)
        );
        $reportingstrategy = self::extract_reporting_strategy($jsondata, $wizarddata, $mainscaleid, $subscaleids);
        $ranges = self::extract_feedback_ranges($jsondata, $wizarddata, $mainscaleid, $rangecount, $range['min'], $range['max']);

        $defaults = [
            'feedbackmode' => self::normalise_feedback_mode((string)($wizarddata['feedbackmode'] ?? 'fixed')),
            'feedbackdisplaymode' => self::normalise_feedback_display_mode(
                (string)($wizarddata['feedbackdisplaymode'] ?? 'text_only')
            ),
            'feedbackvariablepreset' => self::normalise_feedback_variable_preset(
                (string)($wizarddata['feedbackvariablepreset'] ?? 'equal')
            ),
            'feedbackcsvranges' => (string)($wizarddata['feedbackcsvranges'] ?? ''),
            'feedbackrangecount' => $rangecount,
            'reportingstrategy' => $reportingstrategy,
        ];
        return array_merge($defaults, self::flatten_feedback_ranges($ranges));
    }

    /**
     * Build feedback defaults from the current wizard state.
     *
     * @param array $state
     * @param float $minimum
     * @param float $maximum
     * @return array
     */
    public static function build_feedback_defaults_from_wizard_state(array $state, float $minimum, float $maximum): array {
        $rangecount = self::normalise_feedback_range_count((int)($state['feedbackrangecount'] ?? 0));
        $ranges = self::extract_feedback_ranges_from_state($state, $rangecount, $minimum, $maximum);
        if (empty($ranges)) {
            $ranges = self::build_default_feedback_ranges($rangecount, $minimum, $maximum);
        }
        $defaults = [
            'feedbackmode' => self::normalise_feedback_mode((string)($state['feedbackmode'] ?? 'fixed')),
            'feedbackdisplaymode' => self::normalise_feedback_display_mode(
                (string)($state['feedbackdisplaymode'] ?? 'text_only')
            ),
            'feedbackvariablepreset' => self::normalise_feedback_variable_preset(
                (string)($state['feedbackvariablepreset'] ?? 'equal')
            ),
            'feedbackcsvranges' => (string)($state['feedbackcsvranges'] ?? ''),
            'feedbackrangecount' => $rangecount,
            'reportingstrategy' => (string)($state['reportingstrategy'] ?? 'main_only'),
        ];
        return array_merge($defaults, self::flatten_feedback_ranges($ranges));
    }

    /**
     * Normalise the configured feedback range count.
     *
     * @param int $rangecount
     * @return int
     */
    public static function normalise_feedback_range_count(int $rangecount): int {
        if ($rangecount < self::MIN_FEEDBACK_RANGES || $rangecount > self::MAX_FEEDBACK_RANGES) {
            return 3;
        }
        return $rangecount;
    }


    /**
     * Normalise the configured feedback mode.
     *
     * @param string $mode
     * @return string
     */
    public static function normalise_feedback_mode(string $mode): string {
        if (!in_array($mode, ['fixed', 'variable', 'csv'], true)) {
            return 'fixed';
        }
        return $mode;
    }

    /**
     * Normalise the configured feedback display mode.
     *
     * @param string $mode
     * @return string
     */
    public static function normalise_feedback_display_mode(string $mode): string {
        if (!in_array($mode, ['text_only', 'text_and_graphic', 'text_and_scores'], true)) {
            return 'text_only';
        }
        return $mode;
    }

    /**
     * Normalise the configured variable range preset.
     *
     * @param string $preset
     * @return string
     */
    public static function normalise_feedback_variable_preset(string $preset): string {
        if (!in_array($preset, ['equal', 'focus_low', 'focus_high'], true)) {
            return 'equal';
        }
        return $preset;
    }

    /**
     * Extract feedback ranges from the wizard state.
     *
     * @param array $state
     * @param int $rangecount
     * @param float $minimum
     * @param float $maximum
     * @return array
     */
    public static function extract_feedback_ranges_from_state(
        array $state,
        int $rangecount,
        float $minimum,
        float $maximum
    ): array {
        $feedbackmode = self::normalise_feedback_mode((string)($state['feedbackmode'] ?? 'fixed'));

        if (!empty($state['feedbackranges']) && is_array($state['feedbackranges'])) {
            $ranges = [];
            foreach ((array)$state['feedbackranges'] as $range) {
                $ranges[] = [
                    'label' => (string)($range['label'] ?? ''),
                    'lower' => (isset($range['lower']) && $range['lower'] !== '') ? (float)$range['lower'] : null,
                    'upper' => (isset($range['upper']) && $range['upper'] !== '') ? (float)$range['upper'] : null,
                    'text' => (string)($range['text'] ?? ''),
                    'templateformat' => (string)($range['templateformat'] ?? 'mustache'),
                    'actioncourseenabled' => !empty($range['actioncourseenabled']) ? 1 : 0,
                    'actioncoursetarget' => (string)($range['actioncoursetarget'] ?? ''),
                    'actiongroupenabled' => !empty($range['actiongroupenabled']) ? 1 : 0,
                    'actiongrouptarget' => (string)($range['actiongrouptarget'] ?? ''),
                ];
            }
            if (!empty($ranges)) {
                return $ranges;
            }
        }

        if ($feedbackmode === 'csv') {
            $ranges = self::parse_csv_feedback_ranges((string)($state['feedbackcsvranges'] ?? ''));
            if (!empty($ranges)) {
                return self::apply_state_overrides_to_ranges($ranges, $state, $minimum, $maximum);
            }
        }

        if ($feedbackmode === 'variable') {
            $ranges = self::build_variable_feedback_ranges(
                $rangecount,
                $minimum,
                $maximum,
                self::normalise_feedback_variable_preset((string)($state['feedbackvariablepreset'] ?? 'equal'))
            );
            return self::apply_state_overrides_to_ranges($ranges, $state, $minimum, $maximum);
        }

        $ranges = [];
        for ($index = 1; $index <= $rangecount; $index++) {
            $ranges[] = self::build_feedback_range_from_state($state, $index);
        }

        if (self::has_non_empty_feedback_ranges($ranges)) {
            return self::merge_feedback_ranges_with_defaults($ranges, $minimum, $maximum);
        }

        return [];
    }

    /**
     * Extract the reporting strategy.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @param int $mainscaleid
     * @param array $subscaleids
     * @return string
     */
    public static function extract_reporting_strategy(
        array $jsondata,
        array $wizarddata,
        int $mainscaleid,
        array $subscaleids
    ): string {
        if (!empty($wizarddata['reportingstrategy'])) {
            return (string)$wizarddata['reportingstrategy'];
        }

        $subscaleids = array_values(array_unique(array_map('intval', $subscaleids)));
        sort($subscaleids);

        if ($mainscaleid < 1) {
            return 'main_only';
        }

        $mainreported = !empty($jsondata['catquiz_scalereportcheckbox_' . $mainscaleid]);
        $reportedsubscaleids = [];
        foreach ($subscaleids as $subscaleid) {
            if (!empty($jsondata['catquiz_scalereportcheckbox_' . $subscaleid])) {
                $reportedsubscaleids[] = $subscaleid;
            }
        }

        if ($mainreported && empty($reportedsubscaleids)) {
            return 'main_only';
        }
        if ($mainreported && !empty($reportedsubscaleids)) {
            return 'main_and_subscales_separate';
        }
        if (!empty($reportedsubscaleids)) {
            $parents = catquiz_data::get_parent_scale_ids($reportedsubscaleids, $mainscaleid);
            if (!empty($parents)) {
                return 'subscales_with_parents_without_main';
            }
            return 'subscales_only';
        }

        return 'main_only';
    }

    /**
     * Extract feedback ranges from saved JSON or wizard state.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @param int $mainscaleid
     * @param int $rangecount
     * @param float $minimum
     * @param float $maximum
     * @return array
     */
    public static function extract_feedback_ranges(
        array $jsondata,
        array $wizarddata,
        int $mainscaleid,
        int $rangecount,
        float $minimum,
        float $maximum
    ): array {
        $ranges = self::extract_feedback_ranges_from_state($wizarddata, $rangecount, $minimum, $maximum);
        if (self::has_non_empty_feedback_ranges($ranges)) {
            return self::merge_feedback_ranges_with_defaults($ranges, $minimum, $maximum);
        }

        $ranges = [];
        for ($index = 1; $index <= $rangecount; $index++) {
            $ranges[] = [
                'label' => 'Range ' . $index,
                'lower' => isset($jsondata['feedback_scaleid_limit_lower_' . $mainscaleid . '_' . $index])
                    ? (float)$jsondata['feedback_scaleid_limit_lower_' . $mainscaleid . '_' . $index]
                    : null,
                'upper' => isset($jsondata['feedback_scaleid_limit_upper_' . $mainscaleid . '_' . $index])
                    ? (float)$jsondata['feedback_scaleid_limit_upper_' . $mainscaleid . '_' . $index]
                    : null,
                'text' => self::extract_feedback_text($jsondata, $mainscaleid, $index),
                'templateformat' => 'mustache',
                'actioncourseenabled' => 0,
                'actioncoursetarget' => '',
                'actiongroupenabled' => 0,
                'actiongrouptarget' => '',
            ];
        }

        if (self::has_non_empty_feedback_ranges($ranges)) {
            return self::merge_feedback_ranges_with_defaults($ranges, $minimum, $maximum);
        }

        return self::build_default_feedback_ranges($rangecount, $minimum, $maximum);
    }


    /**
     * Parse CSV-based feedback ranges.
     *
     * @param string $csvranges
     * @return array
     */
    public static function parse_csv_feedback_ranges(string $csvranges): array {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvranges));
        if (empty($lines)) {
            return [];
        }

        $ranges = [];
        foreach ($lines as $lineindex => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $columns = str_getcsv($line);
            if ($lineindex === 0 && isset($columns[0], $columns[1], $columns[2])) {
                $header = array_map('core_text::strtolower', array_map('trim', array_slice($columns, 0, 3)));
                if ($header === ['label', 'lower', 'upper']) {
                    continue;
                }
            }
            if (count($columns) < 4) {
                return [];
            }
            $ranges[] = [
                'label' => trim((string)$columns[0]),
                'lower' => is_numeric(trim((string)$columns[1])) ? (float)trim((string)$columns[1]) : null,
                'upper' => is_numeric(trim((string)$columns[2])) ? (float)trim((string)$columns[2]) : null,
                'text' => trim((string)$columns[3]),
                'templateformat' => !empty($columns[4]) ? trim((string)$columns[4]) : 'mustache',
                'actioncourseenabled' => 0,
                'actioncoursetarget' => '',
                'actiongroupenabled' => 0,
                'actiongrouptarget' => '',
            ];
        }

        return $ranges;
    }

    /**
     * Build variable feedback ranges from a preset.
     *
     * @param int $rangecount
     * @param float $minimum
     * @param float $maximum
     * @param string $preset
     * @return array
     */
    public static function build_variable_feedback_ranges(
        int $rangecount,
        float $minimum,
        float $maximum,
        string $preset
    ): array {
        $rangecount = self::normalise_feedback_range_count($rangecount);
        $equal = self::build_default_feedback_ranges($rangecount, $minimum, $maximum);
        if ($preset === 'equal') {
            return $equal;
        }

        $weights = [];
        if ($preset === 'focus_low') {
            for ($index = 0; $index < $rangecount; $index++) {
                $weights[] = $rangecount - $index;
            }
        } else {
            for ($index = 0; $index < $rangecount; $index++) {
                $weights[] = $index + 1;
            }
        }

        $span = $maximum - $minimum;
        $totalweight = array_sum($weights) ?: 1;
        $cursor = $minimum;
        $ranges = [];
        foreach ($weights as $index => $weight) {
            $width = $span * ($weight / $totalweight);
            $upper = $index === ($rangecount - 1) ? $maximum : $cursor + $width;
            $ranges[] = [
                'label' => 'Range ' . ($index + 1),
                'lower' => round($cursor, 2),
                'upper' => round($upper, 2),
                'text' => 'Feedback for {{result.ranklabel}} in {{result.scalename}}.',
                'templateformat' => 'mustache',
                'actioncourseenabled' => 0,
                'actioncoursetarget' => '',
                'actiongroupenabled' => 0,
                'actiongrouptarget' => '',
            ];
            $cursor = $upper;
        }

        return $ranges;
    }

    /**
     * Apply explicit state fields to generated feedback ranges.
     *
     * @param array $ranges
     * @param array $state
     * @param float $minimum
     * @param float $maximum
     * @return array
     */
    protected static function apply_state_overrides_to_ranges(array $ranges, array $state, float $minimum, float $maximum): array {
        foreach ($ranges as $index => $range) {
            $stateindex = $index + 1;
            $override = self::build_feedback_range_from_state($state, $stateindex);
            foreach ($override as $key => $value) {
                if ($value !== null && $value !== '') {
                    $ranges[$index][$key] = $value;
                }
            }
        }
        return self::merge_feedback_ranges_with_defaults($ranges, $minimum, $maximum);
    }

    /**
     * Build one feedback range from flat state fields.
     *
     * @param array $state
     * @param int $index
     * @return array
     */
    protected static function build_feedback_range_from_state(array $state, int $index): array {
        return [
            'label' => (string)($state['feedbacklabel_' . $index] ?? ''),
            'lower' => isset($state['feedbacklower_' . $index]) ? (float)$state['feedbacklower_' . $index] : null,
            'upper' => isset($state['feedbackupper_' . $index]) ? (float)$state['feedbackupper_' . $index] : null,
            'text' => (string)($state['feedbacktext_' . $index] ?? ''),
            'templateformat' => (string)($state['feedbacktemplateformat_' . $index] ?? 'mustache'),
            'actioncourseenabled' => !empty($state['feedbackactioncourseenabled_' . $index]) ? 1 : 0,
            'actioncoursetarget' => (string)($state['feedbackactioncoursetarget_' . $index] ?? ''),
            'actiongroupenabled' => !empty($state['feedbackactiongroupenabled_' . $index]) ? 1 : 0,
            'actiongrouptarget' => (string)($state['feedbackactiongrouptarget_' . $index] ?? ''),
        ];
    }

    /**
     * Flatten feedback ranges to single form fields.
     *
     * @param array $ranges
     * @return array
     */
    public static function flatten_feedback_ranges(array $ranges): array {
        $fields = [];
        foreach (array_values($ranges) as $index => $range) {
            $fieldindex = $index + 1;
            $fields['feedbacklabel_' . $fieldindex] = (string)($range['label'] ?? '');
            $fields['feedbacklower_' . $fieldindex] = (float)($range['lower'] ?? 0);
            $fields['feedbackupper_' . $fieldindex] = (float)($range['upper'] ?? 0);
            $fields['feedbacktext_' . $fieldindex] = (string)($range['text'] ?? '');
            $fields['feedbacktemplateformat_' . $fieldindex] = (string)($range['templateformat'] ?? 'mustache');
            $fields['feedbackactioncourseenabled_' . $fieldindex] = !empty($range['actioncourseenabled']) ? 1 : 0;
            $fields['feedbackactioncoursetarget_' . $fieldindex] = (string)($range['actioncoursetarget'] ?? '');
            $fields['feedbackactiongroupenabled_' . $fieldindex] = !empty($range['actiongroupenabled']) ? 1 : 0;
            $fields['feedbackactiongrouptarget_' . $fieldindex] = (string)($range['actiongrouptarget'] ?? '');
        }
        return $fields;
    }

    /**
     * Build default fixed feedback ranges.
     *
     * @param int $rangecount
     * @param float $minimum
     * @param float $maximum
     * @return array
     */
    public static function build_default_feedback_ranges(int $rangecount, float $minimum, float $maximum): array {
        $rangecount = self::normalise_feedback_range_count($rangecount);
        if ($minimum >= $maximum) {
            $minimum = catquiz_data::DEFAULT_SCALE_MIN;
            $maximum = catquiz_data::DEFAULT_SCALE_MAX;
        }

        $step = ($maximum - $minimum) / $rangecount;
        $ranges = [];
        for ($index = 1; $index <= $rangecount; $index++) {
            $lower = $minimum + (($index - 1) * $step);
            $upper = $index === $rangecount ? $maximum : $minimum + ($index * $step);
            $ranges[] = [
                'label' => 'Range ' . $index,
                'lower' => round($lower, 2),
                'upper' => round($upper, 2),
                'text' => 'Feedback for {{result.ranklabel}} in {{result.scalename}}.',
                'templateformat' => 'mustache',
                'actioncourseenabled' => 0,
                'actioncoursetarget' => '',
                'actiongroupenabled' => 0,
                'actiongrouptarget' => '',
            ];
        }
        return $ranges;
    }

    /**
     * Merge partial feedback ranges with generated defaults.
     *
     * @param array $ranges
     * @param float $minimum
     * @param float $maximum
     * @return array
     */
    protected static function merge_feedback_ranges_with_defaults(array $ranges, float $minimum, float $maximum): array {
        $defaults = self::build_default_feedback_ranges(count($ranges), $minimum, $maximum);
        foreach ($ranges as $index => $range) {
            foreach (
                [
                    'label',
                    'lower',
                    'upper',
                    'text',
                    'templateformat',
                    'actioncourseenabled',
                    'actioncoursetarget',
                    'actiongroupenabled',
                    'actiongrouptarget',
                ] as $key
            ) {
                if ($range[$key] === null || $range[$key] === '') {
                    $ranges[$index][$key] = $defaults[$index][$key];
                }
            }
        }
        return $ranges;
    }

    /**
     * Return whether a range array contains meaningful values.
     *
     * @param array $ranges
     * @return bool
     */
    protected static function has_non_empty_feedback_ranges(array $ranges): bool {
        foreach ($ranges as $range) {
            if (!empty($range['label']) || !empty($range['text']) || $range['lower'] !== null || $range['upper'] !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract one feedback editor text from saved JSON.
     *
     * @param array $jsondata
     * @param int $scaleid
     * @param int $rangeindex
     * @return string
     */
    protected static function extract_feedback_text(array $jsondata, int $scaleid, int $rangeindex): string {
        $value = $jsondata['feedbackeditor_scaleid_' . $scaleid . '_' . $rangeindex] ?? '';
        if (is_array($value)) {
            return (string)($value['text'] ?? '');
        }
        if (is_object($value)) {
            return (string)($value->text ?? '');
        }
        return (string)$value;
    }
}
