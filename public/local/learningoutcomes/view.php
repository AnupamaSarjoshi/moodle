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
 * Read-only course learning outcomes page.
 *
 * Accessible to all enrolled users with the view capability (teachers and
 * students).  Shows the full list of course learning outcomes.
 * Teachers with the manage capability see a link to the management page.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_learningoutcomes\manager;
use local_learningoutcomes\output\course_outcomes;

$courseid = required_param('courseid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/learningoutcomes:view', $context);

if (!\local_learningoutcomes_is_enabled_for_course($courseid)) {
    throw new moodle_exception('error:nopermission', 'local_learningoutcomes',
        new moodle_url('/course/view.php', ['id' => $courseid]));
}

$PAGE->set_url('/local/learningoutcomes/view.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('learningoutcomes', 'local_learningoutcomes'));
$PAGE->set_heading($course->fullname);

// Breadcrumb: Course > Learning outcomes.
$PAGE->navbar->add(
    get_string('learningoutcomes', 'local_learningoutcomes'),
    new moodle_url('/local/learningoutcomes/view.php', ['courseid' => $courseid])
);

$canmanage = has_capability('local/learningoutcomes:manage', $context);
$outcomes  = manager::get_available_outcomes($courseid);

$renderable = new course_outcomes($courseid, $outcomes, $canmanage, $PAGE->url, true);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('learningoutcomes', 'local_learningoutcomes'));

$backurl = get_local_referer(false) ?: new moodle_url('/course/view.php', ['id' => $courseid]);
echo html_writer::div(
    html_writer::link($backurl, get_string('back'), ['class' => 'btn btn-secondary mb-3']),
    'mb-3'
);

echo $OUTPUT->render_from_template(
    'local_learningoutcomes/course_outcomes',
    $renderable->export_for_template($OUTPUT)
);
echo $OUTPUT->footer();
