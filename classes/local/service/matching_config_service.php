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
 * Matching configuration helper methods for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

/**
 * Matching configuration helper methods for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matching_config_service {
    /** @var string[] Supported matching modes. */
    const MODES = ['none', 'rule', 'csv'];

    /** @var string[] Supported course identifier fields. */
    const COURSE_FIELDS = ['shortname', 'fullname', 'idnumber'];

    /** @var string[] Supported comparison operators. */
    const OPERATORS = ['contains', 'startswith', 'equals', 'regex'];

    /** @var string[] Supported matching targets. */
    const TARGET_TYPES = ['catscale', 'course', 'group'];

    /**
     * Normalise a full matching configuration array.
     *
     * @param array $matching
     * @return array
     */
    public static function normalise_matching(array $matching): array {
        return [
            'mode' => self::normalise_mode((string)($matching['mode'] ?? 'none')),
            'categoryid' => max(0, (int)($matching['categoryid'] ?? 0)),
            'coursefield' => self::normalise_course_field((string)($matching['coursefield'] ?? 'shortname')),
            'operator' => self::normalise_operator((string)($matching['operator'] ?? 'contains')),
            'pattern' => trim((string)($matching['pattern'] ?? '')),
            'targettype' => self::normalise_target_type((string)($matching['targettype'] ?? 'catscale')),
            'targetvalue' => trim((string)($matching['targetvalue'] ?? '')),
            'csv' => trim((string)($matching['csv'] ?? '')),
            'csvrules' => self::parse_csv_rules((string)($matching['csv'] ?? '')),
        ];
    }

    /**
     * Normalise one matching mode.
     *
     * @param string $mode
     * @return string
     */
    public static function normalise_mode(string $mode): string {
        if (!in_array($mode, self::MODES, true)) {
            return 'none';
        }
        return $mode;
    }

    /**
     * Normalise one matching course field.
     *
     * @param string $field
     * @return string
     */
    public static function normalise_course_field(string $field): string {
        if (!in_array($field, self::COURSE_FIELDS, true)) {
            return 'shortname';
        }
        return $field;
    }

    /**
     * Normalise one matching operator.
     *
     * @param string $operator
     * @return string
     */
    public static function normalise_operator(string $operator): string {
        if (!in_array($operator, self::OPERATORS, true)) {
            return 'contains';
        }
        return $operator;
    }

    /**
     * Normalise one matching target type.
     *
     * @param string $targettype
     * @return string
     */
    public static function normalise_target_type(string $targettype): string {
        if (!in_array($targettype, self::TARGET_TYPES, true)) {
            return 'catscale';
        }
        return $targettype;
    }

    /**
     * Parse CSV-style matching rules from plain text.
     *
     * @param string $csvtext
     * @return array
     */
    public static function parse_csv_rules(string $csvtext): array {
        $rules = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($csvtext));
        if (empty($lines)) {
            return [];
        }

        foreach ($lines as $lineindex => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $columns = array_map('trim', str_getcsv($line));
            if ($lineindex === 0 && self::looks_like_header($columns)) {
                continue;
            }
            if (count($columns) < 5) {
                continue;
            }

            $rules[] = [
                'coursefield' => self::normalise_course_field((string)$columns[0]),
                'operator' => self::normalise_operator((string)$columns[1]),
                'pattern' => (string)$columns[2],
                'targettype' => self::normalise_target_type((string)$columns[3]),
                'targetvalue' => (string)$columns[4],
            ];
        }

        return $rules;
    }

    /**
     * Return whether a CSV row looks like a header.
     *
     * @param array $columns
     * @return bool
     */
    protected static function looks_like_header(array $columns): bool {
        if (count($columns) < 5) {
            return false;
        }

        $expected = ['coursefield', 'operator', 'pattern', 'targettype', 'targetvalue'];
        $actual = array_map(
            static function(string $value): string {
                return strtolower(trim($value));
            },
            array_slice($columns, 0, 5)
        );

        return $actual === $expected;
    }

    /**
     * Return whether a regular expression pattern is invalid.
     *
     * @param string $pattern
     * @return bool
     */
    public static function has_invalid_regex(string $pattern): bool {
        if ($pattern === '') {
            return false;
        }

        return @preg_match('/' . str_replace('/', '\/', $pattern) . '/u', '') === false;
    }

    /**
     * Return the count of valid-looking CSV rules.
     *
     * @param string $csvtext
     * @return int
     */
    public static function count_csv_rules(string $csvtext): int {
        return count(self::parse_csv_rules($csvtext));
    }
}
