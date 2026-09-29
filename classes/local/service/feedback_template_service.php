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
 * Helper methods for feedback text templates.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

/**
 * Helper methods for feedback text templates.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_template_service {
    /** @var array Supported tokens for simple preview rendering. */
    const SUPPORTED_TOKENS = [
        'course.fullname' => 'Example course',
        'result.ranklabel' => 'Support',
        'result.scalename' => 'Main scale',
        'test.name' => 'Example CAT test',
    ];

    /**
     * Normalise one template format value.
     *
     * @param string $templateformat
     * @return string
     */
    public static function normalise_template_format(string $templateformat): string {
        return $templateformat === 'plain' ? 'plain' : 'mustache';
    }

    /**
     * Return supported token placeholders.
     *
     * @return array
     */
    public static function get_supported_tokens(): array {
        return array_keys(self::SUPPORTED_TOKENS);
    }

    /**
     * Build token help text for the wizard.
     *
     * @return string
     */
    public static function build_token_help_text(): string {
        $tokens = array_map(static function (string $token): string {
            return '{{' . $token . '}}';
        }, self::get_supported_tokens());

        return implode(', ', $tokens);
    }

    /**
     * Extract used tokens from one feedback text.
     *
     * @param string $text
     * @return array
     */
    public static function extract_tokens(string $text): array {
        if ($text == '') {
            return [];
        }

        preg_match_all('/{{\s*([a-z0-9_\.]+)\s*}}/i', $text, $matches);
        $tokens = array_values(array_unique($matches[1] ?? []));
        sort($tokens);
        return $tokens;
    }

    /**
     * Return unsupported tokens used in one feedback text.
     *
     * @param string $text
     * @return array
     */
    public static function get_unknown_tokens(string $text): array {
        return array_values(array_diff(self::extract_tokens($text), self::get_supported_tokens()));
    }

    /**
     * Replace the supported tokens with their real values.
     *
     * local_catquiz stores and displays feedback texts verbatim — see
     * customscalefeedback::getfeedbackforrange(), which only rewrites file
     * URLs. A placeholder left in the stored text would therefore be shown to
     * the student as literal "{{result.scalename}}".
     *
     * Every supported token is known at write time, because each stored text
     * belongs to exactly one scale and one feedback range, so the substitution
     * happens once, on the way into the engine.
     *
     * Tokens without a value in $values are left untouched rather than
     * silently emptied.
     *
     * @param string $text
     * @param array $values Token name (without braces) => replacement value.
     * @return string
     */
    public static function render_final(string $text, array $values): string {
        if (trim($text) === '') {
            return $text;
        }

        $rendered = $text;
        foreach ($values as $token => $value) {
            if (!array_key_exists($token, self::SUPPORTED_TOKENS)) {
                continue;
            }
            $rendered = str_replace('{{' . $token . '}}', (string)$value, $rendered);
        }

        return $rendered;
    }

    /**
     * Render a feedback text with the example values.
     *
     * @param string $text
     * @param string $templateformat
     * @return string
     */
    public static function render_preview(string $text, string $templateformat): string {
        $templateformat = self::normalise_template_format($templateformat);
        if ($templateformat === 'plain') {
            return $text;
        }

        $preview = $text;
        foreach (self::SUPPORTED_TOKENS as $token => $value) {
            $preview = preg_replace('/{{\s*' . preg_quote($token, '/') . '\s*}}/', $value, $preview);
        }
        return $preview;
    }
}
