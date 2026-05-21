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
 * Unit tests for \local_learningoutcomes\manager.
 *
 * @package   local_learningoutcomes
 * @category  phpunit
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes;

use advanced_testcase;
use moodle_exception;

/**
 * Tests for the manager service class.
 *
 * @covers \local_learningoutcomes\manager
 */
final class manager_test extends advanced_testcase {

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Creates a course-scoped outcome directly via the manager and returns its ID.
     */
    private function create_outcome(int $courseid, string $shortname = 'LO1', string $fullname = 'Learning Outcome 1'): int {
        return manager::create_outcome($courseid, $fullname, $shortname);
    }

    // -------------------------------------------------------------------------
    // get_course_outcomes / get_available_outcomes
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\manager::get_course_outcomes
     */
    public function test_get_course_outcomes_empty(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $outcomes = manager::get_course_outcomes($course->id);

        $this->assertSame([], $outcomes);
    }

    /**
     * @covers \local_learningoutcomes\manager::get_course_outcomes
     */
    public function test_get_course_outcomes_returns_only_course_scoped(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $id1 = $this->create_outcome($course->id, 'LO1');
        $id2 = $this->create_outcome($course->id, 'LO2');
        $this->create_outcome($course2->id, 'LO3'); // different course — must NOT appear

        $outcomes = manager::get_course_outcomes($course->id);

        $this->assertCount(2, $outcomes);
        $this->assertArrayHasKey($id1, $outcomes);
        $this->assertArrayHasKey($id2, $outcomes);
    }

    /**
     * @covers \local_learningoutcomes\manager::get_available_outcomes
     */
    public function test_get_available_outcomes_merges_site_and_course(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        // Site-level outcome (courseid = null).
        $siteid = $DB->insert_record('grade_outcomes', (object) [
            'courseid'          => null,
            'fullname'          => 'Site Outcome',
            'shortname'         => 'SO1',
            'description'       => '',
            'descriptionformat' => FORMAT_HTML,
            'scaleid'           => null,
            'timecreated'       => time(),
            'timemodified'      => time(),
            'usermodified'      => 2,
        ]);

        $courseid_outcome = $this->create_outcome($course->id, 'CO1');

        $available = manager::get_available_outcomes($course->id);

        $this->assertArrayHasKey($siteid, $available);
        $this->assertArrayHasKey($courseid_outcome, $available);
    }

