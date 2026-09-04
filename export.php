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
 * Downloads the current wizard configuration as a settings pattern.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use block_catquiz_feedbackwizard\local\service\pattern_export_service;
use block_catquiz_feedbackwizard\persistent\draft as draft_persistent;

$draftid = required_param('draftid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

require_sesskey();

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('block/catquiz_feedbackwizard:use', $context);

$draft = new draft_persistent($draftid);

// A draft belongs to one user in one course. Both have to match, otherwise the
// export would be a way to read a colleague's unfinished configuration.
if ((int)$draft->get('userid') !== (int)$USER->id || (int)$draft->get('courseid') !== $courseid) {
    throw new moodle_exception('error:patternexportdenied', 'block_catquiz_feedbackwizard');
}

$state = json_decode((string)$draft->get('datajson'), true);
if (!is_array($state)) {
    $state = [];
}

$pattern = pattern_export_service::build_pattern($state);
$json = pattern_export_service::to_json($pattern);

send_file(
    $json,
    pattern_export_service::build_filename($pattern),
    0,
    0,
    true,
    true,
    'application/json'
);
