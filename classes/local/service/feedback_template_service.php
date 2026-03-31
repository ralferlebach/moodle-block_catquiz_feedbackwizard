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
 * Provides supported feedback template tokens and simple preview rendering.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

/**
 * Provides supported feedback template tokens and simple preview rendering.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_template_service {
    /**
     * Get supported feedback tokens and their sample values.
     *
     * @return array
     */
    public static function get_supported_tokens(): array {
        return [
            '{{course.fullname}}' => [
                'description' => get_string('token:coursefullname', 'block_catquiz_feedbackwizard'),
                'sample' => 'Placement English B1',
            ],
            '{{result.ranklabel}}' => [
                'description' => get_string('token:resultranklabel', 'block_catquiz_feedbackwizard'),
                'sample' => 'Support',
            ],
            '{{result.scalename}}' => [
                'description' => get_string('token:resultscalename', 'block_catquiz_feedbackwizard'),
                'sample' => 'Overall language scale',
            ],
            '{{result.score}}' => [
                'description' => get_string('token:resultscore', 'block_catquiz_feedbackwizard'),
                'sample' => '0.82',
            ],
            '{{test.name}}' => [
                'description' => get_string('token:testname', 'block_catquiz_feedbackwizard'),
                'sample' => 'CAT placement test',
            ],
            '{{user.firstname}}' => [
                'description' => get_string('token:userfirstname', 'block_catquiz_feedbackwizard'),
                'sample' => 'Alex',
            ],
        ];
    }

    /**
     * Normalise the stored template format.
     *
     * @param string $format
     * @return string
     */
    public static function normalise_template_format(string $format): string {
        return $format === 'plain' ? 'plain' : 'mustache';
    }

    /**
     * Render a simple preview from a template and optional token map.
     *
     * @param string $template
     * @param string $format
     * @param array $tokens
     * @return string
     */
    public static function render_preview(string $template, string $format, array $tokens = []): string {
        if (self::normalise_template_format($format) === 'plain') {
            return $template;
        }

        $replacements = [];
        foreach (self::get_supported_tokens() as $token => $definition) {
            $replacements[$token] = (string)($tokens[$token] ?? $definition['sample']);
        }

        return strtr($template, $replacements);
    }

    /**
     * Build a short HTML help block listing supported template tokens.
     *
     * @return string
     */
    public static function get_token_help_html(): string {
        $items = [];
        foreach (self::get_supported_tokens() as $token => $definition) {
            $items[] = '<li><code>' . s($token) . '</code>: ' . s((string)$definition['description']) . '</li>';
        }

        return '<p>' . s(get_string('message:feedbacktokenintro', 'block_catquiz_feedbackwizard')) . '</p>'
            . '<ul>' . implode('', $items) . '</ul>';
    }
}