    // -------------------------------------------------------------------------
    // create_outcome
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\manager::create_outcome
     */
    public function test_create_outcome_inserts_record(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $id = manager::create_outcome($course->id, 'Apply Bloom taxonomy', 'BLOOM1');

        $record = $DB->get_record('grade_outcomes', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('BLOOM1', $record->shortname);
        $this->assertSame((int) $course->id, (int) $record->courseid);
    }

    /**
     * @covers \local_learningoutcomes\manager::create_outcome
     */
    public function test_create_outcome_throws_on_duplicate_shortname(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        manager::create_outcome($course->id, 'First outcome', 'DUP');

        $this->expectException(moodle_exception::class);
        manager::create_outcome($course->id, 'Second outcome same shortname', 'DUP');
    }

    /**
     * @covers \local_learningoutcomes\manager::create_outcome
     */
    public function test_create_outcome_allows_same_shortname_in_different_course(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $id1 = manager::create_outcome($course1->id, 'Outcome A', 'SHARED');
        $id2 = manager::create_outcome($course2->id, 'Outcome A', 'SHARED');

        $this->assertNotSame($id1, $id2);
    }

    // -------------------------------------------------------------------------
    // update_outcome
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\manager::update_outcome
     */
    public function test_update_outcome_changes_fullname(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $id = $this->create_outcome($course->id);

        manager::update_outcome($id, 'Updated outcome statement', 'LO1');

        $record = $DB->get_record('grade_outcomes', ['id' => $id]);
        $this->assertSame('Updated outcome statement', $record->fullname);
    }

    /**
     * @covers \local_learningoutcomes\manager::update_outcome
     */
    public function test_update_outcome_throws_for_nonexistent_id(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(moodle_exception::class);
        manager::update_outcome(99999, 'Whatever', 'XX');
    }

    // -------------------------------------------------------------------------
    // delete_outcome
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\manager::delete_outcome
     */
    public function test_delete_outcome_removes_record_and_tags(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $page    = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $id      = $this->create_outcome($course->id);

        // Tag the activity.
        manager::set_cm_outcomes($page->cmid, $course->id, [$id]);
        $this->assertNotEmpty(manager::get_cm_outcome_ids($page->cmid, $course->id));

        manager::delete_outcome($id, $course->id);

        $this->assertFalse($DB->record_exists('grade_outcomes', ['id' => $id]));
        $this->assertEmpty(manager::get_cm_outcome_ids($page->cmid, $course->id));
    }

    // -------------------------------------------------------------------------
    // set_cm_outcomes / get_cm_outcome_ids
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\manager::set_cm_outcomes
     * @covers \local_learningoutcomes\manager::get_cm_outcome_ids
     */
    public function test_set_and_get_cm_outcomes_roundtrip(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $page   = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $id1    = $this->create_outcome($course->id, 'LO1');
        $id2    = $this->create_outcome($course->id, 'LO2');

        manager::set_cm_outcomes($page->cmid, $course->id, [$id1, $id2]);

        $tagged = manager::get_cm_outcome_ids($page->cmid, $course->id);
        $this->assertCount(2, $tagged);
        $this->assertContains($id1, $tagged);
        $this->assertContains($id2, $tagged);
    }

    /**
     * @covers \local_learningoutcomes\manager::set_cm_outcomes
     */
    public function test_set_cm_outcomes_replaces_previous_tags(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $page   = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $id1    = $this->create_outcome($course->id, 'LO1');
        $id2    = $this->create_outcome($course->id, 'LO2');

        manager::set_cm_outcomes($page->cmid, $course->id, [$id1, $id2]);
        // Replace with only LO2.
        manager::set_cm_outcomes($page->cmid, $course->id, [$id2]);

        $tagged = manager::get_cm_outcome_ids($page->cmid, $course->id);
        $this->assertCount(1, $tagged);
        $this->assertContains($id2, $tagged);
        $this->assertNotContains($id1, $tagged);
    }

    /**
     * @covers \local_learningoutcomes\manager::set_cm_outcomes
     */
    public function test_set_cm_outcomes_empty_clears_all_tags(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $page   = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $id     = $this->create_outcome($course->id, 'LO1');

        manager::set_cm_outcomes($page->cmid, $course->id, [$id]);
        manager::set_cm_outcomes($page->cmid, $course->id, []);

        $this->assertEmpty(manager::get_cm_outcome_ids($page->cmid, $course->id));
    }

    // -------------------------------------------------------------------------
    // get_alignment_score
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\manager::get_alignment_score
     */
    public function test_get_alignment_score_all_tagged(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // Use assign so the activity has a grade item and is non-decorative.
        $assign1 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $assign2 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $outcome = $this->create_outcome($course->id, 'LO1');

        manager::set_cm_outcomes($assign1->cmid, $course->id, [$outcome]);
        manager::set_cm_outcomes($assign2->cmid, $course->id, [$outcome]);

        $score = manager::get_alignment_score($course->id);

        $this->assertSame(2, $score['tagged']);
        $this->assertSame(0, $score['untagged']);
        $this->assertEquals(100.0, $score['score']);
    }

    /**
     * @covers \local_learningoutcomes\manager::get_alignment_score
     */
    public function test_get_alignment_score_partial(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $assign1 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id]); // untagged
        $outcome = $this->create_outcome($course->id, 'LO1');

        manager::set_cm_outcomes($assign1->cmid, $course->id, [$outcome]);

        $score = manager::get_alignment_score($course->id);

        $this->assertSame(1, $score['tagged']);
        $this->assertSame(1, $score['untagged']);
        $this->assertEquals(50.0, $score['score']);
    }

    /**
     * @covers \local_learningoutcomes\manager::get_alignment_score
     */
    public function test_get_alignment_score_empty_course_returns_zero(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $score  = manager::get_alignment_score($course->id);

        $this->assertSame(0.0, $score['score']);
        $this->assertSame(0, $score['total']);
    }

    // -------------------------------------------------------------------------
    // save_course_settings / get_course_settings
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\manager::save_course_settings
     * @covers \local_learningoutcomes\manager::get_course_settings
     */
    public function test_save_and_get_course_settings(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        manager::save_course_settings($course->id, 1);
        $settings = manager::get_course_settings($course->id);
        $this->assertSame(1, (int) $settings->enabled);

        manager::save_course_settings($course->id, 0);
        $settings = manager::get_course_settings($course->id);
        $this->assertSame(0, (int) $settings->enabled);

        manager::save_course_settings($course->id, null);
        $settings = manager::get_course_settings($course->id);
        $this->assertNull($settings->enabled);
    }

    /**
     * @covers \local_learningoutcomes\manager::save_course_settings
     */
    public function test_save_course_settings_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        manager::save_course_settings($course->id, 1);
        manager::save_course_settings($course->id, 1);

        $count = $DB->count_records('local_lo_course_settings', ['courseid' => $course->id]);
        $this->assertSame(1, $count);
    }
}
