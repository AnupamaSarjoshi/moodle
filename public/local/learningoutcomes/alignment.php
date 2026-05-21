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
 * Learning outcomes alignment report page.
 *
 * Shows a two-way coverage view: outcomes with no supporting activities,
 * and activities with no outcome tag.  Accessible from the course Reports
 * menu and from manage.php.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_learningoutcomes\output\alignment_report;

$courseid = required_param('courseid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/learningoutcomes:viewreport', $context);

if (!get_config('local_learningoutcomes', 'enabled')) {
    throw new moodle_exception('error:nopermission', 'local_learningoutcomes');
}

$reporturl = new moodle_url('/local/learningoutcomes/alignment.php', ['courseid' => $courseid]);
$manageurl = new moodle_url('/local/learningoutcomes/manage.php', ['courseid' => $courseid]);

$PAGE->set_url($reporturl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('alignmentreport', 'local_learningoutcomes'));

echo $OUTPUT->header();

$PAGE->navbar->add(
    get_string('reports'),
    new moodle_url('/course/report.php', ['id' => $courseid])
);
$PAGE->navbar->add(get_string('alignmentreport', 'local_learningoutcomes'), $reporturl);

echo $OUTPUT->heading(get_string('alignmentreport', 'local_learningoutcomes'), 2);

echo html_writer::tag('p', get_string('alignmentreport_desc', 'local_learningoutcomes'));

$renderable = new alignment_report($courseid);
echo $OUTPUT->render_from_template(
    'local_learningoutcomes/alignment_report',
    $renderable->export_for_template($OUTPUT)
);

echo $OUTPUT->footer();
