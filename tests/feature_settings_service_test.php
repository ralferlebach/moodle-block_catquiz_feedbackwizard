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
 * Unit tests for the feature settings service.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\service\feature_settings_service;

/**
 * Unit tests for the feature settings service.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\service\feature_settings_service
 */
final class feature_settings_service_test extends \advanced_testcase {
    /**
     * Optional features must be off unless an administrator opted in.
     *
     * @return void
     */
    public function test_optional_features_are_disabled_by_default(): void {
        $this->resetAfterTest();

        $this->assertFalse(feature_settings_service::is_course_provisioning_enabled());
        $this->assertFalse(feature_settings_service::is_group_autocreate_enabled());
        $this->assertFalse(feature_settings_service::is_ai_refinement_enabled());
    }

    /**
     * Enabling a setting must be reflected by the service.
     *
     * @return void
     */
    public function test_settings_are_read_from_config(): void {
        $this->resetAfterTest();

        set_config('enable_courseprovisioning', 1, 'block_catquiz_feedbackwizard');
        set_config('enable_ai_feedback_refinement', 1, 'block_catquiz_feedbackwizard');

        $this->assertTrue(feature_settings_service::is_course_provisioning_enabled());
        $this->assertTrue(feature_settings_service::is_ai_refinement_enabled());
        $this->assertFalse(feature_settings_service::is_group_autocreate_enabled());
    }

    /**
     * An unset or invalid draft lifetime must fall back to the default.
     *
     * @return void
     */
    public function test_draft_ttl_falls_back_to_default(): void {
        $this->resetAfterTest();

        $this->assertEquals(
            feature_settings_service::DEFAULT_DRAFT_TTL_HOURS,
            feature_settings_service::get_draft_ttl_hours()
        );

        set_config('draft_ttl_hours', 0, 'block_catquiz_feedbackwizard');
        $this->assertEquals(
            feature_settings_service::DEFAULT_DRAFT_TTL_HOURS,
            feature_settings_service::get_draft_ttl_hours()
        );

        set_config('draft_ttl_hours', 12, 'block_catquiz_feedbackwizard');
        $this->assertEquals(12, feature_settings_service::get_draft_ttl_hours());
    }

    /**
     * Allowed target categories must be parsed into a clean list of ids.
     *
     * @return void
     */
    public function test_allowed_target_categories(): void {
        $this->resetAfterTest();

        $this->assertSame([], feature_settings_service::get_allowed_target_categories());
        $this->assertTrue(feature_settings_service::is_target_category_allowed(7));

        set_config('allowed_target_categories', '3, 7,7, 0', 'block_catquiz_feedbackwizard');

        $this->assertSame([3, 7], feature_settings_service::get_allowed_target_categories());
        $this->assertTrue(feature_settings_service::is_target_category_allowed(7));
        $this->assertFalse(feature_settings_service::is_target_category_allowed(9));
    }

    /**
     * Submitted state for disabled features must be dropped server side.
     *
     * @return void
     */
    public function test_sanitise_wizard_state_strips_disabled_features(): void {
        $this->resetAfterTest();

        $state = [
            'feedbacklabel_1' => 'Support',
            'feedbackactioncourseenabled_1' => 1,
            'feedbackactioncoursetarget_1' => 'REMEDIAL-01',
            'feedbackactiongroupenabled_1' => 1,
            'feedbackactiongrouptarget_1' => 'Group A',
            'feedbackranges' => [
                [
                    'label' => 'Support',
                    'actioncourseenabled' => 1,
                    'actioncoursetarget' => 'REMEDIAL-01',
                    'actiongroupenabled' => 1,
                    'actiongrouptarget' => 'Group A',
                ],
            ],
        ];

        $sanitised = feature_settings_service::sanitise_wizard_state($state);

        $this->assertArrayNotHasKey('feedbackactioncourseenabled_1', $sanitised);
        $this->assertArrayNotHasKey('feedbackactiongrouptarget_1', $sanitised);
        $this->assertEquals(0, $sanitised['feedbackranges'][0]['actioncourseenabled']);
        $this->assertEquals(0, $sanitised['feedbackranges'][0]['actiongroupenabled']);
        $this->assertSame('', $sanitised['feedbackranges'][0]['actioncoursetarget']);
        $this->assertSame('Support', $sanitised['feedbacklabel_1']);
    }

    /**
     * Enabled features must survive sanitising untouched.
     *
     * @return void
     */
    public function test_sanitise_wizard_state_keeps_enabled_features(): void {
        $this->resetAfterTest();

        set_config('enable_courseprovisioning', 1, 'block_catquiz_feedbackwizard');
        set_config('enable_groupautocreate', 1, 'block_catquiz_feedbackwizard');

        $state = [
            'feedbackactioncourseenabled_1' => 1,
            'feedbackactioncoursetarget_1' => 'REMEDIAL-01',
            'feedbackactiongroupenabled_1' => 1,
            'feedbackactiongrouptarget_1' => 'Group A',
        ];

        $this->assertSame($state, feature_settings_service::sanitise_wizard_state($state));
    }
}
