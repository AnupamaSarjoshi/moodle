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
 * Manager class for local_learningoutcomes.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes;

use moodle_exception;
use stdClass;

/**
 * Central service class for Learning Outcomes data operations.
 *
 * All DB interactions for this plugin should go through here, keeping
 * controllers and forms thin.
 */
class manager {

    /**
     * Returns all learning outcomes defined for the given course (course-scoped
     * outcomes only, i.e. grade_outcomes.courseid = $courseid).
     *
     * @param int $courseid The course ID.
     * @return stdClass[] Indexed by outcome ID.
     */
    public static function get_course_outcomes(int $courseid): array {
        global $DB;

        return $DB->get_records('grade_outcomes', ['courseid' => $courseid], 'shortname ASC');
    }

    /**
     * Returns all site-level (standard) learning outcomes.
     *
     * @return stdClass[] Indexed by outcome ID.
     */
    public static function get_site_outcomes(): array {
        global $DB;

        return $DB->get_records('grade_outcomes', ['courseid' => null], 'shortname ASC');
    }

    /**
     * Returns all outcomes available in a course: course-scoped first, then
     * site-level (standard) outcomes.
     *
     * @param int $courseid The course ID.
     * @return stdClass[] Indexed by outcome ID.
     */
    public static function get_available_outcomes(int $courseid): array {
        $course = static::get_course_outcomes($courseid);
        $site   = static::get_site_outcomes();

        // Course-scoped outcomes take precedence over site outcomes with the same key.
        return $course + $site;
    }

    /**
     * Returns the outcomes to display on the course "What you will learn" block.
     *
     * Includes:
     *   - All course-scoped outcomes defined for this course.
     *   - Site-wide (standard) outcomes that are tagged to at least one
     *     activity in this course.
     *
     * Course-scoped outcomes take precedence if an ID collision ever occurs.
     *
     * @param int $courseid The course ID.
     * @return stdClass[] Indexed by outcome ID, sorted by shortname within each group.
     */
    public static function get_outcomes_for_course_page(int $courseid): array {
        global $DB;

        // All course-scoped outcomes (always shown on the course page).
        $courseoutcomes = $DB->get_records('grade_outcomes', ['courseid' => $courseid], 'shortname ASC');

        // Find the distinct outcome IDs tagged to any activity in this course.
        $usedrows = $DB->get_records('local_lo_activity_outcome', ['courseid' => $courseid], '', 'outcomeid');
        if (empty($usedrows)) {
            return $courseoutcomes;
        }

        $usedids = array_keys($usedrows);

        // From the used IDs, keep only the site-wide ones (courseid IS NULL).
        [$insql, $inparams] = $DB->get_in_or_equal($usedids, SQL_PARAMS_NAMED);
        $siteoutcomes = $DB->get_records_select(
            'grade_outcomes',
            "courseid IS NULL AND id $insql",
            $inparams,
            'shortname ASC'
        );

        // Course-scoped outcomes take precedence; site outcomes appended after.
        return $courseoutcomes + $siteoutcomes;
    }

    /**
     * Creates a new course-scoped learning outcome.
     *
     * @param int $courseid The course ID.
     * @param string $fullname The full-text outcome statement.
     * @param string $shortname The short name / code.
     * @param string|null $description Optional extended description.
     * @param int $descriptionformat The format of $description (FORMAT_* constant).
     * @param int|null $scaleid Optional scale ID.
     * @return int The new outcome's ID.
     * @throws moodle_exception If shortname already exists in the course.
     */
    public static function create_outcome(
        int $courseid,
        string $fullname,
        string $shortname,
        ?string $description = null,
        int $descriptionformat = FORMAT_HTML,
        ?int $scaleid = null
    ): int {
        global $DB, $USER;

        if ($DB->record_exists('grade_outcomes', ['courseid' => $courseid, 'shortname' => $shortname])) {
            throw new moodle_exception('error:outcomeduplicate', 'local_learningoutcomes');
        }

        $now = time();
        $record = (object) [
            'courseid'          => $courseid,
            'fullname'          => $fullname,
            'shortname'         => $shortname,
            'description'       => $description ?? '',
            'descriptionformat' => $descriptionformat,
            'scaleid'           => $scaleid,
            'timecreated'       => $now,
            'timemodified'      => $now,
            'usermodified'      => $USER->id,
        ];

        return $DB->insert_record('grade_outcomes', $record);
    }

