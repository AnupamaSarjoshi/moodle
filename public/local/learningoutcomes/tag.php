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
 * Tag an activity with learning outcomes.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_learningoutcomes\manager;
use local_learningoutcomes\form\tag_activity_form;

$courseid = required_param('courseid', PARAM_INT);
$cmid     = required_param('cmid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm      = get_coursemodule_from_id(null, $cmid, $courseid, false, MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/learningoutcomes:manage', $context);

if (!get_config('local_learningoutcomes', 'enabled')) {
    throw new moodle_exception('error:nopermission', 'local_learningoutcomes');
}

$manageurl = new moodle_url('/local/learningoutcomes/manage.php', ['courseid' => $courseid]);
$tagurl    = new moodle_url('/local/learningoutcomes/tag.php', ['courseid' => $courseid, 'cmid' => $cmid]);
$returnurl = new moodle_url('/course/view.php', ['id' => $courseid]);

$PAGE->set_url($tagurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('tagactivity', 'local_learningoutcomes'));

$outcomes = manager::get_available_outcomes($courseid);
$tagged   = manager::get_cm_outcome_ids($cmid, $courseid);

$customdata = [
    'courseid' => $courseid,
    'cmid'     => $cmid,
    'outcomes' => $outcomes,
];
$form = new tag_activity_form($tagurl, $customdata);

// Pre-populate form with currently tagged outcomes.
$formdefaults = ['courseid' => $courseid, 'cmid' => $cmid];
foreach ($outcomes as $outcome) {
    $formdefaults['outcomes[' . $outcome->id . ']'] = in_array((int) $outcome->id, $tagged) ? 1 : 0;
}
$form->set_data($formdefaults);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $selectedids = [];
    if (!empty($data->outcomes) && is_array($data->outcomes)) {
        foreach ($data->outcomes as $outcomeid => $selected) {
            if ($selected) {
                $selectedids[] = (int) $outcomeid;
            }
        }
    }
    $isdecorative = !empty($data->isdecorative);

    manager::set_cm_outcomes($cmid, $courseid, $selectedids, $isdecorative);

    redirect(
        $returnurl,
        get_string('activitytagsaved', 'local_learningoutcomes'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

$PAGE->navbar->add(
    get_string('manageoutcomes', 'local_learningoutcomes'),
    $manageurl
);
$PAGE->navbar->add(
    get_string('tagactivity', 'local_learningoutcomes'),
    $tagurl
);

echo $OUTPUT->heading(get_string('tagactivity', 'local_learningoutcomes'), 2);

// Show the activity name for context.
$modinfo = get_fast_modinfo($course);
$cminfo  = $modinfo->get_cm($cmid);
echo html_writer::tag(
    'p',
    get_string('activity') . ': ' . html_writer::tag('strong', format_string($cminfo->name))
);

echo $OUTPUT->notification(
    get_string('tagactivity_help', 'local_learningoutcomes'),
    \core\output\notification::NOTIFY_INFO,
    false
);

$form->display();
echo $OUTPUT->footer();
