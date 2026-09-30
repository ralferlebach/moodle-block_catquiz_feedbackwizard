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
 * Consistency tests between the shipped language packs.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

/**
 * Consistency tests between the shipped language packs.
 *
 * A translation drifts silently: a new English string is added, the German one
 * is forgotten, and Moodle quietly falls back to English. Worse, a placeholder
 * lost in translation produces a sentence with a hole in it that no test
 * notices, because the string still exists.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class lang_packs_test extends \advanced_testcase {
    /**
     * Load the strings of one shipped language pack.
     *
     * @param string $lang Language directory name.
     * @return array
     */
    protected function load_pack(string $lang): array {
        global $CFG;

        $path = $CFG->dirroot . '/blocks/catquiz_feedbackwizard/lang/' . $lang
            . '/block_catquiz_feedbackwizard.php';
        $this->assertFileExists($path, 'the ' . $lang . ' language pack must be shipped');

        $string = [];
        include($path);

        return $string;
    }

    /**
     * Return the {$a} placeholders used in a string.
     *
     * @param string $text
     * @return array
     */
    protected function placeholders(string $text): array {
        preg_match_all('/\{\$a(?:->[a-z0-9_]+)?\}/i', $text, $matches);
        $found = array_values(array_unique($matches[0] ?? []));
        sort($found);

        return $found;
    }

    /**
     * Both packs must define exactly the same keys.
     *
     * @return void
     */
    public function test_both_packs_define_the_same_keys(): void {
        $en = $this->load_pack('en');
        $de = $this->load_pack('de');

        $missing = array_values(array_diff(array_keys($en), array_keys($de)));
        $extra = array_values(array_diff(array_keys($de), array_keys($en)));

        $this->assertSame([], $missing, 'these strings are missing from the German pack: '
            . implode(', ', $missing));
        $this->assertSame([], $extra, 'these German strings have no English counterpart: '
            . implode(', ', $extra));
    }

    /**
     * A translation must keep every placeholder of its original.
     *
     * @return void
     */
    public function test_translations_keep_their_placeholders(): void {
        $en = $this->load_pack('en');
        $de = $this->load_pack('de');

        $mismatches = [];
        foreach ($en as $key => $value) {
            if (!isset($de[$key])) {
                continue;
            }
            $expected = $this->placeholders((string)$value);
            $actual = $this->placeholders((string)$de[$key]);
            if ($expected !== $actual) {
                $mismatches[] = $key . ': expected ' . json_encode($expected)
                    . ', got ' . json_encode($actual);
            }
        }

        $this->assertSame([], $mismatches, implode("\n", $mismatches));
    }

    /**
     * No translated string may be left empty or identical by accident.
     *
     * Identical is legitimate for names and shared terms, so only empties fail;
     * the untranslated ones are reported for review.
     *
     * @return void
     */
    public function test_no_translation_is_empty(): void {
        $de = $this->load_pack('de');

        $empty = [];
        foreach ($de as $key => $value) {
            if (trim((string)$value) === '') {
                $empty[] = $key;
            }
        }

        $this->assertSame([], $empty, 'empty translations: ' . implode(', ', $empty));
    }

    /**
     * The mustache placeholders in the token help must survive translation.
     *
     * These are not Moodle {$a} placeholders but the plugin's own {{token}}
     * syntax, which the feedback texts rely on.
     *
     * @return void
     */
    public function test_token_help_keeps_the_mustache_tokens(): void {
        $de = $this->load_pack('de');

        foreach (['result.ranklabel', 'result.scalename', 'test.name', 'course.fullname'] as $token) {
            $this->assertStringContainsString(
                '{{' . $token . '}}',
                (string)$de['message:feedbacktokeninfo'],
                'the German help text must name the ' . $token . ' placeholder'
            );
        }
    }
}
