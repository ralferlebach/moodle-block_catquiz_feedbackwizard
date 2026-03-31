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


/**
 * Normalises local_catquiz test JSON into wizard defaults.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_config_normalizer {
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

        return [
            'selectedtest' => (int)$testrecord->id,
            'testid' => (int)$testrecord->id,
            'wizardmode' => $mode,
            'sourcetestid' => $sourcetestid,
            'clonescope' => (string)($wizarddata['clonescope'] ?? 'full'),
            'scenario' => (string)($wizarddata['scenario'] ?? ''),
            'mainscaleid' => $mainscaleid,
            'subscaleids' => self::extract_subscale_ids($jsondata, $wizarddata),
            'minquestioncount' => self::extract_min_question_count($jsondata, $wizarddata),
            'questioncount' => self::extract_question_count($jsondata, $wizarddata),
            'questioncountpersubscale' => self::extract_question_count_per_subscale($jsondata, $wizarddata),
            'timelimitenabled' => self::extract_time_limit_enabled($jsondata, $wizarddata),
            'timelimitminutes' => self::extract_time_limit_minutes($jsondata, $wizarddata),
            'precisionmode' => self::extract_precision_mode($jsondata, $wizarddata),
            'testgoal' => self::extract_test_goal($wizarddata),
            'completionenabled' => self::extract_completion_enabled($jsondata, $wizarddata),
        ];
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
            foreach ([
                'minquestioncount',
                'questioncount',
                'questioncountpersubscale',
                'timelimitenabled',
                'timelimitminutes',
                'precisionmode',
                'testgoal',
                'completionenabled',
            ] as $field) {
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
     * Extract maximum question count per subscale.
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
     * Extract whether the test currently uses a time limit.
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
}
