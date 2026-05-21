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
 * Tests for lib.php callbacks (is_enabled_for_course tri-state logic).
 *
 * @package   local_learningoutcomes
 * @category  phpunit
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/learningoutcomes/lib.php');

/**
 * Tests for local_learningoutcomes_is_enabled_for_course().
 *
 * @covers ::local_learningoutcomes_is_enabled_for_course
 */
final class lib_test extends advanced_testcase {

    /**
     * Returns false when the site master switch is off, regardless of course or default settings.
     */
    public function test_returns_false_when_site_disabled(): void {
        $this->resetAfterTest();

        set_config('enabled', 0, 'local_learningoutcomes');
        set_config('coursesdefault', 1, 'local_learningoutcomes');

        $course = $this->getDataGenerator()->create_course();
        manager::save_course_settings($course->id, 1); // Explicit course override = on.

        $this->assertFalse(local_learningoutcomes_is_enabled_for_course($course->id));
    }

    /**
     * Returns the site default when no course-level override is set.
     */
    public function test_uses_site_default_when_no_course_setting(): void {
        $this->resetAfterTest();

        set_config('enabled', 1, 'local_learningoutcomes');

        // Default = on.
        set_config('coursesdefault', 1, 'local_learningoutcomes');
        $course = $this->getDataGenerator()->create_course();
        $this->assertTrue(local_learningoutcomes_is_enabled_for_course($course->id));

        // Default = off.
        set_config('coursesdefault', 0, 'local_learningoutcomes');
        $course2 = $this->getDataGenerator()->create_course();
        $this->assertFalse(local_learningoutcomes_is_enabled_for_course($course2->id));
    }

    /**
     * Course-level explicit override takes precedence over the site default.
     */
    public function test_course_override_takes_precedence_over_site_default(): void {
        $this->resetAfterTest();

        set_config('enabled', 1, 'local_learningoutcomes');
        set_config('coursesdefault', 0, 'local_learningoutcomes'); // Default = off.

        $course = $this->getDataGenerator()->create_course();
        manager::save_course_settings($course->id, 1); // Override = on.

        $this->assertTrue(local_learningoutcomes_is_enabled_for_course($course->id));
    }

    /**
     * Course-level explicit off takes precedence even when site default is on.
     */
    public function test_course_override_off_takes_precedence(): void {
        $this->resetAfterTest();

        set_config('enabled', 1, 'local_learningoutcomes');
        set_config('coursesdefault', 1, 'local_learningoutcomes'); // Default = on.

        $course = $this->getDataGenerator()->create_course();
        manager::save_course_settings($course->id, 0); // Override = off.

        $this->assertFalse(local_learningoutcomes_is_enabled_for_course($course->id));
    }

    /**
     * Null course setting (inherit) falls back to site default.
     */
    public function test_null_course_setting_inherits_site_default(): void {
        $this->resetAfterTest();

        set_config('enabled', 1, 'local_learningoutcomes');
        set_config('coursesdefault', 1, 'local_learningoutcomes');

        $course = $this->getDataGenerator()->create_course();
        manager::save_course_settings($course->id, null); // Inherit.

        $this->assertTrue(local_learningoutcomes_is_enabled_for_course($course->id));
    }
}
