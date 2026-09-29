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
 * Scenario presets for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard\local\service;

/**
 * Scenario presets for the CATQuiz wizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scenario_preset_service {
    /**
     * Return wizard defaults for a predefined scenario.
     *
     * @param string $scenario
     * @return array
     */
    public static function get_preset(string $scenario): array {
        $defaults = [
            'wizardmode' => 'scenario',
            'scenario' => $scenario,
            'minquestioncount' => 0,
            'questioncount' => 15,
            'questioncountpersubscale' => 0,
            'timelimitenabled' => 0,
            'timelimitminutes' => 0,
            'precisionmode' => 'medium',
            'testgoal' => 'orientation',
            'completionenabled' => 0,
        ];

        switch ($scenario) {
            case 'learning_diagnostics':
                $defaults['questioncount'] = 18;
                $defaults['precisionmode'] = 'medium';
                $defaults['testgoal'] = 'diagnostics';
                break;
            case 'placement':
                $defaults['questioncount'] = 20;
                $defaults['timelimitenabled'] = 1;
                $defaults['timelimitminutes'] = 45;
                $defaults['precisionmode'] = 'high';
                $defaults['testgoal'] = 'placement';
                $defaults['completionenabled'] = 1;
                break;
            case 'checkup':
                $defaults['questioncount'] = 24;
                $defaults['timelimitenabled'] = 1;
                $defaults['timelimitminutes'] = 50;
                $defaults['precisionmode'] = 'high';
                $defaults['testgoal'] = 'diagnostics';
                break;
            case 'final':
                $defaults['questioncount'] = 30;
                $defaults['timelimitenabled'] = 1;
                $defaults['timelimitminutes'] = 60;
                $defaults['precisionmode'] = 'medium';
                $defaults['testgoal'] = 'final';
                $defaults['completionenabled'] = 1;
                break;
            case 'strength':
                $defaults['questioncount'] = 18;
                $defaults['precisionmode'] = 'medium';
                $defaults['testgoal'] = 'strength';
                break;
            case 'other':
            default:
                $defaults['testgoal'] = 'other';
                break;
        }

        return $defaults;
    }
}
