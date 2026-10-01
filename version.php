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
 * Plugin version and other meta-data are defined here.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_catquiz_feedbackwizard';
$plugin->release = '0.4.22';
$plugin->version = 2026092811;
$plugin->requires = 2024100700;
$plugin->supported = [405, 502];

// Two engine stacks exist and they do not overlap:
// Moodle 4.5 uses local_catquiz 1.2.x (supported = [405, 405]) and
// Moodle 5.1+ uses local_catquiz 1.3.x (supported = [501, 503]).
// Moodle 5.0 is covered by neither, so the block cannot be installed there
// even though it sits inside the range above: the engine will not install.
// The minimums below are the lower of the two stacks, so that both satisfy
// them. See .github/scripts/fetch-engine.sh.
$plugin->dependencies = [
    'mod_adaptivequiz' => 2026090604,
    'adaptivequizcatmodel_catquiz' => 2026082704,
    'local_catquiz' => 2026092616,
];
$plugin->maturity = MATURITY_ALPHA;
