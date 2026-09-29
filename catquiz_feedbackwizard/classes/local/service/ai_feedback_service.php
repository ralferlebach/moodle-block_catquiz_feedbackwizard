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
 * Optional AI refinement of feedback texts.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

use block_catquiz_feedbackwizard\local\adapter\ai_provider_adapter;

/**
 * Optional AI refinement of feedback texts.
 *
 * Feedback texts are written once, for a result range, and shown to whoever
 * lands in that range later. They are not about a person, and nothing about a
 * person is added here: the payload is the text itself plus wording
 * instructions. No names, no course data, no results.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_feedback_service {
    /** @var int Longest feedback text accepted for refinement, in characters. */
    const MAX_TEXT_LENGTH = 4000;

    /** @var string Fallback system prompt when the administrator configured none. */
    const DEFAULT_SYSTEM_PROMPT = 'You are helping a teacher polish short feedback texts for a '
        . 'competence test. Improve grammar, flow and tone. Keep the meaning, the language and '
        . 'the approximate length. Keep every placeholder of the form {{name}} exactly as it is. '
        . 'Reply with the improved text only, without commentary or quotation marks.';

    /** @var array Messages describing what happened during the last run. */
    protected static $messages = [];

    /**
     * Return whether refinement may be offered at all.
     *
     * Both the plugin setting and the AI subsystem have to agree. The setting
     * alone is not enough: an administrator can enable the feature here while
     * no provider is configured site wide.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return feature_settings_service::is_ai_refinement_enabled() && ai_provider_adapter::is_available();
    }

    /**
     * Return the messages collected during the last refine_wizard_state() call.
     *
     * @return array
     */
    public static function get_messages(): array {
        return self::$messages;
    }

    /**
     * Build the prompt for one feedback text.
     *
     * @param string $text The text to refine.
     * @param string $instructions Optional extra wording instructions from the teacher.
     * @return string
     */
    public static function build_prompt(string $text, string $instructions = ''): string {
        $systemprompt = feature_settings_service::get_ai_system_prompt();
        if ($systemprompt === '') {
            $systemprompt = self::DEFAULT_SYSTEM_PROMPT;
        }

        $parts = [$systemprompt];

        $instructions = trim($instructions);
        if ($instructions !== '') {
            $parts[] = 'Additional instructions: ' . $instructions;
        }

        $parts[] = 'Text to improve:';
        $parts[] = $text;

        return implode("\n\n", $parts);
    }

    /**
     * Refine one feedback text.
     *
     * @param string $text
     * @param string $instructions
     * @param int $userid
     * @param int $contextid
     * @return string The refined text, or the original when refinement was not possible.
     */
    public static function refine_text(string $text, string $instructions, int $userid, int $contextid): string {
        $trimmed = trim($text);
        if ($trimmed === '' || \core_text::strlen($trimmed) > self::MAX_TEXT_LENGTH) {
            return $text;
        }

        $result = ai_provider_adapter::generate_text(
            self::build_prompt($trimmed, $instructions),
            $userid,
            $contextid
        );

        if ($result === '') {
            return $text;
        }

        // A response that lost the placeholders would silently break the
        // feedback, so the original text wins in that case.
        if (!self::placeholders_preserved($trimmed, $result)) {
            self::$messages[] = get_string('warning:airefinementplaceholders', 'block_catquiz_feedbackwizard');
            return $text;
        }

        return $result;
    }

    /**
     * Return whether both texts contain the same set of placeholders.
     *
     * @param string $original
     * @param string $refined
     * @return bool
     */
    public static function placeholders_preserved(string $original, string $refined): bool {
        $before = feedback_template_service::extract_tokens($original);
        $after = feedback_template_service::extract_tokens($refined);

        return empty(array_diff($before, $after));
    }

    /**
     * Refine every feedback text in a wizard state.
     *
     * @param array $state The wizard state.
     * @param int $userid The user on whose behalf the action runs.
     * @param int $contextid The context the action belongs to.
     * @return array The wizard state with refined texts.
     */
    public static function refine_wizard_state(array $state, int $userid, int $contextid): array {
        self::$messages = [];

        if (!self::is_available()) {
            self::$messages[] = get_string('warning:airefinementunavailable', 'block_catquiz_feedbackwizard');
            return $state;
        }

        if (!ai_provider_adapter::policy_accepted($userid, $contextid)) {
            self::$messages[] = get_string('warning:aipolicynotaccepted', 'block_catquiz_feedbackwizard');
            return $state;
        }

        $instructions = (string)($state['aiinstructions'] ?? '');
        $rangecount = test_config_normalizer::normalise_feedback_range_count(
            (int)($state['feedbackrangecount'] ?? 0)
        );

        $changed = 0;
        for ($index = 1; $index <= $rangecount; $index++) {
            $key = 'feedbacktext_' . $index;
            $original = (string)($state[$key] ?? '');
            if (trim($original) === '') {
                continue;
            }

            $refined = self::refine_text($original, $instructions, $userid, $contextid);
            if ($refined !== $original) {
                $state[$key] = $refined;
                $changed++;
            }
        }

        // The checkbox is a one-shot request, not a persistent mode. Leaving it
        // set would refine already refined text on every pass through step 4.
        $state['useairefinement'] = 0;

        self::$messages[] = $changed > 0
            ? get_string('message:airefinementapplied', 'block_catquiz_feedbackwizard', $changed)
            : get_string('message:airefinementnochange', 'block_catquiz_feedbackwizard');

        return $state;
    }
}
