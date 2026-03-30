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

namespace block_catquiz_feedbackwizard\local\service;

/**
 * Writes wizard state back into local_catquiz_tests.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2026 OpenAI
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_config_writer {
    /** @var string JSON key owned by this wizard. */
    public const WIZARDKEY = 'catquiz_wizard';

    /**
     * Persist wizard state into local_catquiz_tests.
     *
     * @param int $testid
     * @param array $state
     * @return void
     */
    public static function write_from_wizard_state(int $testid, array $state): void {
        global $DB;

        if ($testid < 1) {
            throw new \coding_exception('A valid testid is required.');
        }

        $target = $DB->get_record('local_catquiz_tests', ['id' => $testid], '*', MUST_EXIST);
        $baseconfig = self::decode_json($target->json);

        if (($state['mode'] ?? '') === 'clone' && !empty($state['sourcetestid'])) {
            $sourceid = (int)$state['sourcetestid'];
            if ($sourceid > 0 && $sourceid !== $testid) {
                $source = $DB->get_record('local_catquiz_tests', ['id' => $sourceid], 'json', IGNORE_MISSING);
                if (!empty($source->json)) {
                    $baseconfig = self::decode_json($source->json);
                }
            }
        }

        $mergedconfig = self::apply_wizard_state($baseconfig, $state);
        $mainscaleid = (int)($state['mainscaleid'] ?? 0);

        $record = (object)[
            'id' => $testid,
            'json' => json_encode($mergedconfig),
            'timemodified' => time(),
        ];

        if ($mainscaleid > 0) {
            $record->catscaleid = $mainscaleid;
        }

        $DB->update_record('local_catquiz_tests', $record);
    }

    /**
     * Apply wizard values to an existing CAT test JSON payload.
     *
     * @param array $baseconfig
     * @param array $state
     * @return array
     */
    public static function apply_wizard_state(array $baseconfig, array $state): array {
        $config = $baseconfig;
        $subscaleids = self::parse_subscale_ids($state['subscaleids'] ?? '');

        foreach (array_keys($config) as $key) {
            if (strpos($key, 'catquiz_subscalecheckbox_') === 0) {
                $config[$key] = '0';
            }
        }

        foreach ($subscaleids as $subscaleid) {
            $config['catquiz_subscalecheckbox_' . $subscaleid] = '1';
        }

        $questioncount = (int)($state['questioncount'] ?? 0);
        if ($questioncount > 0) {
            $config['maxquestionsgroup'] = [
                'catquiz_minquestions' => 0,
                'catquiz_maxquestions' => $questioncount,
            ];
        }

        $timelimitminutes = (int)($state['timelimitminutes'] ?? 0);
        if ($timelimitminutes > 0) {
            $config['catquiz_includetimelimit'] = 1;
            $config['catquiz_timelimitgroup'] = [
                'catquiz_maxtimeperattempt' => $timelimitminutes,
                'catquiz_timeselect_attempt' => 'm',
                'catquiz_maxtimeperitem' => 0,
                'catquiz_timeselect_item' => 'm',
            ];
        } else {
            $config['catquiz_includetimelimit'] = 0;
        }

        $config[self::WIZARDKEY] = [
            'version' => 1,
            'savedfromwizard' => 1,
            'mode' => (string)($state['mode'] ?? 'new'),
            'scenario' => (string)($state['scenario'] ?? ''),
            'scenario_notes' => (string)($state['scenario_notes'] ?? ''),
            'goal' => (string)($state['goal'] ?? ''),
            'mainscaleid' => (int)($state['mainscaleid'] ?? 0),
            'subscaleids' => $subscaleids,
            'precisionmode' => (string)($state['precisionmode'] ?? ''),
            'questioncount' => $questioncount,
            'timelimitminutes' => $timelimitminutes,
        ];

        return $config;
    }

    /**
     * Parse a comma-separated subscale list.
     *
     * @param string $value
     * @return array
     */
    public static function parse_subscale_ids(string $value): array {
        $parts = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return [];
        }

        $ids = [];
        foreach ($parts as $part) {
            if (!preg_match('/^\d+$/', $part)) {
                continue;
            }
            $ids[] = (int)$part;
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    /**
     * Decode a JSON string into an array.
     *
     * @param ?string $json
     * @return array
     */
    protected static function decode_json(?string $json): array {
        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
