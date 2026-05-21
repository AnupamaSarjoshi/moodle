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
 * Add / edit a course-scoped learning outcome.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_learningoutcomes\manager;
use local_learningoutcomes\form\edit_outcome_form;

$courseid = required_param('courseid', PARAM_INT);
$id       = optional_param('id', 0, PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/learningoutcomes:manage', $context);

if (!get_config('local_learningoutcomes', 'enabled')) {
    throw new moodle_exception('error:nopermission', 'local_learningoutcomes');
}

$manageurl = new moodle_url('/local/learningoutcomes/manage.php', ['courseid' => $courseid]);
$editurl   = new moodle_url('/local/learningoutcomes/edit.php', ['courseid' => $courseid]);
if ($id) {
    $editurl->param('id', $id);
}

$PAGE->set_url($editurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($course->fullname);

$heading = $id
    ? get_string('editoutcome', 'local_learningoutcomes')
    : get_string('addoutcome', 'local_learningoutcomes');

$PAGE->set_title($heading);

// Load existing data when editing.
$currentoutcome = null;
if ($id > 0) {
    $currentoutcome = $DB->get_record('grade_outcomes', ['id' => $id, 'courseid' => $courseid], '*', MUST_EXIST);
}

$customdata = [
    'courseid' => $courseid,
    'context'  => $context,
];
$form = new edit_outcome_form($editurl, $customdata);

if ($currentoutcome) {
    $form->set_data([
        'id'               => $currentoutcome->id,
        'courseid'         => $courseid,
        'fullname'         => $currentoutcome->fullname,
        'shortname'        => $currentoutcome->shortname,
        'scaleid'          => $currentoutcome->scaleid ?? 0,
        'description_editor' => [
            'text'   => $currentoutcome->description ?? '',
            'format' => $currentoutcome->descriptionformat,
        ],
    ]);
} else {
    $form->set_data(['courseid' => $courseid]);
}

if ($form->is_cancelled()) {
    redirect($manageurl);
}

if ($data = $form->get_data()) {
    $scaleid     = (!empty($data->scaleid) && $data->scaleid > 0) ? (int) $data->scaleid : null;
    $description = $data->description_editor['text'] ?? '';
    $descformat  = $data->description_editor['format'] ?? FORMAT_HTML;

    if ($data->id) {
        manager::update_outcome(
            $data->id,
            clean_param($data->fullname, PARAM_TEXT),
            clean_param($data->shortname, PARAM_NOTAGS),
            $description,
            $descformat,
            $scaleid
        );
        redirect($manageurl, get_string('outcomeupdated', 'local_learningoutcomes'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        manager::create_outcome(
            $courseid,
            clean_param($data->fullname, PARAM_TEXT),
            clean_param($data->shortname, PARAM_NOTAGS),
            $description,
            $descformat,
            $scaleid
        );
        redirect($manageurl, get_string('outcomeadded', 'local_learningoutcomes'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
$PAGE->navbar->add(
    get_string('manageoutcomes', 'local_learningoutcomes'),
    $manageurl
);
$PAGE->navbar->add($heading, $editurl);
echo $OUTPUT->heading($heading, 2);
$form->display();
echo $OUTPUT->footer();
