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
 * Adapter for all write access to local_catquiz.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\adapter;

/**
 * Adapter for all write access to local_catquiz.
 *
 * The wizard must not write to the local_catquiz tables directly. Doing so
 * skips two things local_catquiz does on every save: it purges the
 * changesinquizsettings cache, and it recalculates the contextid when the main
 * scale changed. Both are easy to forget and hard to notice, so every write
 * goes through local_catquiz\testenvironment here.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_catquiz_adapter {
    /** @var string Fully qualified name of the local_catquiz test environment class. */
    const TESTENVIRONMENT_CLASS = '\local_catquiz\testenvironment';

    /**
     * Return whether the local_catquiz write API is available.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return class_exists(self::TESTENVIRONMENT_CLASS);
    }

    /**
     * Persist a CAT test configuration through local_catquiz.
     *
     * @param int $testid Id of the local_catquiz_tests record.
     * @param array $jsondata Decoded settings payload for the test.
     * @param int $catscaleid Main scale id, 0 keeps the currently stored one.
     * @return int Id of the saved record.
     */
    public static function save_test_configuration(int $testid, array $jsondata, int $catscaleid = 0): int {
        if ($testid < 1) {
            throw new \coding_exception('A valid test id is required to save a CAT test configuration.');
        }

        if (!self::is_available()) {
            throw new \moodle_exception(
                'error:localcatquizunavailable',
                'block_catquiz_feedbackwizard'
            );
        }

        $record = (object)[
            'id' => $testid,
            'json' => json_encode($jsondata),
        ];

        // Only override the scale when the wizard actually carries one. Passing
        // null would make local_catquiz fall back to the stored value anyway,
        // but being explicit keeps the intent readable.
        if ($catscaleid > 0) {
            $record->catscaleid = $catscaleid;
        }

        $class = self::TESTENVIRONMENT_CLASS;
        $testenvironment = new $class($record);

        return (int)$testenvironment->save_or_update();
    }
}
