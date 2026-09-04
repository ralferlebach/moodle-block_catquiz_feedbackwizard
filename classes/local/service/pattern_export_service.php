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
 * Builds exportable settings patterns from a wizard state.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

use block_catquiz_feedbackwizard\catquiz_data;

/**
 * Builds exportable settings patterns from a wizard state.
 *
 * A pattern describes how a CAT test is set up, not which test it was taken
 * from. Instance references (draft id, course id, test id) are deliberately
 * dropped, so a pattern carries no reference to a person, a course or an
 * attempt and can be moved between courses and sites.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pattern_export_service {
    /** @var string Format identifier written into every exported pattern. */
    const FORMAT = 'catquiz-settings-pattern';

    /** @var int Current format version. */
    const VERSION = 1;

    /**
     * Build a settings pattern from a wizard state.
     *
     * @param array $wizardstate
     * @param string $name Optional human readable name for the pattern.
     * @return array
     */
    public static function build_pattern(array $wizardstate, string $name = ''): array {
        $mainscaleid = (int)($wizardstate['mainscaleid'] ?? 0);
        $subscaleids = array_values(array_filter(array_map('intval', (array)($wizardstate['subscaleids'] ?? []))));

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'meta' => [
                'name' => $name !== '' ? $name : self::build_default_name($wizardstate),
                'generator' => 'block_catquiz_feedbackwizard',
            ],
            'settings' => [
                'scenario' => (string)($wizardstate['scenario'] ?? ''),
                'testgoal' => (string)($wizardstate['testgoal'] ?? ''),
                'precisionmode' => (string)($wizardstate['precisionmode'] ?? 'medium'),
                'minquestioncount' => (int)($wizardstate['minquestioncount'] ?? 0),
                'questioncount' => (int)($wizardstate['questioncount'] ?? 0),
                'questioncountpersubscale' => (int)($wizardstate['questioncountpersubscale'] ?? 0),
                'timelimitenabled' => !empty($wizardstate['timelimitenabled']) ? 1 : 0,
                'timelimitminutes' => (int)($wizardstate['timelimitminutes'] ?? 0),
                'completionenabled' => !empty($wizardstate['completionenabled']) ? 1 : 0,
            ],
            'scales' => self::build_scale_section($mainscaleid, $subscaleids),
            'feedback' => self::build_feedback_section($wizardstate, $mainscaleid),
            'routing' => matching_config_service::normalise_matching([
                'mode' => (string)($wizardstate['matchingmode'] ?? 'none'),
                'categoryid' => (int)($wizardstate['matchingcategoryid'] ?? 0),
                'coursefield' => (string)($wizardstate['matchingcoursefield'] ?? 'shortname'),
                'operator' => (string)($wizardstate['matchingoperator'] ?? 'contains'),
                'pattern' => (string)($wizardstate['matchingpattern'] ?? ''),
                'targettype' => (string)($wizardstate['matchingtargettype'] ?? 'catscale'),
                'targetvalue' => (string)($wizardstate['matchingtargetvalue'] ?? ''),
                'csv' => (string)($wizardstate['matchingcsv'] ?? ''),
            ]),
        ];
    }

    /**
     * Build the scale section of a pattern.
     *
     * Scale ids are site local. The names travel with them so that an import on
     * another site can tell the user what the pattern expected, even when the
     * ids do not resolve there.
     *
     * @param int $mainscaleid
     * @param array $subscaleids
     * @return array
     */
    protected static function build_scale_section(int $mainscaleid, array $subscaleids): array {
        $mainscale = $mainscaleid > 0 ? catquiz_data::get_scale_by_id($mainscaleid) : null;

        $subscales = [];
        foreach ($subscaleids as $subscaleid) {
            $record = catquiz_data::get_scale_by_id($subscaleid);
            $subscales[] = [
                'id' => $subscaleid,
                'name' => $record ? (string)$record->name : '',
            ];
        }

        return [
            'main' => [
                'id' => $mainscaleid,
                'name' => $mainscale ? (string)$mainscale->name : '',
            ],
            'subscales' => $subscales,
        ];
    }

    /**
     * Build the feedback section of a pattern.
     *
     * @param array $wizardstate
     * @param int $mainscaleid
     * @return array
     */
    protected static function build_feedback_section(array $wizardstate, int $mainscaleid): array {
        $range = catquiz_data::get_scale_range($mainscaleid);
        $rangecount = test_config_normalizer::normalise_feedback_range_count(
            (int)($wizardstate['feedbackrangecount'] ?? 0)
        );
        $ranges = test_config_normalizer::extract_feedback_ranges_from_state(
            $wizardstate,
            $rangecount,
            (float)$range['min'],
            (float)$range['max']
        );

        $includetexts = feature_settings_service::pattern_export_includes_feedback_texts();

        $exportranges = [];
        foreach (array_values($ranges) as $feedbackrange) {
            $exportranges[] = [
                'label' => (string)($feedbackrange['label'] ?? ''),
                'lower' => isset($feedbackrange['lower']) ? (float)$feedbackrange['lower'] : null,
                'upper' => isset($feedbackrange['upper']) ? (float)$feedbackrange['upper'] : null,
                'text' => $includetexts ? (string)($feedbackrange['text'] ?? '') : '',
                'templateformat' => feedback_template_service::normalise_template_format(
                    (string)($feedbackrange['templateformat'] ?? 'mustache')
                ),
                'actioncourseenabled' => !empty($feedbackrange['actioncourseenabled']) ? 1 : 0,
                'actioncoursetarget' => (string)($feedbackrange['actioncoursetarget'] ?? ''),
                'actiongroupenabled' => !empty($feedbackrange['actiongroupenabled']) ? 1 : 0,
                'actiongrouptarget' => (string)($feedbackrange['actiongrouptarget'] ?? ''),
            ];
        }

        return [
            'reportingstrategy' => (string)($wizardstate['reportingstrategy'] ?? 'main_only'),
            'rangecount' => $rangecount,
            'includes_texts' => $includetexts ? 1 : 0,
            'ranges' => $exportranges,
        ];
    }

    /**
     * Build a fallback name for an unnamed pattern.
     *
     * @param array $wizardstate
     * @return string
     */
    protected static function build_default_name(array $wizardstate): string {
        $scenario = (string)($wizardstate['scenario'] ?? '');
        if ($scenario !== '') {
            return 'catquiz-pattern-' . $scenario;
        }
        return 'catquiz-pattern';
    }

    /**
     * Encode a pattern as pretty printed JSON.
     *
     * @param array $pattern
     * @return string
     */
    public static function to_json(array $pattern): string {
        return (string)json_encode($pattern, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build a safe download filename for a pattern.
     *
     * @param array $pattern
     * @return string
     */
    public static function build_filename(array $pattern): string {
        $name = (string)($pattern['meta']['name'] ?? 'catquiz-pattern');
        $name = clean_param($name, PARAM_FILE);
        if ($name === '') {
            $name = 'catquiz-pattern';
        }
        return $name . '.json';
    }
}
