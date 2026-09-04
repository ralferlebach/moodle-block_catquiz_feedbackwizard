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
 * Scheduled task that removes expired wizard drafts.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\task;

use block_catquiz_feedbackwizard\local\service\draft_cleanup_service;
use core\task\scheduled_task;

/**
 * Scheduled task that removes expired wizard drafts.
 *
 * Wizard drafts are working data, not a record of anything. Keeping them beyond
 * the configured lifetime would build exactly the personal shadow history the
 * plugin is meant to avoid.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_drafts extends scheduled_task {
    /**
     * Return the name shown in the task administration.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:cleanupdrafts', 'block_catquiz_feedbackwizard');
    }

    /**
     * Delete every draft that exceeded its lifetime.
     *
     * @return void
     */
    public function execute(): void {
        $deleted = draft_cleanup_service::delete_expired_drafts();
        mtrace('block_catquiz_feedbackwizard: deleted ' . $deleted . ' expired wizard draft(s).');
    }
}
