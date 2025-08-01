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
 * Script to get comments from DB
 *
 * @package     mod_nextblocks
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

global $DB;

$id = optional_param('id', 0, PARAM_INT);

$n = optional_param('n', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('nextblocks', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('nextblocks', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('nextblocks', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('nextblocks', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

$context = context_module::instance($cm->id);
require_capability('mod/nextblocks:view', $context);

$block_id = required_param('block_id', PARAM_ALPHANUMEXT);
$comments = $DB->get_records_sql("
    SELECT c.*, u.firstname, u.lastname 
    FROM {blockly_comments} c
    JOIN {user} u ON u.id = c.userid
    WHERE blockid = :blockid
    AND contextid = :contextid
    ORDER BY timecreated DESC",
    ['blockid' => $block_id, 'contextid' => $context->id]
);

echo json_encode(array_values($comments));