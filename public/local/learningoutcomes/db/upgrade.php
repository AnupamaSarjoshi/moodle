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
 * Upgrade script for local_learningoutcomes.
 *
 * Contains an upgrade-time data audit that checks whether the site has
 * "meaningful" prior use of the Moodle outcomes feature before enabling
 * the plugin for any course.
 *
 * Conservative bias (PRD requirement): the failure mode we guard against is
 * silently turning OFF the feature for a site that was actively using it.
 * Therefore we only auto-enable for courses that clearly show evidence of
 * prior use; everything else defaults to off.
 *
 * Evidence criteria (ANY of the following counts as meaningful use):
 *  1. The course had `enableoutcomes = 1` in its course settings AND it has
 *     at least one grade_outcomes record scoped to that course.
 *  2. There is at least one grade_outcomes_courses record linking an outcome
 *     to the course AND the outcome also appears in grade_items for the same
 *     course (i.e. it was actually used in the gradebook).
 *
 * Courses not meeting either criterion default to enabled = 0 in
 * local_lo_course_settings (feature off for that course).
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrades local_learningoutcomes to the target version.
 *
 * @param int $oldversion Version being upgraded from.
 * @return bool
 */
function xmldb_local_learningoutcomes_upgrade(int $oldversion): bool {
    global $DB;

    // -------------------------------------------------------------------------
    // 2026052101 – Upgrade-time data audit.
    //
    // On first install this runs after the tables are created (old version is 0
    // or any pre-audit version).  Safe to run multiple times: we only INSERT
    // rows that don't already exist.
    // -------------------------------------------------------------------------
    if ($oldversion < 2026052101) {

        // The site master switch defaults to off.  If the site admin had the
        // core "enable outcomes" site-wide config on, treat that as a signal
        // that outcomes were in use and set the plugin master switch on.
        $siteenabled = (bool) get_config('moodlecourse', 'enableoutcomes');
        if ($siteenabled) {
            set_config('enabled', 1, 'local_learningoutcomes');
        }

        // Audit every course that has outcomes enabled at the course level.
        // We look at the course.enableoutcomes field (1 = on).
        $courses = $DB->get_records_select(
            'course',
            'enableoutcomes = 1 AND id != :siteid',
            ['siteid' => SITEID],
            '',
            'id'
        );

        if (!empty($courses)) {
            // Fetch course ids that actually have grade_outcomes scoped to them.
            // grade_outcomes rows with courseid IS NOT NULL are course-scoped.
            // grade_outcomes_courses links outcomes to courses for the gradebook.
            //
            // Criterion 1: course has enableoutcomes=1 AND has course-scoped outcomes.
            [$inparams, $inargs] = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED, 'cid');
            $courseswithoutcomes = $DB->get_fieldset_select(
                'grade_outcomes',
                'courseid',
                "courseid $inparams AND courseid IS NOT NULL",
                $inargs
            );
            $courseswithoutcomes = array_flip(array_unique($courseswithoutcomes));

            // Criterion 2: has outcome linked via grade_outcomes_courses that
            // also appears in grade_items with outcomeid set for the same course.
            $sql = "SELECT DISTINCT gi.courseid
                      FROM {grade_items} gi
                      JOIN {grade_outcomes_courses} goc ON goc.outcomeid = gi.outcomeid
                                                       AND goc.courseid = gi.courseid
                     WHERE gi.courseid $inparams
                       AND gi.outcomeid IS NOT NULL";
            $courseswithgradebook = $DB->get_fieldset_sql($sql, $inargs);
            $courseswithgradebook = array_flip(array_unique($courseswithgradebook));

            $now    = time();
            $userid = get_admin()->id;

            foreach ($courses as $courseid => $unused) {
                $hasuse = isset($courseswithoutcomes[$courseid])
                       || isset($courseswithgradebook[$courseid]);

                // Skip if a settings record already exists (idempotent).
                if ($DB->record_exists('local_lo_course_settings', ['courseid' => $courseid])) {
                    continue;
                }

                $record = new stdClass();
                $record->courseid     = $courseid;
                $record->enabled      = $hasuse ? 1 : 0;
                $record->timecreated  = $now;
                $record->timemodified = $now;
                $record->usermodified = $userid;
                $DB->insert_record('local_lo_course_settings', $record);
            }
        }

        upgrade_plugin_savepoint(true, 2026052101, 'local', 'learningoutcomes');
    }

    // -------------------------------------------------------------------------
    // 2026052102 – Add local_lo_cm_settings table to store explicit per-cm
    //              decorative override set by teachers via the tagging widget.
    // -------------------------------------------------------------------------
    if ($oldversion < 2026052102) {
        $dbman = $DB->get_manager();

        $table = new xmldb_table('local_lo_cm_settings');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('decorative', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('fk_cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $table->add_key('fk_usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('uq_courseid_cmid', XMLDB_KEY_UNIQUE, ['courseid', 'cmid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026052102, 'local', 'learningoutcomes');
    }

    // -------------------------------------------------------------------------
    // 2026052103 – Remove malformed grade_items created by an earlier version
    //              of sync_outcome_grade_items.
    //
    // The earlier code called grade_update() which silently ignores the
    // 'outcomeid' key (not in its $allowed list).  This produced grade_items
    // with itemnumber in [1, 999] and outcomeid = NULL for activities that have
    // rows in local_lo_activity_outcome.  Those items triggered the exception
    // "Unknown itemnumber mapping for N in mod_X" whenever the activity
    // settings page was loaded (get_moduleinfo_data iterates all grade_items
    // and calls component_gradeitems::get_field_name_for_itemnumber for every
    // itemnumber that has no outcomeid).
    //
    // Safe to delete: activity modules that legitimately carry multiple grade
    // items use itemnumber = 0 (main) plus values >= 1000 (standard Moodle
    // convention for outcomes).  Items in [1, 999] with no outcomeid that
    // belong to an activity instance managed by this plugin are definitively
    // artefacts of the bug.
    // -------------------------------------------------------------------------
    if ($oldversion < 2026052103) {

        // Find activity instances (itemtype='mod') that have at least one row
        // in local_lo_activity_outcome — these were managed by this plugin.
        $managed = $DB->get_records_sql(
            "SELECT DISTINCT gi.itemmodule, gi.iteminstance, gi.courseid
               FROM {grade_items} gi
               JOIN {course_modules} cm ON cm.instance = gi.iteminstance
                                       AND cm.course   = gi.courseid
               JOIN {local_lo_activity_outcome} lo ON lo.cmid = cm.id
                                                   AND lo.courseid = gi.courseid
              WHERE gi.itemtype   = 'mod'
                AND gi.itemnumber BETWEEN 1 AND 999
                AND gi.outcomeid  IS NULL"
        );

        foreach ($managed as $row) {
            $DB->delete_records_select(
                'grade_items',
                "itemtype = 'mod'
                 AND itemmodule   = :module
                 AND iteminstance = :instance
                 AND courseid     = :courseid
                 AND itemnumber BETWEEN 1 AND 999
                 AND outcomeid IS NULL",
                [
                    'module'   => $row->itemmodule,
                    'instance' => (int) $row->iteminstance,
                    'courseid' => (int) $row->courseid,
                ]
            );
        }

        upgrade_plugin_savepoint(true, 2026052103, 'local', 'learningoutcomes');
    }

    return true;
}
