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
 * Plugin version and other meta-data are defined here.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2025 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
    namespace block_catquiz_feedbackwizard\privacy;
    
    defined('MOODLE_INTERNAL') || die();
    use core_privacy\local\metadata\collection; 
    use core_privacy\local\request\writer; 
    use context;

    /**
     *
     */
    class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider {


        /**
         * @param collection $collection
         * @return collection
         */
        public static function get_metadata(collection $collection): collection {
        
        $collection->add_database_table(
            'block_catquiz_feedbackwizard', 
            [
                'userid' => 'privacy:metadata:block_catquiz_feedbackwizard:userid',
                'courseid' => 'privacy:metadata:block_catquiz_feedbackwizard:courseid',
                'datajson' => 'privacy:metadata:block_catquiz_feedbackwizard:datajson',
                ], 
            'privacy:metadata:block_catquiz_feedbackwizard');

            return $collection;
        }

        /**
         * @param int $userid
         * @return \core_privacy\local\request\contextlist
         */
        public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
            
            global $DB;
            
            $contextlist = new \core_privacy\local\request\contextlist();
            
            $sql = "SELECT DISTINCT ctx.id
            FROM {block_catquiz_feedbackwizard} mf
            JOIN {context} ctx ON ctx.instanceid = mf.courseid AND ctx.contextlevel = ?
            WHERE mf.userid = ?";
            $params = [CONTEXT_COURSE, $userid];
            
            $contextlist->add_from_sql($sql, $params);
            
            return $contextlist;
        }

        /**
         * @param \core_privacy\local\request\approved_contextlist $contextlist
         * @return void
         */
        public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist) {
        
            global $DB;
            
            $userid = $contextlist->get_user()->id;
            
            foreach ($contextlist->get_contexts() as $context) {
            
                if ($context->contextlevel != CONTEXT_COURSE) {
                    continue;
                }
                
                $records = $DB->get_records('block_catquiz_feedbackwizard', [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ]);
                
                $data = array_values($records);
                
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'block_catquiz_feedbackwizard')],
                    (object)['records' => $data]
                );
            }
        }

        /**
         * @param context $context
         * @return void
         */
        public static function delete_data_for_all_users_in_context(context $context) {
            
            global $DB;
            
            if ($context->contextlevel == CONTEXT_COURSE) {
                $DB->delete_records('block_catquiz_feedbackwizard', ['courseid' => $context->instanceid]);
            }
        }

        /**
         * @param \core_privacy\local\request\approved_contextlist $contextlist
         * @return void
         */
        public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist) {
            global $DB;
            $userid = $contextlist->get_user()->id;
                
        }
    }