    /**
     * Updates an existing learning outcome.
     *
     * @param int $id The outcome ID.
     * @param string $fullname The full-text outcome statement.
     * @param string $shortname The short name / code.
     * @param string|null $description Optional extended description.
     * @param int $descriptionformat The format of $description (FORMAT_* constant).
     * @param int|null $scaleid Optional scale ID.
     * @throws moodle_exception If the outcome does not exist.
     */
    public static function update_outcome(
        int $id,
        string $fullname,
        string $shortname,
        ?string $description = null,
        int $descriptionformat = FORMAT_HTML,
        ?int $scaleid = null
    ): void {
        global $DB, $USER;

        if (!$DB->record_exists('grade_outcomes', ['id' => $id])) {
            throw new moodle_exception('error:invalidoutcome', 'local_learningoutcomes');
        }

        $record = (object) [
            'id'                => $id,
            'fullname'          => $fullname,
            'shortname'         => $shortname,
            'description'       => $description ?? '',
            'descriptionformat' => $descriptionformat,
            'scaleid'           => $scaleid,
            'timemodified'      => time(),
            'usermodified'      => $USER->id,
        ];

        $DB->update_record('grade_outcomes', $record);
    }

    /**
     * Deletes a learning outcome and all associated activity tags for the given
     * course.  Does not remove grade items that reference the outcome.
     *
     * @param int $outcomeid The outcome ID.
     * @param int $courseid The course ID (used to scope the activity tag deletion).
     */
    public static function delete_outcome(int $outcomeid, int $courseid): void {
        global $DB;

        $DB->delete_records('local_lo_activity_outcome', [
            'outcomeid' => $outcomeid,
            'courseid'  => $courseid,
        ]);

        $DB->delete_records('grade_outcomes', ['id' => $outcomeid, 'courseid' => $courseid]);
    }

    /**
     * Returns the IDs of outcomes currently tagged to a course module.
     *
     * @param int $cmid The course module ID.
     * @param int $courseid The course ID.
     * @return int[] Outcome IDs.
     */
    public static function get_cm_outcome_ids(int $cmid, int $courseid): array {
        global $DB;

        return array_keys(
            $DB->get_records('local_lo_activity_outcome', ['cmid' => $cmid, 'courseid' => $courseid], '', 'outcomeid')
        );
    }

    /**
     * Replaces the set of outcomes tagged to a course module.
     *
     * @param int $cmid The course module ID.
     * @param int $courseid The course ID.
     * @param int[] $outcomeids New set of outcome IDs (may be empty).
     * @param bool $isdecorative Whether this activity is marked as decorative.
     */
    public static function set_cm_outcomes(int $cmid, int $courseid, array $outcomeids, bool $isdecorative = false): void {
        global $DB, $USER;

        $DB->delete_records('local_lo_activity_outcome', ['cmid' => $cmid, 'courseid' => $courseid]);

        $now = time();
        foreach ($outcomeids as $outcomeid) {
            $DB->insert_record('local_lo_activity_outcome', (object) [
                'courseid'     => $courseid,
                'cmid'         => $cmid,
                'outcomeid'    => (int) $outcomeid,
                'timecreated'  => $now,
                'timemodified' => $now,
                'usermodified' => $USER->id,
            ]);
        }
    }

