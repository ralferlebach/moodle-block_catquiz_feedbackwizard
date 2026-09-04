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
 * Unit tests for the draft cleanup service.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\draft_cleanup_service;

/**
 * Unit tests for the draft cleanup service.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\service\draft_cleanup_service
 */
final class draft_cleanup_service_test extends \advanced_testcase {
    /**
     * Insert one draft record directly.
     *
     * @param string $status
     * @param int $timemodified
     * @return int
     */
    protected function create_draft(string $status, int $timemodified): int {
        global $DB;

        return (int)$DB->insert_record('block_catquiz_feedbackwizard', (object)[
            'userid' => 5,
            'courseid' => 7,
            'testid' => 11,
            'status' => $status,
            'step' => 1,
            'datajson' => '{}',
            'timecreated' => $timemodified,
            'timemodified' => $timemodified,
        ]);
    }

    /**
     * Fresh drafts must survive, expired ones must go.
     *
     * @return void
     */
    public function test_expired_drafts_are_deleted(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('draft_ttl_hours', 24, 'block_catquiz_feedbackwizard');

        $now = time();
        $fresh = $this->create_draft('draft', $now - HOURSECS);
        $expired = $this->create_draft('draft', $now - (48 * HOURSECS));

        $deleted = draft_cleanup_service::delete_expired_drafts($now);

        $this->assertEquals(1, $deleted);
        $this->assertTrue($DB->record_exists('block_catquiz_feedbackwizard', ['id' => $fresh]));
        $this->assertFalse($DB->record_exists('block_catquiz_feedbackwizard', ['id' => $expired]));
    }

    /**
     * Submitted drafts are redundant once written and go after a short grace period.
     *
     * @return void
     */
    public function test_submitted_drafts_are_removed_after_grace_period(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('draft_ttl_hours', 72, 'block_catquiz_feedbackwizard');

        $now = time();
        $justsubmitted = $this->create_draft('submitted', $now - 60);
        $oldsubmitted = $this->create_draft('submitted', $now - (3 * HOURSECS));

        $deleted = draft_cleanup_service::delete_expired_drafts($now);

        $this->assertEquals(1, $deleted);
        $this->assertTrue($DB->record_exists('block_catquiz_feedbackwizard', ['id' => $justsubmitted]));
        $this->assertFalse($DB->record_exists('block_catquiz_feedbackwizard', ['id' => $oldsubmitted]));
    }

    /**
     * A clean table must not cause a delete call.
     *
     * @return void
     */
    public function test_nothing_to_delete(): void {
        $this->resetAfterTest();

        $this->create_draft('draft', time());

        $this->assertEquals(0, draft_cleanup_service::delete_expired_drafts());
    }
}
