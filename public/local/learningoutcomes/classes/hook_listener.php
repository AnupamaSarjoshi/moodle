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
 * Two hooks are used:
 *
 *   before_http_headers  — fires at the start of $OUTPUT->header(), before the
 *     HTML <head> is written. This is the right time to call js_call_amd() for
 *     the teacher-facing activity-tagging widget (tag_activity.js).
 *
 *   before_footer_html_generation — fires just before the page footer. Used to
 *     append the student-facing outcomes HTML to the page body.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_http_headers;
use local_learningoutcomes\output\activity_outcomes;
use local_learningoutcomes\output\course_outcomes;

/**
 * Listens to Moodle output hooks and injects learning outcomes surfaces.
 */
class hook_listener {

    // -------------------------------------------------------------------------
    // before_http_headers — AMD loading (teacher nudge / tagging widget)
    // -------------------------------------------------------------------------

    /**
     * Fires at the very start of $OUTPUT->header().
     *
     * On course pages where the current user has the manage capability,
     * loads the AMD tag_activity module so that a tagging panel appears
     * next to each activity card, nudging teachers to tag activities to
     * learning outcomes without leaving the course view.
     *
     * @param before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE, $COURSE;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        $pagetype = $PAGE->pagetype;
        if (strpos($pagetype, 'course-view') !== 0) {
            return;
        }

        $courseid = $COURSE->id ?? 0;
        if ($courseid < 2) {
            return;
        }

        if (!\local_learningoutcomes_is_enabled_for_course($courseid)) {
            return;
        }

        $context = \context_course::instance($courseid);
        if (!has_capability('local/learningoutcomes:manage', $context)) {
            return;
        }

        // Load the tagging widget AMD module.  It will scan the page for
        // [data-for="cmitem"] elements and attach colour-coded nudge panels to each.
        // The second argument is the URL of the alignment report, used in the
        // course-level nudge banner so teachers can jump directly to the gap report.
        $reporturl = new \moodle_url('/local/learningoutcomes/alignment.php', ['courseid' => $courseid]);
        $PAGE->requires->js_call_amd(
            'local_learningoutcomes/tag_activity',
            'init',
            [$courseid, $reporturl->out(false)]
        );
    }

    // -------------------------------------------------------------------------
    // before_footer_html_generation — student-facing surfaces
    // -------------------------------------------------------------------------

    /**
     * Fires just before the page footer is generated.
     *
     * Injects:
     *   - On course main pages: a learning outcomes card showing the course
     *     outcomes, inserted before the main course content.
     *   - On activity view pages: a card showing which outcomes the activity
     *     contributes to, inserted after the activity header.
     *
     * HTML is injected via an inline script that uses json_encode() for safe
     * HTML embedding and reads the page CSP nonce when available.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT, $COURSE;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        $pagetype = $PAGE->pagetype;

        // --- Course main page ------------------------------------------------
        // Exclude sub-pages that reuse the 'course-view-*' pagetype prefix but
        // are not the actual course content view (e.g. course-view-participants
        // is set by user/index.php).
        if (strpos($pagetype, 'course-view') === 0 && $pagetype !== 'course-view-participants') {
            $courseid = $COURSE->id ?? 0;
            if ($courseid < 2) {
                return;
            }

            if (!\local_learningoutcomes_is_enabled_for_course($courseid)) {
                return;
            }

            $context = \context_course::instance($courseid);
            if (!has_capability('local/learningoutcomes:view', $context)) {
                return;
            }

            $outcomes  = manager::get_outcomes_for_course_page($courseid);
            $canmanage = has_capability('local/learningoutcomes:manage', $context);

            $renderable = new course_outcomes($courseid, $outcomes, $canmanage, $PAGE->url);
            $html = $OUTPUT->render_from_template(
                'local_learningoutcomes/course_outcomes',
                $renderable->export_for_template($OUTPUT)
            );

            $hook->add_html(self::build_injection_script(
                'course-learning-outcomes',
                '.course-content, #region-main',
                'beforebegin',
                $html
            ));
            return;
        }

        // --- Activity entry pages -------------------------------------------
        if (strpos($pagetype, 'mod-') === 0 && strpos($pagetype, '-view') !== false) {
            $courseid = $COURSE->id ?? 0;
            if ($courseid < 2 || !\local_learningoutcomes_is_enabled_for_course($courseid)) {
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

            // Load full outcome records for the tagged ids.
            $all            = manager::get_available_outcomes($courseid);
            $outcomerecords = array_intersect_key($all, array_flip($outcomeids));

            $canmanage  = has_capability('local/learningoutcomes:manage', $context);
            $renderable = new activity_outcomes($courseid, $cmid, $outcomerecords, $canmanage, $PAGE->url);
            $html = $OUTPUT->render_from_template(
                'local_learningoutcomes/activity_outcomes',
                $renderable->export_for_template($OUTPUT)
            );

            $hook->add_html(self::build_injection_script(
                'activity-learning-outcomes',
                '.activity-header, #region-main-box, #region-main',
                'afterend',
                $html
            ));
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Builds an inline script that inserts the given HTML into the page DOM.
     *
     * Uses json_encode() for safe HTML embedding in JS (not addslashes()).
     * Reads the page CSP nonce when available so the script passes CSP checks.
     *
     * @param string $targetId   The id to give the wrapper element.
     * @param string $anchors    Comma-separated CSS selectors to try as insertion points.
     * @param string $position   insertAdjacentElement position string.
     * @param string $html       The HTML to inject.
     * @return string            The full <script> tag.
     */
    private static function build_injection_script(
        string $targetId,
        string $anchors,
        string $position,
        string $html
    ): string {
        global $PAGE;

        $nonce = method_exists($PAGE, 'get_csp_nonce') ? $PAGE->get_csp_nonce() : '';
        $nonceattr = $nonce ? ' nonce="' . s($nonce) . '"' : '';

        $jsonhtml     = json_encode($html);
        $jsontargetid = json_encode($targetId);
        $jsonanchors  = json_encode(array_map('trim', explode(',', $anchors)));
        $jsonposition = json_encode($position);

        return <<<HTML
<script{$nonceattr}>
(function() {
    var id = {$jsontargetid};
    var t = document.getElementById(id);
    if (!t) {
        t = document.createElement('div');
        t.id = id;
        var selectors = {$jsonanchors};
        var anchor = null;
        for (var i = 0; i < selectors.length; i++) {
            anchor = document.querySelector(selectors[i]);
            if (anchor) { break; }
        }
        if (anchor) {
            anchor.insertAdjacentElement({$jsonposition}, t);
        } else {
            document.body.appendChild(t);
        }
    }
    t.innerHTML = {$jsonhtml};
})();
</script>
HTML;
    }
}
