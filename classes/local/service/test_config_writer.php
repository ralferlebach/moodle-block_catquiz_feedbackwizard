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
 * Writes wizard state back into local_catquiz_tests.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

use block_catquiz_feedbackwizard\catquiz_data;


/**
 * Writes wizard state back into local_catquiz_tests.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_config_writer {
    /**
     * Persist wizard state to a local_catquiz_tests record.
     *
     * @param int $testid
     * @param array $wizardstate
     * @return void
     */
    public static function write_to_test(int $testid, array $wizardstate): void {
        global $DB;

        $record = catquiz_data::get_test_by_id($testid);
        if (!$record) {
            throw new \moodle_exception('invalidrecord', 'error');
        }

        $basejson = test_config_normalizer::decode_json((string)($record->json ?? ''));
        if (($wizardstate['wizardmode'] ?? '') === 'clone' && !empty($wizardstate['sourcetestid'])) {
            $source = catquiz_data::get_test_by_id((int)$wizardstate['sourcetestid']);
            if ($source) {
                $basejson = test_config_normalizer::decode_json((string)($source->json ?? ''));
            }
        }

        $jsondata = self::apply_wizard_state($basejson, $wizardstate);
        $update = (object)[
            'id' => $testid,
            'catscaleid' => !empty($wizardstate['mainscaleid'])
                ? (int)$wizardstate['mainscaleid']
                : (int)($record->catscaleid ?? 0),
            'json' => json_encode($jsondata),
            'timemodified' => time(),
        ];
        $DB->update_record('local_catquiz_tests', $update);
    }

    /**
     * Apply wizard state to an existing local_catquiz JSON payload.
     *
     * @param array $jsondata
     * @param array $wizardstate
     * @return array
     */
    public static function apply_wizard_state(array $jsondata, array $wizardstate): array {
        $mainscaleid = (int)($wizardstate['mainscaleid'] ?? 0);
        if ($mainscaleid > 0) {
            $jsondata['catscaleid'] = $mainscaleid;
            $jsondata['catquiz_catscales'] = $mainscaleid;
        }

        foreach (array_keys($jsondata) as $key) {
            if (strpos($key, 'catquiz_subscalecheckbox_') === 0) {
                unset($jsondata[$key]);
            }
        }
        foreach (array_map('intval', (array)($wizardstate['subscaleids'] ?? [])) as $subscaleid) {
            if ($subscaleid > 0) {
                $jsondata['catquiz_subscalecheckbox_' . $subscaleid] = 1;
            }
        }

        if (!isset($jsondata['maxquestionsgroup']) || !is_array($jsondata['maxquestionsgroup'])) {
            $jsondata['maxquestionsgroup'] = [];
        }
        $jsondata['maxquestionsgroup']['catquiz_minquestions'] = (int)($wizardstate['minquestioncount'] ?? 0);
        $jsondata['maxquestionsgroup']['catquiz_maxquestions'] = (int)($wizardstate['questioncount'] ?? 0);

        if (!isset($jsondata['maxquestionsscalegroup']) || !is_array($jsondata['maxquestionsscalegroup'])) {
            $jsondata['maxquestionsscalegroup'] = [];
        }
        $jsondata['maxquestionsscalegroup']['catquiz_maxquestionspersubscale'] =
            (int)($wizardstate['questioncountpersubscale'] ?? 0);
        $jsondata['maxquestionsscalegroup']['catquiz_minquestionspersubscale'] = 0;

        $timelimitenabled = !empty($wizardstate['timelimitenabled']);
        $completionenabled = !empty($wizardstate['completionenabled']);
        $testgoal = (string)($wizardstate['testgoal'] ?? '');
        $jsondata['catquiz_includetimelimit'] = $timelimitenabled ? 1 : 0;
        if (!isset($jsondata['catquiz_timelimitgroup']) || !is_array($jsondata['catquiz_timelimitgroup'])) {
            $jsondata['catquiz_timelimitgroup'] = [];
        }
        $jsondata['catquiz_timelimitgroup']['catquiz_timeselect_attempt'] = 'min';
        $jsondata['catquiz_timelimitgroup']['catquiz_maxtimeperattempt'] = $timelimitenabled
            ? (int)($wizardstate['timelimitminutes'] ?? 0)
            : 0;
        $jsondata['catquiz_timelimitgroup']['catquiz_timeselect_item'] = 'sec';
        $jsondata['catquiz_timelimitgroup']['catquiz_maxtimeperitem'] = 0;

        if (!isset($jsondata['catquiz_standarderrorgroup']) || !is_array($jsondata['catquiz_standarderrorgroup'])) {
            $jsondata['catquiz_standarderrorgroup'] = [];
        }
        [$semin, $semax] = self::map_precision_mode((string)($wizardstate['precisionmode'] ?? 'medium'));
        $jsondata['catquiz_standarderrorgroup']['catquiz_standarderror_min'] = $semin;
        $jsondata['catquiz_standarderrorgroup']['catquiz_standarderror_max'] = $semax;

        $jsondata['completion'] = $completionenabled ? 1 : 0;
        $jsondata['completionview'] = $completionenabled ? 1 : 0;

        $jsondata['catquiz_wizard'] = [
            'wizardmode' => (string)($wizardstate['wizardmode'] ?? 'edit'),
            'scenario' => (string)($wizardstate['scenario'] ?? ''),
            'mainscaleid' => $mainscaleid,
            'subscaleids' => array_values(array_map('intval', (array)($wizardstate['subscaleids'] ?? []))),
            'minquestioncount' => (int)($wizardstate['minquestioncount'] ?? 0),
            'questioncount' => (int)($wizardstate['questioncount'] ?? 0),
            'questioncountpersubscale' => (int)($wizardstate['questioncountpersubscale'] ?? 0),
            'timelimitenabled' => $timelimitenabled ? 1 : 0,
            'timelimitminutes' => (int)($wizardstate['timelimitminutes'] ?? 0),
            'precisionmode' => (string)($wizardstate['precisionmode'] ?? 'medium'),
            'testgoal' => $testgoal,
            'completionenabled' => $completionenabled ? 1 : 0,
            'sourcetestid' => (int)($wizardstate['sourcetestid'] ?? 0),
        ];

        return $jsondata;
    }

    /**
     * Map a simple precision mode to standard error values.
     *
     * @param string $precisionmode
     * @return array
     */
    public static function map_precision_mode(string $precisionmode): array {
        switch ($precisionmode) {
            case 'high':
                return [0.2, 1.0];
            case 'low':
                return [0.6, 1.0];
            default:
                return [0.35, 1.0];
        }
    }
}
