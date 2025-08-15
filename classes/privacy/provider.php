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
 * Privacy provider for the catquiz feedback wizard block.
 *
 * This file contains the privacy provider implementation that handles
 * GDPR compliance for the catquiz feedback wizard block, including
 * data export and deletion functionality.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2025 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use context;

/**
 * Privacy provider for catquiz feedback wizard block.
 *
 * This class implements Moodle's privacy API to ensure GDPR compliance
 * by providing methods to export and delete user data stored by the
 * catquiz feedback wizard block.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2025 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider {

    /**
     * Get metadata about the data stored by this plugin.
     *
     * Returns information about what personal data is stored by the plugin
     * and where it is stored, for GDPR compliance documentation.
     *
     * @param collection $collection The collection to add metadata to
     * @return collection The updated collection with plugin metadata
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'block_catquiz_feedbackwizard', [
                'userid' => 'privacy:metadata:block_catquiz_feedbackwizard:userid',
                'courseid' => 'privacy:metadata:block_catquiz_feedbackwizard:courseid',
                'datajson' => 'privacy:metadata:block_catquiz_feedbackwizard:datajson',
            ],
            'privacy:metadata:block_catquiz_feedbackwizard'
        );

        return $collection;
    }

    /**
     * Get contexts containing user data for the specified user.
     *
     * Retrieves all course contexts where the specified user has
     * created feedback wizard entries.
     *
     * @param int $userid The user ID to get contexts for
     * @return \core_privacy\local\request\contextlist List of contexts containing user data
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {

        global $DB;

        $contextlist = new \core_privacy\local\request\contextlist();

        // Find all course contexts where this user has feedback wizard data.
        $sql = "SELECT DISTINCT ctx.id
                FROM {block_catquiz_feedbackwizard} mf
                JOIN {context} ctx ON ctx.instanceid = mf.courseid AND ctx.contextlevel = ?
                WHERE mf.userid = ?";
        $params = [CONTEXT_COURSE, $userid];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export user data for the specified contexts.
     *
     * Exports all feedback wizard data for the user in the approved contexts
     * as part of a GDPR data export request.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist The approved contexts to export data for
     * @return void
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist) {

        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            // Only process course contexts.
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            // Get all feedback wizard records for this user in this course.
            $records = $DB->get_records('block_catquiz_feedbackwizard', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ]);

            $data = array_values($records);

            // Export the data to the privacy export.
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'block_catquiz_feedbackwizard')],
                (object)['records' => $data]
            );
        }
    }

    /**
     * Delete all user data in the specified context.
     *
     * Removes all feedback wizard data for all users within the given context.
     * This is typically called when a course is deleted.
     *
     * @param context $context The context to delete data for
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context) {

        global $DB;

        // Only delete data for course contexts.
        if ($context->contextlevel == CONTEXT_COURSE) {
            $DB->delete_records('block_catquiz_feedbackwizard', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete user data for the specified user in the approved contexts.
     *
     * Removes all feedback wizard data for the specified user within
     * the approved contexts as part of a GDPR deletion request.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist The approved contexts to delete data for
     * @return void
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            // Only process course contexts.
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            // Delete all feedback wizard records for this user in this course.
            $DB->delete_records('block_catquiz_feedbackwizard', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ]);
        }
    }
}
