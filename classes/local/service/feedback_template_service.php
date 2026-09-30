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
    /** @var array Tokens the wizard resolves when writing to the engine. */
    const SUPPORTED_TOKENS = [
        'course.fullname',
        'result.ranklabel',
        'result.scalename',
        'test.name',
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
        return self::SUPPORTED_TOKENS;
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
            if (!in_array($token, self::SUPPORTED_TOKENS, true)) {
                continue;
            }
            $rendered = str_replace('{{' . $token . '}}', (string)$value, $rendered);
        }

        return $rendered;
    }
}
