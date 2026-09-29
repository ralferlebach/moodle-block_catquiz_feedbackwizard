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
 * Imports feedback texts from a structured serial document.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

/**
 * Imports feedback texts from a structured serial document.
 *
 * The document is a CSV with one row per feedback range, so a teacher can
 * write the texts in a spreadsheet and paste the same building blocks across
 * several tests. Placeholders travel with the text and are resolved on the way
 * into the engine, exactly like hand-typed ones.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_import_service {
    /** @var array Accepted column headers, in the order the parser expects them. */
    const COLUMNS = ['range', 'label', 'text'];

    /** @var array Messages describing what the last import did. */
    protected static $messages = [];

    /**
     * Return the messages collected by the last apply_to_state() call.
     *
     * @return array
     */
    public static function get_messages(): array {
        return self::$messages;
    }

    /**
     * Return the example document shown in the form.
     *
     * @return string
     */
    public static function get_template_example(): string {
        return "range,label,text\n"
            . "1,Needs support,\"Please review the basics of {{result.scalename}}.\"\n"
            . "2,Ready,\"Well done in {{test.name}}.\"";
    }

    /**
     * Parse a serial document into rows.
     *
     * @param string $csv
     * @return array List of ['range' => int, 'label' => string, 'text' => string].
     */
    public static function parse(string $csv): array {
        $lines = preg_split('/\r\n|\r|\n/', trim($csv));
        if (!is_array($lines) || empty($lines)) {
            return [];
        }

        $rows = [];
        $autoindex = 0;
        foreach ($lines as $lineindex => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // The escape parameter is passed explicitly: PHP 8.4 deprecates
            // relying on the default, and an empty escape is the correct choice
            // for feedback texts, which may well end in a backslash.
            $columns = array_map('trim', str_getcsv($line, ',', '"', ''));

            if ($lineindex === 0 && self::looks_like_header($columns)) {
                continue;
            }

            if (count($columns) < 3) {
                self::$messages[] = get_string(
                    'warning:feedbackimportshortrow',
                    'block_catquiz_feedbackwizard',
                    $lineindex + 1
                );
                continue;
            }

            $autoindex++;
            $range = (int)$columns[0];
            if ($range < 1) {
                $range = $autoindex;
            }

            $rows[] = [
                'range' => $range,
                'label' => (string)$columns[1],
                'text' => (string)$columns[2],
            ];
        }

        return $rows;
    }

    /**
     * Return whether the first row is a header rather than data.
     *
     * @param array $columns
     * @return bool
     */
    protected static function looks_like_header(array $columns): bool {
        $normalised = array_map('strtolower', array_map('trim', $columns));

        return count(array_intersect(self::COLUMNS, $normalised)) >= 2;
    }

    /**
     * Apply imported rows to a wizard state.
     *
     * Rows beyond the configured number of ranges are reported rather than
     * silently dropped, and an unknown placeholder is reported rather than
     * quietly shipped to the student as literal braces.
     *
     * @param array $state
     * @param array $rows
     * @return array The updated state.
     */
    public static function apply_to_state(array $state, array $rows): array {
        self::$messages = [];

        $rangecount = test_config_normalizer::normalise_feedback_range_count(
            (int)($state['feedbackrangecount'] ?? 0)
        );

        $applied = 0;
        foreach ($rows as $row) {
            $index = (int)$row['range'];
            if ($index < 1 || $index > $rangecount) {
                self::$messages[] = get_string(
                    'warning:feedbackimportoutofrange',
                    'block_catquiz_feedbackwizard',
                    (object)['range' => $index, 'count' => $rangecount]
                );
                continue;
            }

            $unknown = feedback_template_service::get_unknown_tokens((string)$row['text']);
            if (!empty($unknown)) {
                self::$messages[] = get_string(
                    'warning:feedbackimportunknowntoken',
                    'block_catquiz_feedbackwizard',
                    implode(', ', $unknown)
                );
            }

            if (trim((string)$row['label']) !== '') {
                $state['feedbacklabel_' . $index] = (string)$row['label'];
            }
            $state['feedbacktext_' . $index] = (string)$row['text'];
            $applied++;
        }

        self::$messages[] = $applied > 0
            ? get_string('message:feedbackimportapplied', 'block_catquiz_feedbackwizard', $applied)
            : get_string('message:feedbackimportnothing', 'block_catquiz_feedbackwizard');

        return $state;
    }
}
