<?php
    namespace block_catquiz_feedbackwizard\privacy;
    
    defined('MOODLE_INTERNAL') || die();
    use core_privacy\local\metadata\collection; 
    use core_privacy\local\request\writer; 
    use context;
    
    class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider {
        
        
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
        
        public static function delete_data_for_all_users_in_context(context $context) {
            
            global $DB;
            
            if ($context->contextlevel == CONTEXT_COURSE) {
                $DB->delete_records('block_catquiz_feedbackwizard', ['courseid' => $context->instanceid]);
            }
        }
        public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist) {
            global $DB;
            $userid = $contextlist->get_user()->id;
                
        }
    }