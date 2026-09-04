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

/**
 * Data access helper methods for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catquiz_data {
    /** @var float Default lower ability boundary. */
    const DEFAULT_SCALE_MIN = -5.0;

    /** @var float Default upper ability boundary. */
    const DEFAULT_SCALE_MAX = 5.0;

    /**
     * Check whether a local_catquiz table exists.
     *
     * @param string $tablename
     * @return bool
     */
    protected static function local_catquiz_table_exists(string $tablename): bool {
        global $DB;

        $manager = $DB->get_manager();
        $table = new \xmldb_table($tablename);
        return $manager->table_exists($table);
    }

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
    public static function get_test_by_id(int $testid, int $courseid = 0): ?\stdClass {
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

        if ($courseid > 0) {
            $sql .= " AND lct.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        $record = $DB->get_record_sql($sql, $params);
        return $record ?: null;
    }

    /**
     * Return whether a CAT test belongs to the given course.
     *
     * Used to make sure a submitted test id cannot address a test outside the
     * course context the capability check was performed against.
     *
     * @param int $testid
     * @param int $courseid
     * @return bool
     */
    public static function test_belongs_to_course(int $testid, int $courseid): bool {
        if ($testid < 1 || $courseid < 1) {
            return false;
        }

        return self::get_test_by_id($testid, $courseid) !== null;
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

        return array_values(array_filter($records, static function ($record) use ($excludetestid) {
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

        if (!self::local_catquiz_table_exists('local_catquiz_catscales')) {
            return [];
        }

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
     * Return course category options for matching rules.
     *
     * @return array
     */
    public static function get_course_category_options(): array {
        global $DB;

        $records = $DB->get_records('course_categories', null, 'sortorder ASC, id ASC', 'id, name, path');
        $options = [0 => get_string('choosedots')];
        foreach ($records as $record) {
            $depth = max(0, count(array_filter(explode('/', (string)$record->path))) - 1);
            $prefix = str_repeat('- ', $depth);
            $options[(int)$record->id] = $prefix . format_string($record->name);
        }
        return $options;
    }

    /**
     * Return one catscale record.
     *
     * @param int $scaleid
     * @return \stdClass|null
     */
    public static function get_scale_by_id(int $scaleid): ?\stdClass {
        global $DB;

        if ($scaleid < 1 || !self::local_catquiz_table_exists('local_catquiz_catscales')) {
            return null;
        }

        $record = $DB->get_record(
            'local_catquiz_catscales',
            ['id' => $scaleid],
            'id, parentid, name, minscalevalue, maxscalevalue'
        );

        return $record ?: null;
    }

    /**
     * Return the ability range for a scale, preferring the root scale values.
     *
     * @param int $scaleid
     * @return array
     */
    public static function get_scale_range(int $scaleid): array {
        $record = self::get_scale_by_id($scaleid);
        if (!$record) {
            return ['min' => self::DEFAULT_SCALE_MIN, 'max' => self::DEFAULT_SCALE_MAX];
        }

        $current = $record;
        while ($current && (int)$current->parentid > 0) {
            $parent = self::get_scale_by_id((int)$current->parentid);
            if (!$parent) {
                break;
            }
            if ($parent->minscalevalue !== null || $parent->maxscalevalue !== null) {
                $current = $parent;
            } else {
                break;
            }
        }

        $minimum = $current->minscalevalue !== null ? (float)$current->minscalevalue : self::DEFAULT_SCALE_MIN;
        $maximum = $current->maxscalevalue !== null ? (float)$current->maxscalevalue : self::DEFAULT_SCALE_MAX;

        if ($minimum >= $maximum) {
            return ['min' => self::DEFAULT_SCALE_MIN, 'max' => self::DEFAULT_SCALE_MAX];
        }

        return ['min' => $minimum, 'max' => $maximum];
    }

    /**
     * Return subscale option labels for a main scale.
     *
     * @param int $mainscaleid
     * @return array
     */
    public static function get_subscale_options(int $mainscaleid): array {
        $records = self::get_subscale_records($mainscaleid);
        $options = [];
        foreach ($records as $record) {
            $options[(int)$record->id] = self::format_subscale_label($record);
        }
        return $options;
    }

    /**
     * Return subscale records with item counts for a main scale.
     *
     * @param int $mainscaleid
     * @return array
     */
    public static function get_subscale_records(int $mainscaleid): array {
        global $DB;

        if ($mainscaleid < 1 || !self::local_catquiz_table_exists('local_catquiz_catscales')) {
            return [];
        }

        $allrecords = $DB->get_records(
            'local_catquiz_catscales',
            null,
            'parentid ASC, name ASC, id ASC',
            'id, parentid, name'
        );
        if (empty($allrecords)) {
            return [];
        }

        $childrenbyparent = [];
        foreach ($allrecords as $record) {
            $childrenbyparent[(int)$record->parentid][] = $record;
        }

        $itemcounts = [];
        $countrecords = [];
        if (self::local_catquiz_table_exists('local_catquiz_items')) {
            $countrecords = $DB->get_records_sql(
                'SELECT catscaleid, COUNT(*) AS itemcount
                   FROM {local_catquiz_items}
               GROUP BY catscaleid'
            );
        }
        foreach ($countrecords as $countrecord) {
            $itemcounts[(int)$countrecord->catscaleid] = (int)$countrecord->itemcount;
        }

        $results = [];
        self::append_subscale_records($mainscaleid, $childrenbyparent, $itemcounts, 1, $results);
        return $results;
    }

    /**
     * Return all scale ids in the selected main-scale tree.
     *
     * @param int $mainscaleid
     * @return array
     */
    public static function get_scale_tree_ids(int $mainscaleid): array {
        if ($mainscaleid < 1) {
            return [];
        }

        $ids = [$mainscaleid];
        foreach (self::get_subscale_records($mainscaleid) as $record) {
            $ids[] = (int)$record->id;
        }

        $ids = array_values(array_unique(array_filter($ids)));
        sort($ids);
        return $ids;
    }

    /**
     * Return all direct parent ids for the selected subscales, excluding the root scale.
     *
     * @param array $subscaleids
     * @param int $mainscaleid
     * @return array
     */
    public static function get_parent_scale_ids(array $subscaleids, int $mainscaleid): array {
        $parentids = [];
        foreach (array_values(array_unique(array_map('intval', $subscaleids))) as $subscaleid) {
            $current = self::get_scale_by_id($subscaleid);
            while ($current && (int)$current->parentid > 0 && (int)$current->parentid !== $mainscaleid) {
                $parentids[] = (int)$current->parentid;
                $current = self::get_scale_by_id((int)$current->parentid);
            }
        }

        $parentids = array_values(array_unique(array_filter($parentids)));
        sort($parentids);
        return $parentids;
    }

    /**
     * Resolve report scale ids from a reporting strategy.
     *
     * @param int $mainscaleid
     * @param array $subscaleids
     * @param string $reportingstrategy
     * @return array
     */
    public static function get_reporting_scale_ids(int $mainscaleid, array $subscaleids, string $reportingstrategy): array {
        $subscaleids = array_values(array_unique(array_filter(array_map('intval', $subscaleids))));
        sort($subscaleids);

        switch ($reportingstrategy) {
            case 'subscales_only':
                $scaleids = $subscaleids;
                break;
            case 'main_and_subscales_separate':
                $scaleids = array_merge([$mainscaleid], $subscaleids);
                break;
            case 'subscales_with_parents_without_main':
                $scaleids = array_merge($subscaleids, self::get_parent_scale_ids($subscaleids, $mainscaleid));
                break;
            case 'main_only':
            default:
                $scaleids = [$mainscaleid];
                break;
        }

        $scaleids = array_values(array_unique(array_filter(array_map('intval', $scaleids))));
        sort($scaleids);
        return $scaleids;
    }

    /**
     * Format a CAT test display name.
     *
     * @param \stdClass $test
     * @return string
     */
    public static function get_test_display_name(\stdClass $test): string {
        $parts = [];
        $name = trim((string)($test->name ?? ''));
        if ($name !== '') {
            $parts[] = format_string($name);
        } else if (!empty($test->adaptivequizname)) {
            $parts[] = format_string((string)$test->adaptivequizname);
        } else {
            $parts[] = 'CAT test #' . (int)$test->id;
        }

        $parts[] = '#' . (int)$test->id;

        $status = self::analyse_test_readiness($test);
        $parts[] = '[' . get_string('readiness:' . $status, 'block_catquiz_feedbackwizard') . ']';

        return implode(' ', $parts);
    }

    /**
     * Analyse a rough readiness level from a CAT test record.
     *
     * @param \stdClass $test
     * @return string
     */
    public static function analyse_test_readiness(\stdClass $test): string {
        $jsondata = [];
        if (!empty($test->json)) {
            $decoded = json_decode((string)$test->json, true);
            if (is_array($decoded)) {
                $jsondata = $decoded;
            }
        }

        $mainscaleid = (int)($jsondata['catquiz_catscales'] ?? $jsondata['catscaleid'] ?? $test->catscaleid ?? 0);
        $hasfeedback = !empty($jsondata['numberoffeedbackoptionsselect']);
        $hasquestions = !empty($jsondata['maxquestionsgroup']['catquiz_maxquestions'] ?? 0);

        if ($mainscaleid < 1) {
            return 'incomplete';
        }
        if ($hasquestions && $hasfeedback) {
            return 'ready';
        }
        return 'warnings';
    }

    /**
     * Append recursive subscale records.
     *
     * @param int $parentid
     * @param array $childrenbyparent
     * @param array $itemcounts
     * @param int $depth
     * @param array $results
     * @return void
     */
    protected static function append_subscale_records(
        int $parentid,
        array $childrenbyparent,
        array $itemcounts,
        int $depth,
        array &$results
    ): void {
        if (empty($childrenbyparent[$parentid])) {
            return;
        }

        foreach ($childrenbyparent[$parentid] as $record) {
            $record->depth = $depth;
            $record->itemcount = $itemcounts[(int)$record->id] ?? 0;
            $results[] = $record;
            self::append_subscale_records((int)$record->id, $childrenbyparent, $itemcounts, $depth + 1, $results);
        }
    }

    /**
     * Format a subscale label for display.
     *
     * @param \stdClass $record
     * @return string
     */
    protected static function format_subscale_label(\stdClass $record): string {
        $prefix = str_repeat('- ', max(0, ((int)($record->depth ?? 1)) - 1));
        return $prefix . format_string($record->name) . ' (' . (int)($record->itemcount ?? 0) . ')';
    }
}
