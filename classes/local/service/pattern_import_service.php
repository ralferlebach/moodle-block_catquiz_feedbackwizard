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
 * Validates settings patterns and maps them back into a wizard state.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

use block_catquiz_feedbackwizard\catquiz_data;

/**
 * Validates settings patterns and maps them back into a wizard state.
 *
 * Scale ids are site local, so an imported pattern is not trusted about them:
 * every id is checked against this site and dropped when it does not resolve.
 * The caller gets the resulting warnings and can show them before anything is
 * written.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pattern_import_service {
    /** @var array Warnings collected during the last conversion. */
    protected static $warnings = [];

    /**
     * Decode and validate a pattern document.
     *
     * @param string $json
     * @return array The decoded pattern.
     */
    public static function parse(string $json): array {
        $decoded = json_decode(trim($json), true);

        if (!is_array($decoded)) {
            throw new \moodle_exception('error:patterninvalidjson', 'block_catquiz_feedbackwizard');
        }

        if (($decoded['format'] ?? '') !== pattern_export_service::FORMAT) {
            throw new \moodle_exception('error:patternwrongformat', 'block_catquiz_feedbackwizard');
        }

        $version = (int)($decoded['version'] ?? 0);
        if ($version < 1 || $version > pattern_export_service::VERSION) {
            throw new \moodle_exception(
                'error:patternunsupportedversion',
                'block_catquiz_feedbackwizard',
                '',
                $version
            );
        }

        foreach (['settings', 'feedback'] as $section) {
            if (!isset($decoded[$section]) || !is_array($decoded[$section])) {
                throw new \moodle_exception(
                    'error:patternmissingsection',
                    'block_catquiz_feedbackwizard',
                    '',
                    $section
                );
            }
        }

        return $decoded;
    }

    /**
     * Return the warnings collected by the last to_wizard_state() call.
     *
     * @return array
     */
    public static function get_warnings(): array {
        return self::$warnings;
    }

    /**
     * Convert a validated pattern into wizard state keys.
     *
     * @param array $pattern
     * @return array
     */
    public static function to_wizard_state(array $pattern): array {
        self::$warnings = [];

        $settings = (array)($pattern['settings'] ?? []);
        $state = [
            'scenario' => (string)($settings['scenario'] ?? ''),
            'testgoal' => (string)($settings['testgoal'] ?? ''),
            'precisionmode' => self::normalise_precision((string)($settings['precisionmode'] ?? 'medium')),
            'minquestioncount' => max(0, (int)($settings['minquestioncount'] ?? 0)),
            'questioncount' => max(0, (int)($settings['questioncount'] ?? 0)),
            'questioncountpersubscale' => max(0, (int)($settings['questioncountpersubscale'] ?? 0)),
            'timelimitenabled' => !empty($settings['timelimitenabled']) ? 1 : 0,
            'timelimitminutes' => max(0, (int)($settings['timelimitminutes'] ?? 0)),
            'completionenabled' => !empty($settings['completionenabled']) ? 1 : 0,
        ];

        $state += self::resolve_scales((array)($pattern['scales'] ?? []));
        $state += self::map_feedback((array)($pattern['feedback'] ?? []));
        $state += self::map_routing((array)($pattern['routing'] ?? []));

        return $state;
    }

    /**
     * Resolve the scale references of a pattern against this site.
     *
     * @param array $scales
     * @return array
     */
    protected static function resolve_scales(array $scales): array {
        $mainid = (int)($scales['main']['id'] ?? 0);
        $mainname = (string)($scales['main']['name'] ?? '');

        if ($mainid > 0 && catquiz_data::get_scale_by_id($mainid) === null) {
            self::$warnings[] = get_string(
                'warning:patternscalemissing',
                'block_catquiz_feedbackwizard',
                $mainname !== '' ? $mainname : (string)$mainid
            );
            $mainid = 0;
        }

        $subscaleids = [];
        foreach ((array)($scales['subscales'] ?? []) as $subscale) {
            $subscaleid = (int)($subscale['id'] ?? 0);
            if ($subscaleid < 1) {
                continue;
            }
            if (catquiz_data::get_scale_by_id($subscaleid) === null) {
                self::$warnings[] = get_string(
                    'warning:patternscalemissing',
                    'block_catquiz_feedbackwizard',
                    (string)($subscale['name'] ?? $subscaleid)
                );
                continue;
            }
            $subscaleids[] = $subscaleid;
        }

        return [
            'mainscaleid' => $mainid,
            'subscaleids' => $subscaleids,
        ];
    }

    /**
     * Map the feedback section onto the flat wizard field names.
     *
     * @param array $feedback
     * @return array
     */
    protected static function map_feedback(array $feedback): array {
        $ranges = array_values((array)($feedback['ranges'] ?? []));
        $rangecount = test_config_normalizer::normalise_feedback_range_count(
            (int)($feedback['rangecount'] ?? count($ranges))
        );

        $state = [
            'reportingstrategy' => (string)($feedback['reportingstrategy'] ?? 'main_only'),
            'feedbackrangecount' => $rangecount,
        ];

        if (empty($feedback['includes_texts'])) {
            self::$warnings[] = get_string('warning:patternwithouttexts', 'block_catquiz_feedbackwizard');
        }

        for ($index = 1; $index <= $rangecount; $index++) {
            $range = (array)($ranges[$index - 1] ?? []);
            $state['feedbacklabel_' . $index] = (string)($range['label'] ?? '');
            $state['feedbacklower_' . $index] = isset($range['lower']) ? (float)$range['lower'] : 0;
            $state['feedbackupper_' . $index] = isset($range['upper']) ? (float)$range['upper'] : 0;
            $state['feedbacktext_' . $index] = (string)($range['text'] ?? '');
            $state['feedbacktemplateformat_' . $index] = feedback_template_service::normalise_template_format(
                (string)($range['templateformat'] ?? 'mustache')
            );
            $state['feedbackactioncourseenabled_' . $index] = !empty($range['actioncourseenabled']) ? 1 : 0;
            $state['feedbackactioncoursetarget_' . $index] = (string)($range['actioncoursetarget'] ?? '');
            $state['feedbackactiongroupenabled_' . $index] = !empty($range['actiongroupenabled']) ? 1 : 0;
            $state['feedbackactiongrouptarget_' . $index] = (string)($range['actiongrouptarget'] ?? '');
        }

        return $state;
    }

    /**
     * Map the routing section onto the flat wizard field names.
     *
     * @param array $routing
     * @return array
     */
    protected static function map_routing(array $routing): array {
        $matching = matching_config_service::normalise_matching($routing);

        if ($matching['categoryid'] > 0 && !feature_settings_service::is_target_category_allowed($matching['categoryid'])) {
            self::$warnings[] = get_string('warning:patterncategorynotallowed', 'block_catquiz_feedbackwizard');
            $matching['categoryid'] = 0;
        }

        return [
            'matchingmode' => $matching['mode'],
            'matchingcategoryid' => $matching['categoryid'],
            'matchingcoursefield' => $matching['coursefield'],
            'matchingoperator' => $matching['operator'],
            'matchingpattern' => $matching['pattern'],
            'matchingtargettype' => $matching['targettype'],
            'matchingtargetvalue' => $matching['targetvalue'],
            'matchingcsv' => $matching['csv'],
        ];
    }

    /**
     * Normalise a precision mode coming from an untrusted document.
     *
     * @param string $precisionmode
     * @return string
     */
    protected static function normalise_precision(string $precisionmode): string {
        if (!in_array($precisionmode, ['low', 'medium', 'high'], true)) {
            return 'medium';
        }
        return $precisionmode;
    }
}
