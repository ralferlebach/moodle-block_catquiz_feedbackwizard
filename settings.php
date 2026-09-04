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
 * Plugin administration pages are defined here.
 *
 * @package     block_catquiz_feedbackwizard
 * @category    admin
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'block_catquiz_feedbackwizard_settings',
        new lang_string('pluginname', 'block_catquiz_feedbackwizard')
    );

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'block_catquiz_feedbackwizard/optionalfeatures',
            new lang_string('settings:optionalfeatures', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:optionalfeatures_desc', 'block_catquiz_feedbackwizard')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_catquiz_feedbackwizard/enable_courseprovisioning',
            new lang_string('settings:enable_courseprovisioning', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:enable_courseprovisioning_desc', 'block_catquiz_feedbackwizard'),
            0
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_catquiz_feedbackwizard/enable_groupautocreate',
            new lang_string('settings:enable_groupautocreate', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:enable_groupautocreate_desc', 'block_catquiz_feedbackwizard'),
            0
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_catquiz_feedbackwizard/enable_ai_feedback_refinement',
            new lang_string('settings:enable_ai_feedback_refinement', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:enable_ai_feedback_refinement_desc', 'block_catquiz_feedbackwizard'),
            0
        ));

        $settings->add(new admin_setting_configtextarea(
            'block_catquiz_feedbackwizard/ai_feedback_systemprompt',
            new lang_string('settings:ai_feedback_systemprompt', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:ai_feedback_systemprompt_desc', 'block_catquiz_feedbackwizard'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_heading(
            'block_catquiz_feedbackwizard/dataretention',
            new lang_string('settings:dataretention', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:dataretention_desc', 'block_catquiz_feedbackwizard')
        ));

        $settings->add(new admin_setting_configtext(
            'block_catquiz_feedbackwizard/draft_ttl_hours',
            new lang_string('settings:draft_ttl_hours', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:draft_ttl_hours_desc', 'block_catquiz_feedbackwizard'),
            72,
            PARAM_INT
        ));

        $settings->add(new admin_setting_heading(
            'block_catquiz_feedbackwizard/patterns',
            new lang_string('settings:patterns', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:patterns_desc', 'block_catquiz_feedbackwizard')
        ));

        $settings->add(new admin_setting_configtext(
            'block_catquiz_feedbackwizard/allowed_target_categories',
            new lang_string('settings:allowed_target_categories', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:allowed_target_categories_desc', 'block_catquiz_feedbackwizard'),
            '',
            PARAM_SEQUENCE
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_catquiz_feedbackwizard/pattern_export_include_feedback_texts',
            new lang_string('settings:pattern_export_include_feedback_texts', 'block_catquiz_feedbackwizard'),
            new lang_string('settings:pattern_export_include_feedback_texts_desc', 'block_catquiz_feedbackwizard'),
            1
        ));
    }
}
