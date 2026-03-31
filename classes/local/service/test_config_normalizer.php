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

defined('MOODLE_INTERNAL') || die();

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
        $subscaleids = self::extract_subscale_ids($jsondata, $wizarddata);

        return [
            'selectedtest' => (int)$testrecord->id,
            'testid' => (int)$testrecord->id,
            'wizardmode' => $mode,
            'sourcetestid' => $sourcetestid,
            'scenario' => (string)($wizarddata['scenario'] ?? ''),
            'mainscaleid' => $mainscaleid,
            'subscaleids' => $subscaleids,
            'questioncount' => self::extract_question_count($jsondata, $wizarddata),
            'timelimitminutes' => self::extract_time_limit_minutes($jsondata, $wizarddata),
            'precisionmode' => self::extract_precision_mode($jsondata, $wizarddata),
        ];
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
            if (strpos($key, 'catquiz_subscalecheckbox_') !== 0) {
                continue;
            }
            if (empty($value)) {
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
     * Extract question count.
     *
     * @param array $jsondata
     * @param array $wizarddata
     * @return int
     */
    public static function extract_question_count(array $jsondata, array $wizarddata = []): int {
        if (!empty($wizarddata['questioncount'])) {
            return (int)$wizarddata['questioncount'];
        }
        if (!empty($jsondata['catquiz_maxquestions'])) {
            return (int)$jsondata['catquiz_maxquestions'];
        }
        return 0;
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
        if (!empty($jsondata['catquiz_maxtimeperattempt'])) {
            return (int)floor(((int)$jsondata['catquiz_maxtimeperattempt']) / 60);
        }
        return 0;
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
}
