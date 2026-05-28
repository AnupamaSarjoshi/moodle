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
 * Plugin settings and gateway page for course-level learning outcomes.
 *
 * Manages the per-course enabled toggle and nudges towards the alignment
 * report.  Outcome CRUD (add / edit / delete) is delegated to the standard
 * grade outcomes page at grade/edit/outcome/index.php.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_learningoutcomes\manager;
use local_learningoutcomes\form\course_settings_form;

$courseid  = required_param('courseid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/learningoutcomes:manage', $context);

// Check site feature switch.
if (!get_config('local_learningoutcomes', 'enabled')) {
    throw new moodle_exception('error:nopermission', 'local_learningoutcomes',
        new moodle_url('/course/view.php', ['id' => $courseid]));
}

$manageurlparams = ['courseid' => $courseid];
if ($returnurl !== '') {
    $manageurlparams['returnurl'] = $returnurl;
}
$manageurl    = new moodle_url('/local/learningoutcomes/manage.php', $manageurlparams);
$alignmenturl = new moodle_url('/local/learningoutcomes/alignment.php', ['courseid' => $courseid]);

$PAGE->set_url($manageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('manageoutcomes', 'local_learningoutcomes'));
$PAGE->set_heading($course->fullname);

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

// --- Outcomes --------------------------------------------------------

$outcomecount  = count(manager::get_course_outcomes($courseid));
$mincount      = (int) get_config('local_learningoutcomes', 'mincount') ?: 3;
$gradeindexurl = new moodle_url('/grade/edit/outcome/index.php', ['id' => $courseid, 'returnurl' => $manageurl->out_as_local_url(false)]);
$gradeaddurl   = new moodle_url('/grade/edit/outcome/edit.php', ['courseid' => $courseid]);

echo $OUTPUT->heading(get_string('courseoutcomes', 'local_learningoutcomes'), 3);

// Alignment report link.
echo html_writer::div(
    html_writer::link($alignmenturl, get_string('alignmentreport', 'local_learningoutcomes')),
    'mb-3'
);

// Nudge: fewer course-scoped outcomes than the recommended minimum.
if ($outcomecount < $mincount) {
    $nudgedata = (object) [
        'min' => $mincount,
        'url' => $gradeaddurl->out(false),
    ];
    echo $OUTPUT->notification(
        get_string('nudge:incomplete', 'local_learningoutcomes', $nudgedata),
        \core\output\notification::NOTIFY_WARNING,
        false
    );
}

// Button linking to the core grade outcomes management page.
echo html_writer::div(
    html_writer::link($gradeindexurl, get_string('manageoutcomes', 'grades'), ['class' => 'btn btn-primary']),
    'mt-2'
);

echo $OUTPUT->footer();