    /**
     * Returns all activity-outcome tag records for a course, keyed by cmid.
     *
     * @param int $courseid The course ID.
     * @return array<int, stdClass[]> Map of cmid => array of tag records.
     */
    public static function get_course_activity_tags(int $courseid): array {
        global $DB;

        $tags = $DB->get_records('local_lo_activity_outcome', ['courseid' => $courseid]);
        $bycm = [];
        foreach ($tags as $tag) {
            $bycm[(int) $tag->cmid][] = $tag;
        }

        return $bycm;
    }

    /**
     * Returns the coverage score for a course: the percentage of non-decorative
     * course modules that are tagged to at least one outcome.
     *
     * @param int $courseid The course ID.
     * @return array{tagged: int, untagged: int, decorative: int, total: int, score: float}
     */
    public static function get_alignment_score(int $courseid): array {
        global $DB;

        // All visible, non-label course modules (approximate; decorative detection
        // is done by checking whether the cm has no grade weight and no completion).
        $modinfo = get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();

        $taggedbycm = static::get_course_activity_tags($courseid);

        $tagged     = 0;
        $untagged   = 0;
        $decorative = 0;

        foreach ($cms as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            if (static::is_decorative($cm)) {
                $decorative++;
                continue;
            }

            if (!empty($taggedbycm[$cm->id])) {
                $tagged++;
            } else {
                $untagged++;
            }
        }

        $total = $tagged + $untagged;
        $score = $total > 0 ? round(($tagged / $total) * 100, 1) : 0.0;

        return [
            'tagged'     => $tagged,
            'untagged'   => $untagged,
            'decorative' => $decorative,
            'total'      => $total,
            'score'      => $score,
        ];
    }

    /**
     * Heuristically determines whether a course module is decorative (i.e. not
     * expected to contribute to a learning outcome).
     *
     * The heuristic checks:
     *   1. The activity type is 'label' (always decorative).
     *   2. The activity has no grade weight AND no completion condition.
     *
     * This is intentionally conservative; a false negative (marking a decorative
     * activity as non-decorative) is less harmful than a false positive.
     *
     * @param \cm_info $cm The course module info object.
     * @return bool True if the activity is decorative.
     */
    public static function is_decorative(\cm_info $cm): bool {
        if ($cm->modname === 'label') {
            return true;
        }

        // No completion condition and no grade weight.
        $nocompletion = ($cm->completion == COMPLETION_DISABLED || $cm->completion == COMPLETION_TRACKING_NONE);
        $nograde = (empty($cm->grademax) && empty($cm->gradepass));

        return $nocompletion && $nograde;
    }

    /**
     * Returns or creates the course settings record.
     *
     * @param int $courseid The course ID.
     * @return stdClass The settings record.
     */
    public static function get_course_settings(int $courseid): stdClass {
        global $DB;

        $record = $DB->get_record('local_lo_course_settings', ['courseid' => $courseid]);
        if (!$record) {
            $record = (object) [
                'courseid'     => $courseid,
                'enabled'      => null,
                'timecreated'  => time(),
                'timemodified' => time(),
                'usermodified' => 0,
            ];
        }

        return $record;
    }

    /**
     * Saves (insert or update) the course-level enabled setting.
     *
     * @param int $courseid The course ID.
     * @param int|null $enabled NULL = inherit, 1 = on, 0 = off.
     */
    public static function save_course_settings(int $courseid, ?int $enabled): void {
        global $DB, $USER;

        $existing = $DB->get_record('local_lo_course_settings', ['courseid' => $courseid]);
        $now = time();

        if ($existing) {
            $existing->enabled      = $enabled;
            $existing->timemodified = $now;
            $existing->usermodified = $USER->id;
            $DB->update_record('local_lo_course_settings', $existing);
        } else {
            $DB->insert_record('local_lo_course_settings', (object) [
                'courseid'     => $courseid,
                'enabled'      => $enabled,
                'timecreated'  => $now,
                'timemodified' => $now,
                'usermodified' => $USER->id,
            ]);
        }
    }
}
