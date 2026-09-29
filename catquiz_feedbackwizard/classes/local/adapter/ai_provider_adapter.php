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
 * Adapter over the Moodle AI subsystem.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\adapter;

/**
 * Adapter over the Moodle AI subsystem.
 *
 * The wizard never talks to an AI vendor itself. It hands a prompt to
 * core_ai and takes back text, so provider choice, credentials, rate limits,
 * the user policy and the action log all stay where a site administrator can
 * see and control them.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_provider_adapter {
    /** @var string Action class used for text refinement. */
    const ACTION_CLASS = '\core_ai\aiactions\generate_text';

    /** @var string Manager class of the AI subsystem. */
    const MANAGER_CLASS = '\core_ai\manager';

    /**
     * Return whether the AI subsystem exists in this Moodle version.
     *
     * @return bool
     */
    public static function is_supported(): bool {
        return class_exists(self::MANAGER_CLASS) && class_exists(self::ACTION_CLASS);
    }

    /**
     * Return the AI manager instance.
     *
     * Everything goes through an instance on purpose. Moodle 4.5 declares
     * is_action_available() and is_action_enabled() static, Moodle 5.0 turned
     * them into instance methods. PHP allows calling a static method on an
     * instance but not the other way round, so the instance is the only call
     * style that works on every supported branch.
     *
     * @return object
     */
    protected static function get_manager(): object {
        return \core\di::get(ltrim(self::MANAGER_CLASS, '\\'));
    }

    /**
     * Return whether text generation is available and enabled for a provider.
     *
     * @return bool
     */
    public static function is_available(): bool {
        if (!self::is_supported()) {
            return false;
        }

        return (bool)self::get_manager()->is_action_available(self::ACTION_CLASS);
    }

    /**
     * Return whether the user accepted the AI policy for this context.
     *
     * @param int $userid
     * @param int $contextid
     * @return bool
     */
    public static function policy_accepted(int $userid, int $contextid): bool {
        if (!self::is_supported()) {
            return false;
        }

        return (bool)self::get_manager()->user_policy_accepted($userid, $contextid);
    }

    /**
     * Send one prompt to the AI subsystem and return the generated text.
     *
     * @param string $prompt The complete prompt.
     * @param int $userid The user on whose behalf the action runs.
     * @param int $contextid The context the action belongs to.
     * @return string The generated text, or an empty string on failure.
     */
    public static function generate_text(string $prompt, int $userid, int $contextid): string {
        if (trim($prompt) === '' || !self::is_available()) {
            return '';
        }

        $actionclass = self::ACTION_CLASS;
        $action = new $actionclass(
            contextid: $contextid,
            userid: $userid,
            prompttext: $prompt,
        );

        $response = self::get_manager()->process_action($action);

        if (!$response->get_success()) {
            return '';
        }

        $data = $response->get_response_data();

        return trim((string)($data['generatedcontent'] ?? ''));
    }
}
