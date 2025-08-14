<?php 
    defined('MOODLE_INTERNAL') || die();
    
    class block_catquiz_feedbackwizard extends block_base { 
        
        public function init() { 
            $this->title = get_string('pluginname', 'block_catquiz_feedbackwizard'); 
        }
        
        public function applicable_formats() {
            return [
            'site' => true,
            'course-view' => true,
            ];
        }
        
        public function instance_allow_multiple() {
            return false;
        }
        
        public function get_content() {
        
            global $COURSE, $OUTPUT;
            if ($this->content !== null) {
                return $this->content;
            }
            
            $this->content = new stdClass();
            $context = context_course::instance($COURSE->id ?? SITEID);
            if (!has_capability('block/catquiz_feedbackwizard:use', $context)) {
                $this->content->text = '';
                return $this->content;
            }
            
            $data = (object)[
            'buttonlabel' => get_string('openwizard', 'block_catquiz_feedbackwizard'),
            'courseid' => (int)($COURSE->id ?? SITEID),
            ];
            
            $this->content->text = $OUTPUT->render_from_template('block_catquiz_feedbackwizard/block', $data);
            
            // Load our AMD to wire up the modal form.
            $this->page->requires->js_call_amd('block_catquiz_feedbackwizard/main', 'init');
            
            return $this->content;
        }
    }