<?php 
    
    namespace block_catquiz_feedbackwizard\persistent;
    
    defined('MOODLE_INTERNAL') || die();
    
    use core\persistent;
    
    class draft extends persistent { 
        
        const TABLE = 'block_catquiz_feedbackwizard';
        
        protected static function define_properties() {
            return [
            'userid' => [
                'type' => PARAM_INT,
                ],
            'courseid' => [
                'type' => PARAM_INT,
                ],
            'status' => [
                'type' => PARAM_ALPHA, // draft|submitted|abandoned
                'default' => 'draft',
                ],
            'step' => [
                'type' => PARAM_INT,
                'default' => 1,
                ],
            'datajson' => [
                    'type' => PARAM_RAW, // JSON string.
                    'null' => NULL_ALLOWED,
                    ],
            'timecreated' => [
                'type' => PARAM_INT,
                'default' => 0,
                ],
            'timemodified' => [
                'type' => PARAM_INT,
                'default' => 0,
                ],
            ];
        }
    }
        