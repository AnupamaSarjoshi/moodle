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
    $setting = $DB->get_field('local_lo_course_settings', 'enabled', ['courseid' => $courseid]);
    if ($setting !== false) {
        // Explicit course setting (0 or 1).
        return (bool) $setting;
    }

    // Fall back to the site default for new courses.
    return (bool) get_config('local_learningoutcomes', 'coursesdefault');
}

/**
 * Extends the course settings form to add a Learning Outcomes toggle.
 * Called via the standard Moodle course edit form hook.
 *
 * @param moodleform $formwrapper The Moodle form wrapper.
 * @param MoodleQuickForm $mform The underlying form object.
 */
function local_learningoutcomes_coursemodule_standard_elements(
    moodleform $formwrapper,
    MoodleQuickForm $mform
): void {
    // Not used here; activity-level tagging is handled separately.
}
