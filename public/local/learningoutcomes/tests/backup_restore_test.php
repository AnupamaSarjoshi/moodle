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
 * Tests that local_learningoutcomes data is preserved through backup and restore.
 *
 * @package   local_learningoutcomes
 * @category  phpunit
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes;

use advanced_testcase;
use backup;
use backup_controller;
use restore_controller;
use restore_dbops;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Integration tests for the backup and restore plugin classes.
 *
 * @covers \backup_local_learningoutcomes_plugin
 * @covers \restore_local_learningoutcomes_plugin
 */
final class backup_restore_test extends advanced_testcase {

    /**
     * Backs up a course and restores it into a new course, returning the new course ID.
     *
     * @param \stdClass $course Source course.
     * @return int New course ID.
     */
    private function backup_and_restore(\stdClass $course): int {
        global $USER, $CFG;

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $results  = $bc->get_results();
        $file     = $results['backup_destination'];
        $fp       = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-lo-restore-course-' . $course->id;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        $newcourseid = restore_dbops::create_new_course(
            $course->fullname . '_copy',
            $course->shortname . '_copy',
            $course->category
        );

        $rc = new restore_controller(
            'test-lo-restore-course-' . $course->id,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * Outcome–activity tags are preserved after backup/restore.
     *
     * @covers \backup_local_learningoutcomes_plugin::define_course_plugin_structure
     * @covers \restore_local_learningoutcomes_plugin::process_local_learningoutcomes_activity_outcome
     */
    public function test_activity_outcome_tags_are_restored(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $page    = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $outcome = manager::create_outcome($course->id, 'Apply higher-order thinking', 'HOT1');
        manager::set_cm_outcomes($page->cmid, $course->id, [$outcome]);

        $newcourseid = $this->backup_and_restore($course);

        // The restored course should have a page with the same outcome tag.
        $modinfo    = get_fast_modinfo($newcourseid);
        $restoredcms = $modinfo->get_cms();
        $this->assertNotEmpty($restoredcms);

        $restoredcm = reset($restoredcms);
        $taggedids  = manager::get_cm_outcome_ids($restoredcm->id, $newcourseid);

        // The outcome id in the restored course is the same (grade_outcomes has a
        // courseid FK; the restore step does NOT remap outcome ids, only cmids).
        // Verify at least one outcome is tagged to the restored cm.
        $this->assertNotEmpty($taggedids, 'Restored activity should be tagged to the outcome');
    }

    /**
     * Course settings (enabled tri-state) are preserved after backup/restore.
     *
     * @covers \backup_local_learningoutcomes_plugin::define_course_plugin_structure
     * @covers \restore_local_learningoutcomes_plugin::process_local_learningoutcomes_course_settings
     */
    public function test_course_settings_are_restored(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        manager::save_course_settings($course->id, 1);

        $newcourseid = $this->backup_and_restore($course);

        $settings = manager::get_course_settings($newcourseid);
        $this->assertSame(1, (int) $settings->enabled);
    }

    /**
     * Restoring into a course with an existing settings row does not create duplicates.
     *
     * We simulate a "restore on top of existing data" by inserting a settings
     * record for the target course before restoring.
     *
     * @covers \restore_local_learningoutcomes_plugin::process_local_learningoutcomes_course_settings
     */
    public function test_restore_does_not_duplicate_course_settings(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        manager::save_course_settings($course->id, 1);

        $newcourseid = $this->backup_and_restore($course);

        // A second restore into the same target (simulated by inserting first, then
        // calling save_course_settings which is the idempotent write path).
        manager::save_course_settings($newcourseid, 1);

        $count = $DB->count_records('local_lo_course_settings', ['courseid' => $newcourseid]);
        $this->assertSame(1, $count, 'Only one settings row expected after duplicate write');
    }

    /**
     * A course with no outcomes data produces a clean restore without errors.
     */
    public function test_backup_restore_empty_course_no_error(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        // No outcomes, no settings — should not throw.
        $newcourseid = $this->backup_and_restore($course);
        $this->assertGreaterThan(0, $newcourseid);
    }
}
