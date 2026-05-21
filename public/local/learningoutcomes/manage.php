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
 * Course-level learning outcomes management page.
 *
 * Lists existing outcomes for the course and allows editing teachers to add,
 * edit, delete, and reorder them.  Also exposes the course-level enabled
 * toggle.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_learningoutcomes\manager;
use local_learningoutcomes\form\course_settings_form;

$courseid = required_param('courseid', PARAM_INT);
$action   = optional_param('action', '', PARAM_ALPHA);
$id       = optional_param('id', 0, PARAM_INT);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/learningoutcomes:manage', $context);

// Check site feature switch.
if (!get_config('local_learningoutcomes', 'enabled')) {
    throw new moodle_exception('error:nopermission', 'local_learningoutcomes');
}

$manageurl    = new moodle_url('/local/learningoutcomes/manage.php', ['courseid' => $courseid]);
$editurl      = new moodle_url('/local/learningoutcomes/edit.php', ['courseid' => $courseid]);
$alignmenturl = new moodle_url('/local/learningoutcomes/alignment.php', ['courseid' => $courseid]);

$PAGE->set_url($manageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('manageoutcomes', 'local_learningoutcomes'));
$PAGE->set_heading($course->fullname);

// --- Handle delete action ------------------------------------------------

if ($action === 'delete' && $id > 0) {
    $outcome = $DB->get_record('grade_outcomes', ['id' => $id, 'courseid' => $courseid], '*', MUST_EXIST);

    if ($confirm) {
        require_sesskey();
        manager::delete_outcome($id, $courseid);
        redirect($manageurl, get_string('outcomedeleted', 'local_learningoutcomes'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deleteoutcomeconfirm', 'local_learningoutcomes', $outcome->fullname),
        new moodle_url($manageurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
        $manageurl
    );
    echo $OUTPUT->footer();
    exit;
}

// --- Handle course settings form ----------------------------------------

$settingsform = new course_settings_form($manageurl, null, 'post');
$currentsettings = manager::get_course_settings($courseid);
$settingsform->set_data([
    'courseid' => $courseid,
    'enabled'  => ($currentsettings->enabled === null ? '' : (string) $currentsettings->enabled),
]);

if ($settingsdata = $settingsform->get_data()) {
    $enabled = ($settingsdata->enabled === '') ? null : (int) $settingsdata->enabled;
    manager::save_course_settings($courseid, $enabled);
    redirect($manageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// --- Page output --------------------------------------------------------

echo $OUTPUT->header();

// Navigation breadcrumb.
$PAGE->navbar->add(get_string('manageoutcomes', 'local_learningoutcomes'), $manageurl);

echo $OUTPUT->heading(get_string('manageoutcomes', 'local_learningoutcomes'), 2);

// --- Course settings toggle -----------------------------------------

$settingsform->display();

// --- Outcomes list ---------------------------------------------------

$outcomes = manager::get_course_outcomes($courseid);
$mincount = (int) get_config('local_learningoutcomes', 'mincount') ?: 3;

echo $OUTPUT->heading(get_string('courseoutcomes', 'local_learningoutcomes'), 3);

// Alignment report link.
echo html_writer::div(
    html_writer::link($alignmenturl, get_string('alignmentreport', 'local_learningoutcomes')),
    'mb-3'
);

// Nudge: fewer outcomes than the minimum.
if (count($outcomes) < $mincount) {
    $nudgedata = (object) [
        'min' => $mincount,
        'url' => $editurl->out(false),
    ];
    echo $OUTPUT->notification(
        get_string('nudge:incomplete', 'local_learningoutcomes', $nudgedata),
        \core\output\notification::NOTIFY_WARNING,
        false
    );
}

if (empty($outcomes)) {
    echo $OUTPUT->notification(get_string('nooutcomes', 'local_learningoutcomes'), \core\output\notification::NOTIFY_INFO, false);
} else {
    $table = new html_table();
    $table->id = 'learning-outcomes-table';
    $table->attributes['class'] = 'generaltable fullwidth';
    $table->head = [
        get_string('outcomeshortname', 'local_learningoutcomes'),
        get_string('outcomefullname', 'local_learningoutcomes'),
        get_string('actions'),
    ];

    foreach ($outcomes as $outcome) {
        $editlink   = html_writer::link(
            new moodle_url('/local/learningoutcomes/edit.php', ['courseid' => $courseid, 'id' => $outcome->id]),
            get_string('edit')
        );
        $deletelink = html_writer::link(
            new moodle_url($manageurl, ['action' => 'delete', 'id' => $outcome->id]),
            get_string('delete'),
            ['class' => 'text-danger']
        );

        $table->data[] = [
            format_string($outcome->shortname),
            format_string($outcome->fullname),
            $editlink . ' | ' . $deletelink,
        ];
    }

    echo html_writer::table($table);
}

// Add outcome button.
$addbutton = new single_button(
    new moodle_url('/local/learningoutcomes/edit.php', ['courseid' => $courseid]),
    get_string('addoutcome', 'local_learningoutcomes'),
    'get',
    single_button::BUTTON_PRIMARY
);
echo $OUTPUT->render($addbutton);

echo $OUTPUT->footer();
