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
 * Tests for local_learningoutcomes external API functions.
 *
 * @package   local_learningoutcomes
 * @category  phpunit
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes\external;

use advanced_testcase;
use local_learningoutcomes\manager;

/**
 * Tests for tag_activity and get_course_outcomes external functions.
 *
 * @covers \local_learningoutcomes\external\tag_activity
 * @covers \local_learningoutcomes\external\get_course_outcomes
 */
final class external_test extends advanced_testcase {

    // -------------------------------------------------------------------------
    // get_course_outcomes::execute
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\external\get_course_outcomes::execute
     */
    public function test_get_course_outcomes_no_cmid_returns_all(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $id1 = manager::create_outcome($course->id, 'Outcome One', 'LO1');
        $id2 = manager::create_outcome($course->id, 'Outcome Two', 'LO2');

        $this->setUser($teacher);
        $result = get_course_outcomes::execute($course->id, 0);

        $this->assertArrayHasKey('outcomes', $result);
        $this->assertArrayHasKey('isdecorative', $result);
        $this->assertCount(2, $result['outcomes']);
        $ids = array_column($result['outcomes'], 'id');
        $this->assertContains($id1, $ids);
        $this->assertContains($id2, $ids);

        // With no cm context, nothing should be tagged.
        foreach ($result['outcomes'] as $row) {
            $this->assertFalse($row['tagged']);
        }
    }

    /**
     * @covers \local_learningoutcomes\external\get_course_outcomes::execute
     */
    public function test_get_course_outcomes_with_cmid_marks_tagged(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $page    = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $id1 = manager::create_outcome($course->id, 'Outcome One', 'LO1');
        $id2 = manager::create_outcome($course->id, 'Outcome Two', 'LO2');
        manager::set_cm_outcomes($page->cmid, $course->id, [$id1]);

        $this->setUser($teacher);
        $result = get_course_outcomes::execute($course->id, $page->cmid);

        $byid = [];
        foreach ($result['outcomes'] as $row) {
            $byid[$row['id']] = $row;
        }

        $this->assertTrue($byid[$id1]['tagged']);
        $this->assertFalse($byid[$id2]['tagged']);
        $this->assertFalse($result['isdecorative']);
    }

    /**
     * @covers \local_learningoutcomes\external\get_course_outcomes::execute
     */
    public function test_get_course_outcomes_requires_view_capability(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        manager::create_outcome($course->id, 'Outcome', 'LO1');

        // Students have the view capability by default; make sure the call succeeds.
        $this->setUser($student);
        $result = get_course_outcomes::execute($course->id, 0);
        $this->assertArrayHasKey('outcomes', $result);
    }

    // -------------------------------------------------------------------------
    // tag_activity::execute
    // -------------------------------------------------------------------------

    /**
     * @covers \local_learningoutcomes\external\tag_activity::execute
     */
    public function test_tag_activity_sets_outcomes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $page    = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $id1 = manager::create_outcome($course->id, 'Outcome One', 'LO1');
        $id2 = manager::create_outcome($course->id, 'Outcome Two', 'LO2');

        $this->setUser($teacher);
        $result = tag_activity::execute($course->id, $page->cmid, [$id1, $id2]);

        $this->assertTrue($result['success']);

        $tagged = manager::get_cm_outcome_ids($page->cmid, $course->id);
        $this->assertContains($id1, $tagged);
        $this->assertContains($id2, $tagged);
    }

    /**
     * @covers \local_learningoutcomes\external\tag_activity::execute
     */
    public function test_tag_activity_replaces_previous_tags(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $page    = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $id1 = manager::create_outcome($course->id, 'Outcome One', 'LO1');
        $id2 = manager::create_outcome($course->id, 'Outcome Two', 'LO2');

        $this->setUser($teacher);
        tag_activity::execute($course->id, $page->cmid, [$id1, $id2]);
        tag_activity::execute($course->id, $page->cmid, [$id2]);

        $tagged = manager::get_cm_outcome_ids($page->cmid, $course->id);
        $this->assertCount(1, $tagged);
        $this->assertContains($id2, $tagged);
    }

    /**
     * @covers \local_learningoutcomes\external\tag_activity::execute
     */
    public function test_tag_activity_requires_manage_capability(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course  = $this->getDataGenerator()->create_course();
        $page    = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $id1     = manager::create_outcome($course->id, 'Outcome', 'LO1');

        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        tag_activity::execute($course->id, $page->cmid, [$id1]);
    }

    /**
     * @covers \local_learningoutcomes\external\tag_activity::execute
     */
    public function test_tag_activity_rejects_wrong_course_cm(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $page    = $this->getDataGenerator()->create_module('page', ['course' => $course2->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course1, 'editingteacher');
        $id1     = manager::create_outcome($course1->id, 'Outcome', 'LO1');

        $this->setUser($teacher);

        // Passing a cmid that belongs to course2 while claiming course1 should fail.
        $this->expectException(\dml_missing_record_exception::class);
        tag_activity::execute($course1->id, $page->cmid, [$id1]);
    }
}
