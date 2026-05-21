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
 * Hook listener for local_learningoutcomes.
 *
 * Injects the student-facing learning outcomes surfaces on:
 *   - the course main page (prominent outcomes list)
 *   - activity entry pages (per-activity outcomes list)
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes;

use core\hook\output\before_footer_html_generation;
use local_learningoutcomes\output\activity_outcomes;
use local_learningoutcomes\output\course_outcomes;

/**
 * Listens to Moodle output hooks and injects learning outcomes HTML.
 */
class hook_listener {

    /**
     * Fires just before the page footer is generated.
     *
     * Used to inject the learning outcomes panels into course and activity
     * pages by appending HTML that positions itself via a data-target anchor.
     *
     * For the course main page  : renders the course_outcomes template and
     *   injects it via a small inline script that moves the node into the
     *   #course-learning-outcomes-target anchor rendered by course formats.
     *
     * For activity pages        : renders the activity_outcomes template and
     *   appends it to the #activity-learning-outcomes-target div.
     *
     * Both targets are rendered by the hook below only when the feature is
     * enabled; this approach keeps the injection non-destructive.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT, $COURSE, $USER;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        $pagetype = $PAGE->pagetype;

        // --- Course main page ------------------------------------------------
        if ($pagetype === 'course-view-topics'
            || $pagetype === 'course-view-weeks'
            || strpos($pagetype, 'course-view') === 0
        ) {
            $courseid = $COURSE->id ?? 0;
            if ($courseid < 2) {
                // Site front page — skip.
                return;
            }

            if (!local_learningoutcomes_is_enabled_for_course($courseid)) {
                return;
            }

            $context = \context_course::instance($courseid);
            if (!has_capability('local/learningoutcomes:view', $context)) {
                return;
            }

            $outcomes  = manager::get_outcomes_for_course_page($courseid);
            $canmanage = has_capability('local/learningoutcomes:manage', $context);

            $renderable = new course_outcomes($courseid, $outcomes, $canmanage);
            $html = $OUTPUT->render_from_template(
                'local_learningoutcomes/course_outcomes',
                $renderable->export_for_template($OUTPUT)
            );

            // Inject via a small inline script that moves the node into the
            // target anchor once the DOM is ready.
            $escapedhtml = addslashes($html);
            $hook->add_html(
                "<script>
                (function() {
                    var target = document.getElementById('course-learning-outcomes');
                    if (!target) {
                        target = document.createElement('div');
                        target.id = 'course-learning-outcomes';
                        var main = document.querySelector('.course-content') || document.querySelector('#region-main');
                        if (main) { main.insertAdjacentElement('beforebegin', target); }
                    }
                    if (target) { target.innerHTML = '" . $escapedhtml . "'; }
                })();
                </script>"
            );
            return;
        }

        // --- Activity entry pages -------------------------------------------
        if (strpos($pagetype, 'mod-') === 0 && strpos($pagetype, '-view') !== false) {
            $courseid = $COURSE->id ?? 0;
            if ($courseid < 2 || !local_learningoutcomes_is_enabled_for_course($courseid)) {
                return;
            }

            $context = \context_course::instance($courseid);
            if (!has_capability('local/learningoutcomes:view', $context)) {
                return;
            }

            $cmid = $PAGE->cm->id ?? 0;
            if ($cmid < 1) {
                return;
            }

            $outcomeids = manager::get_cm_outcome_ids($cmid, $courseid);
            if (empty($outcomeids)) {
                return;
            }

            // Load full outcome records.
            $outcomerecords = [];
            foreach ($outcomeids as $oid) {
                $all = manager::get_available_outcomes($courseid);
                if (isset($all[$oid])) {
                    $outcomerecords[$oid] = $all[$oid];
                }
            }

            $renderable = new activity_outcomes($courseid, $cmid, $outcomerecords);
            $html = $OUTPUT->render_from_template(
                'local_learningoutcomes/activity_outcomes',
                $renderable->export_for_template($OUTPUT)
            );

            $escapedhtml = addslashes($html);
            $hook->add_html(
                "<script>
                (function() {
                    var target = document.getElementById('activity-learning-outcomes');
                    if (!target) {
                        target = document.createElement('div');
                        target.id = 'activity-learning-outcomes';
                        var main = document.querySelector('#region-main .activity-header') ||
                                   document.querySelector('#region-main .activityiconcontainer') ||
                                   document.querySelector('#region-main');
                        if (main) { main.insertAdjacentElement('afterend', target); }
                    }
                    if (target) { target.innerHTML = '" . $escapedhtml . "'; }
                })();
                </script>"
            );
        }
    }
}
