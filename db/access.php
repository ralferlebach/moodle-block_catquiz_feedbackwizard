<?php 
    defined('MOODLE_INTERNAL') || die();
    
    $capabilities = [ 
        // Erlaubt das Hinzufügen des Blocks auf Kursseiten. 
        'block/catquiz_feedbackwizard:addinstance' => [
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE, 
            'archetypes' => [
                'manager' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
            ], 
        'clonepermissionsfrom' => 'moodle/site:manageblocks', 
        ],
    
        // Ihre bestehende Capability zum Nutzen des Wizards.
        'block/catquiz_feedbackwizard:use' => [
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => [
                'manager' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
            ],
        ],
    ];
