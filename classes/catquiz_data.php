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
 * Data access helper methods for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

defined('MOODLE_INTERNAL') || die();

/**
 * Data access helper methods for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catquiz_data {
    /**
     * Return CAT tests for a course.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_tests_by_courseid(int $courseid): array {
        global $DB;

        if ($courseid < 1) {
            return [];
        }

        $sql = "SELECT lct.id,
                       lct.courseid,
                       lct.componentid,
                       lct.component,
                       lct.catscaleid,
                       lct.name,
                       lct.json,
                       aq.id AS adaptivequizid,
                       aq.name AS adaptivequizname
                  FROM {local_catquiz_tests} lct
             LEFT JOIN {adaptivequiz} aq
                    ON aq.id = lct.componentid
                   AND lct.component = :component
                 WHERE lct.courseid = :courseid
                   AND lct.status = :status
              ORDER BY lct.name ASC, lct.id ASC";

        $params = [
            'component' => 'mod_adaptivequiz',
            'courseid' => $courseid,
            'status' => 1,
        ];

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Backwards compatible wrapper for the old typoed method name.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_catquiz_by_couseid(int $courseid): array {
        return self::get_tests_by_courseid($courseid);
    }

    /**
     * Return a single CAT test record.
     *
     * @param int $testid
     * @return \stdClass|null
     */
    public static function get_test_by_id(int $testid): ?\stdClass {
        global $DB;

        if ($testid < 1) {
            return null;
        }

        $sql = "SELECT lct.id,
                       lct.courseid,
                       lct.componentid,
                       lct.component,
                       lct.catscaleid,
                       lct.contextid,
                       lct.name,
                       lct.description,
                       lct.descriptionformat,
                       lct.json,
                       aq.name AS adaptivequizname
                  FROM {local_catquiz_tests} lct
             LEFT JOIN {adaptivequiz} aq
                    ON aq.id = lct.componentid
                   AND lct.component = :component
                 WHERE lct.id = :testid";

        $params = [
            'component' => 'mod_adaptivequiz',
            'testid' => $testid,
        ];

        $record = $DB->get_record_sql($sql, $params);
        return $record ?: null;
    }

    /**
     * Return clone candidates for a course.
     *
     * @param int $courseid
     * @param int $excludetestid
     * @return array
     */
    public static function get_clone_candidates(int $courseid, int $excludetestid = 0): array {
        $records = self::get_tests_by_courseid($courseid);

        if ($excludetestid < 1) {
            return $records;
        }

        return array_values(array_filter($records, static function($record) use ($excludetestid) {
            return (int)$record->id !== $excludetestid;
        }));
    }

    /**
     * Return main scale options.
     *
     * @return array
     */
    public static function get_main_scale_options(): array {
        global $DB;

        $sql = "SELECT id, name
                  FROM {local_catquiz_catscales}
                 WHERE parentid = 0
              ORDER BY name ASC, id ASC";

        $records = $DB->get_records_sql($sql);
        $options = [];
        foreach ($records as $record) {
            $options[(int)$record->id] = format_string($record->name);
        }
        return $options;
    }

    /**
     * Return subscale options for a main scale.
     *
     * @param int $mainscaleid
     * @return array
     */
    public static function get_subscale_options(int $mainscaleid): array {
        global $DB;

        if ($mainscaleid < 1) {
            return [];
        }

        $records = $DB->get_records('local_catquiz_catscales', ['parentid' => $mainscaleid], 'name ASC, id ASC', 'id, name');
        $options = [];
        foreach ($records as $record) {
            $options[(int)$record->id] = format_string($record->name);
        }
        return $options;
    }

    /**
     * Return human readable label for a test record.
     *
     * @param \stdClass $record
     * @return string
     */
    public static function get_test_display_name(\stdClass $record): string {
        $parts = [];
        $name = trim((string)($record->name ?? ''));
        $adaptivequizname = trim((string)($record->adaptivequizname ?? ''));

        if ($name !== '') {
            $parts[] = $name;
        }
        if ($adaptivequizname !== '' && $adaptivequizname !== $name) {
            $parts[] = $adaptivequizname;
        }
        $parts[] = '#' . (int)$record->id;

        return implode(' – ', $parts);
    }
}
