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
 * CATQuiz test data access helpers.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2026 OpenAI
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use moodle_url;

/**
 * Helper methods for reading CATQuiz tests for the wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2026 OpenAI
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catquiz_data {
    /** @var string Readiness state: not configured. */
    public const READINESS_NOTCONFIGURED = 'notconfigured';
    /** @var string Readiness state: partially configured. */
    public const READINESS_PARTIAL = 'partial';
    /** @var string Readiness state: configured. */
    public const READINESS_CONFIGURED = 'configured';

    /**
     * Return CAT tests for a course.
     *
     * The wizard references local_catquiz_tests.id as testid.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_tests_by_courseid(int $courseid): array {
        global $DB;

        if ($courseid < 1) {
            return [];
        }

        $records = $DB->get_records('local_catquiz_tests', ['courseid' => $courseid], 'name ASC, id ASC');
        $tests = [];

        foreach ($records as $record) {
            $record->readiness = self::analyse_readiness($record);
            $record->readinesslabel = self::get_readiness_label($record->readiness);
            $record->editurl = (new moodle_url('/local/catquiz/manage_testenvironments.php'))->out(false);
            $tests[$record->id] = $record;
        }

        return $tests;
    }

    /**
     * Return a single CAT test by local_catquiz_tests.id.
     *
     * @param int $testid
     * @return ?\stdClass
     */
    public static function get_test_by_id(int $testid): ?\stdClass {
        global $DB;

        if ($testid < 1) {
            return null;
        }

        $record = $DB->get_record('local_catquiz_tests', ['id' => $testid]);
        if (!$record) {
            return null;
        }

        $record->readiness = self::analyse_readiness($record);
        $record->readinesslabel = self::get_readiness_label($record->readiness);
        $record->editurl = (new moodle_url('/local/catquiz/manage_testenvironments.php'))->out(false);

        return $record;
    }

    /**
     * Return tests which are useful as clone sources.
     *
     * @param int $courseid
     * @param int $excludedtestid
     * @return array
     */
    public static function get_clone_candidates(int $courseid, int $excludedtestid = 0): array {
        $tests = self::get_tests_by_courseid($courseid);

        foreach ($tests as $id => $test) {
            if ((int)$id === $excludedtestid) {
                unset($tests[$id]);
            }
        }

        return $tests;
    }

    /**
     * Return readiness for a local_catquiz_tests record.
     *
     * @param \stdClass $record
     * @return string
     */
    public static function analyse_readiness(\stdClass $record): string {
        $hascatscale = !empty($record->catscaleid);
        $hasjson = !empty($record->json);

        if (!$hascatscale && !$hasjson) {
            return self::READINESS_NOTCONFIGURED;
        }

        if ($hascatscale && $hasjson) {
            return self::READINESS_CONFIGURED;
        }

        return self::READINESS_PARTIAL;
    }

    /**
     * Return a language string label for a readiness state.
     *
     * @param string $state
     * @return string
     */
    public static function get_readiness_label(string $state): string {
        switch ($state) {
            case self::READINESS_CONFIGURED:
                return get_string('readiness:configured', 'block_catquiz_feedbackwizard');
            case self::READINESS_PARTIAL:
                return get_string('readiness:partial', 'block_catquiz_feedbackwizard');
            default:
                return get_string('readiness:notconfigured', 'block_catquiz_feedbackwizard');
        }
    }
}
