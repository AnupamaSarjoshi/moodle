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
 * Plugin callbacks and library functions for local_learningoutcomes.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the course navigation to add a Learning Outcomes management link
 * for editing teachers.
 *
 * @param navigation_node $navigation The course navigation node.
 * @param stdClass $course The course record.
 * @param context_course $context The course context.
 */
function local_learningoutcomes_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
): void {
    if (!local_learningoutcomes_is_enabled_for_course($course->id)) {
        return;
    }

    if (has_capability('local/learningoutcomes:manage', $context)) {
        $url = new moodle_url('/local/learningoutcomes/manage.php', ['courseid' => $course->id]);
        $navigation->add(
            get_string('manageoutcomes', 'local_learningoutcomes'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'learningoutcomes',
            new pix_icon('i/outcomes', '')
        );
    }

    if (has_capability('local/learningoutcomes:viewreport', $context)) {
        $reporturl = new moodle_url('/local/learningoutcomes/alignment.php', ['courseid' => $course->id]);
        $navigation->add(
            get_string('alignmentreport', 'local_learningoutcomes'),
            $reporturl,
            navigation_node::TYPE_SETTING,
            null,
            'learningoutcomesreport',
            new pix_icon('i/report', '')
        );
    }
}

/**
 * Returns whether learning outcomes are enabled for the given course.
 *
 * The tri-state logic is:
 *   - If the site-level feature is off, always returns false.
 *   - If the course has an explicit setting (0 or 1), that takes precedence.
 *   - Otherwise the site default for new courses applies.
 *
 * @param int $courseid The course ID.
 * @return bool True if learning outcomes are enabled for this course.
 */
function local_learningoutcomes_is_enabled_for_course(int $courseid): bool {
    global $DB;

    // Check site-level master switch.
    if (!get_config('local_learningoutcomes', 'enabled')) {
        return false;
    }

    // Check course-level override.
    // get_field() returns false when no record exists, null when the field is NULL.
    // NULL means "inherit from site default" — must be distinguished from 0 (off).
    $setting = $DB->get_field('local_lo_course_settings', 'enabled', ['courseid' => $courseid]);
    if ($setting !== false && $setting !== null) {
        // Explicit course setting: 1 = on, 0 = off.
        return (bool) $setting;
    }

    // Fall back to the site default for new courses.
    return (bool) get_config('local_learningoutcomes', 'coursesdefault');
}

/**
 * Adds a "Course learning outcomes" section to the activity settings form.
 *
 * Shows all outcomes available in the course as a multi-select, pre-checked
 * with the outcomes currently tagged to this activity (if editing).
 *
 * @param moodleform $formwrapper The Moodle form wrapper.
 * @param MoodleQuickForm $mform The underlying form object.
 */
function local_learningoutcomes_coursemodule_standard_elements(
    moodleform $formwrapper,
    MoodleQuickForm $mform
): void {
    global $COURSE;

    $courseid = $COURSE->id ?? 0;
    if ($courseid < 2) {
        return;
    }

    if (!local_learningoutcomes_is_enabled_for_course($courseid)) {
        return;
    }

    $context = context_course::instance($courseid);
    if (!has_capability('local/learningoutcomes:manage', $context)) {
        return;
    }

    $outcomes = \local_learningoutcomes\manager::get_available_outcomes($courseid);
    if (empty($outcomes)) {
        return;
    }

    $mform->addElement('header', 'learningoutcomessection',
        get_string('courseoutcomes', 'local_learningoutcomes'));

    $options = [];
    foreach ($outcomes as $outcome) {
        $options[$outcome->id] = format_string($outcome->shortname . ' — ' . $outcome->fullname);
    }

    $select = $mform->addElement('select', 'learningoutcomes_ids',
        get_string('tagactivity', 'local_learningoutcomes'), $options);
    $select->setMultiple(true);
    $select->setSize(min(8, count($options)));
    $mform->addHelpButton('learningoutcomes_ids', 'tagactivity', 'local_learningoutcomes');

    // Pre-populate with outcomes already tagged to this cm when editing.
    $cm = $formwrapper->get_coursemodule();
    if ($cm) {
        $current = \local_learningoutcomes\manager::get_cm_outcome_ids($cm->id, $courseid);
        $mform->setDefault('learningoutcomes_ids', $current);
    }
}

/**
 * Syncs the local_lo_activity_outcome table after the activity settings form
 * is saved.
 *
 * Replaces all outcome tags for the saved cm with whatever the teacher
 * selected in the "Course learning outcomes" form section.
 *
 * @param stdClass $moduleinfo The module info object (populated from form data).
 * @param stdClass $course The course object.
 * @return stdClass The (unchanged) module info object.
 */
function local_learningoutcomes_coursemodule_edit_post_actions(stdClass $moduleinfo, stdClass $course): stdClass {
    // The field is absent when the section was not rendered (disabled plugin,
    // no outcomes defined, or insufficient capability).
    if (!isset($moduleinfo->learningoutcomes_ids)) {
        return $moduleinfo;
    }

    $courseid = (int) $course->id;
    $cmid     = (int) $moduleinfo->coursemodule;

    if (!local_learningoutcomes_is_enabled_for_course($courseid)) {
        return $moduleinfo;
    }

    $newids = array_map('intval', (array) $moduleinfo->learningoutcomes_ids);

    // Preserve the existing decorative flag; only the outcome selection changes here.
    $isdecorative = \local_learningoutcomes\manager::get_cm_decorative($cmid, $courseid);
    \local_learningoutcomes\manager::set_cm_outcomes($cmid, $courseid, $newids, $isdecorative);

    return $moduleinfo;
}
