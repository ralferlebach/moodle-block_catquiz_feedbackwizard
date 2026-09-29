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
 * Retention logic for wizard drafts.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

use block_catquiz_feedbackwizard\persistent\draft as draft_persistent;

/**
 * Retention logic for wizard drafts.
 *
 * Submitted drafts have already been written to local_catquiz, so the block
 * copy has no purpose afterwards and is removed on the next run. Unfinished
 * drafts are kept for the configured lifetime so that a teacher can come back
 * to an interrupted session, and are removed after that.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class draft_cleanup_service {
    /** @var int Grace period in seconds before a submitted draft is removed. */
    const SUBMITTED_GRACE_SECONDS = HOURSECS;

    /**
     * Delete all drafts that exceeded their lifetime.
     *
     * @param int|null $now Timestamp used as reference, defaults to the current time.
     * @return int Number of deleted records.
     */
    public static function delete_expired_drafts(?int $now = null): int {
        global $DB;

        $now = $now ?? time();
        $table = draft_persistent::TABLE;

        $draftcutoff = $now - (feature_settings_service::get_draft_ttl_hours() * HOURSECS);
        $submittedcutoff = $now - self::SUBMITTED_GRACE_SECONDS;

        $select = "(status <> :submittedstatus AND timemodified < :draftcutoff)
                   OR (status = :submittedstatus2 AND timemodified < :submittedcutoff)";
        $params = [
            'submittedstatus' => 'submitted',
            'draftcutoff' => $draftcutoff,
            'submittedstatus2' => 'submitted',
            'submittedcutoff' => $submittedcutoff,
        ];

        $count = $DB->count_records_select($table, $select, $params);
        if ($count < 1) {
            return 0;
        }

        $DB->delete_records_select($table, $select, $params);

        return $count;
    }
}
