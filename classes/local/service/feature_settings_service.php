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
 * Central access point for the admin settings that gate optional features.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

/**
 * Central access point for the admin settings that gate optional features.
 *
 * Every optional feature of the Settings Wizard is off by default and may only
 * be offered in the UI once an administrator opted in. All gating decisions go
 * through this class so that no step class or template has to read config
 * values directly.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feature_settings_service {
    /** @var string Plugin component used for get_config() lookups. */
    const COMPONENT = 'block_catquiz_feedbackwizard';

    /** @var int Default lifetime of a wizard draft in hours. */
    const DEFAULT_DRAFT_TTL_HOURS = 72;

    /** @var int Default maximum size of an imported pattern file in bytes. */
    const DEFAULT_PATTERN_MAXBYTES = 262144;

    /**
     * Return one raw plugin config value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected static function get_setting(string $name, $default) {
        $value = get_config(self::COMPONENT, $name);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }

    /**
     * Return whether automatic course creation or course requests are allowed.
     *
     * @return bool
     */
    public static function is_course_provisioning_enabled(): bool {
        return (int)self::get_setting('enable_courseprovisioning', 0) === 1;
    }

    /**
     * Return whether automatic group creation is allowed.
     *
     * @return bool
     */
    public static function is_group_autocreate_enabled(): bool {
        return (int)self::get_setting('enable_groupautocreate', 0) === 1;
    }

    /**
     * Return whether AI based refinement of feedback texts is allowed.
     *
     * @return bool
     */
    public static function is_ai_refinement_enabled(): bool {
        return (int)self::get_setting('enable_ai_feedback_refinement', 0) === 1;
    }

    /**
     * Return the configured system prompt for AI refinement.
     *
     * @return string
     */
    public static function get_ai_system_prompt(): string {
        return trim((string)self::get_setting('ai_feedback_systemprompt', ''));
    }

    /**
     * Return the configured draft lifetime in hours.
     *
     * @return int
     */
    public static function get_draft_ttl_hours(): int {
        $hours = (int)self::get_setting('draft_ttl_hours', self::DEFAULT_DRAFT_TTL_HOURS);
        if ($hours < 1) {
            return self::DEFAULT_DRAFT_TTL_HOURS;
        }
        return $hours;
    }

    /**
     * Return the course category ids that may be used as provisioning targets.
     *
     * An empty array means that no restriction is configured.
     *
     * @return array
     */
    public static function get_allowed_target_categories(): array {
        $raw = (string)self::get_setting('allowed_target_categories', '');
        if (trim($raw) === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int)trim($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Return whether a target category may be used for provisioning.
     *
     * @param int $categoryid
     * @return bool
     */
    public static function is_target_category_allowed(int $categoryid): bool {
        $allowed = self::get_allowed_target_categories();
        if (empty($allowed)) {
            return true;
        }
        return in_array($categoryid, $allowed, true);
    }

    /**
     * Return the maximum accepted size of an imported pattern file in bytes.
     *
     * @return int
     */
    public static function get_pattern_import_maxbytes(): int {
        $bytes = (int)self::get_setting('pattern_import_maxfilesize', self::DEFAULT_PATTERN_MAXBYTES);
        if ($bytes < 1) {
            return self::DEFAULT_PATTERN_MAXBYTES;
        }
        return $bytes;
    }

    /**
     * Return whether exported patterns may carry feedback texts.
     *
     * @return bool
     */
    public static function pattern_export_includes_feedback_texts(): bool {
        return (int)self::get_setting('pattern_export_include_feedback_texts', 1) === 1;
    }

    /**
     * Remove wizard state that belongs to features the administrator disabled.
     *
     * The UI already hides disabled features, but a submitted form must never be
     * trusted on its own. This is the server side enforcement of the gating.
     *
     * @param array $state
     * @return array
     */
    public static function sanitise_wizard_state(array $state): array {
        $courseallowed = self::is_course_provisioning_enabled();
        $groupallowed = self::is_group_autocreate_enabled();
        $aiallowed = self::is_ai_refinement_enabled();

        if (!$aiallowed) {
            unset($state['useairefinement'], $state['aisystemprompt']);
        }

        if ($courseallowed && $groupallowed) {
            return $state;
        }

        foreach (array_keys($state) as $key) {
            if (!$courseallowed && preg_match('/^feedbackactioncourse(enabled|target)_\d+$/', $key)) {
                unset($state[$key]);
            }
            if (!$groupallowed && preg_match('/^feedbackactiongroup(enabled|target)_\d+$/', $key)) {
                unset($state[$key]);
            }
        }

        if (!empty($state['feedbackranges']) && is_array($state['feedbackranges'])) {
            foreach ($state['feedbackranges'] as $index => $range) {
                if (!is_array($range)) {
                    continue;
                }
                if (!$courseallowed) {
                    $range['actioncourseenabled'] = 0;
                    $range['actioncoursetarget'] = '';
                }
                if (!$groupallowed) {
                    $range['actiongroupenabled'] = 0;
                    $range['actiongrouptarget'] = '';
                }
                $state['feedbackranges'][$index] = $range;
            }
        }

        return $state;
    }
}
