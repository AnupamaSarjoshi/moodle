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
 * Custom Behat step definitions for local_learningoutcomes.
 *
 * @package   local_learningoutcomes
 * @category  test
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;

// PHPUnit is not available in Behat context; use raw assertions.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Step definitions for local_learningoutcomes Behat tests.
 */
class behat_local_learningoutcomes extends behat_base {

    /**
     * Tags a named activity with a named outcome in a named course.
     *
     * Example:
     *   Given the "Test page" activity is tagged with outcome "LO1" in course "C1"
     *
     * @Given the :activityname activity is tagged with outcome :shortname in course :courseshortname
     * @param string $activityname The cm name.
     * @param string $shortname    The outcome short name.
     * @param string $courseshortname The course short name.
     */
    public function the_activity_is_tagged_with_outcome(
        string $activityname,
        string $shortname,
        string $courseshortname
    ): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        $outcome = $DB->get_record(
            'grade_outcomes',
            ['shortname' => $shortname, 'courseid' => $course->id],
            '*',
            MUST_EXIST
        );

        $modinfo = get_fast_modinfo($course->id);
        $cm = null;
        foreach ($modinfo->get_cms() as $candidate) {
            if ($candidate->name === $activityname) {
                $cm = $candidate;
                break;
            }
        }

        if ($cm === null) {
            throw new \RuntimeException(
                "Activity '{$activityname}' not found in course '{$courseshortname}'"
            );
        }

        \local_learningoutcomes\manager::set_cm_outcomes($cm->id, $course->id, [(int) $outcome->id]);
    }

    /**
     * Navigates directly to the manage learning outcomes page for a course.
     *
     * Example:
     *   When I navigate to the manage learning outcomes page for course "C1"
     *
     * @When I navigate to the manage learning outcomes page for course :courseshortname
     * @param string $courseshortname The course short name.
     */
    public function i_navigate_to_the_manage_learning_outcomes_page(string $courseshortname): void {
        global $DB;

        $courseid = $DB->get_field('course', 'id', ['shortname' => $courseshortname], MUST_EXIST);
        $url = new \moodle_url('/local/learningoutcomes/manage.php', ['courseid' => $courseid]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }

    /**
     * Checks that a link with the given text exists somewhere associated with a named element on the page.
     *
     * Example:
     *   Then the page should contain a link "Tag this activity" for "Page one"
     *
     * @Then the page should contain a link :linktext for :context
     * @param string $linktext  The visible text of the link.
     * @param string $context   A nearby text anchor (the row or surrounding text).
     */
    public function the_page_should_contain_a_link_for(string $linktext, string $context): void {
        // Look for the link anywhere on the page first, then assert the context text is visible too.
        $this->assertSession()->elementExists('xpath',
            '//a[normalize-space(.)="' . addslashes($linktext) . '"]'
        );
        $this->assertSession()->pageTextContains($context);
    }
}
