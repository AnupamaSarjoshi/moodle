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
 * Test data generator for local_learningoutcomes.
 *
 * @package   local_learningoutcomes
 * @category  test
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_learningoutcomes_generator extends component_generator_base {

    /**
     * Creates a course-scoped learning outcome.
     *
     * Accepted keys:
     *   - shortname (required)
     *   - fullname  (required)
     *   - courseid  (required, or pass 'course' as a shortname and let the
     *                behat_generator switchid resolve it)
     *   - description (optional)
     *   - scaleid     (optional)
     *
     * @param array|stdClass $record
     * @return stdClass The inserted grade_outcomes record.
     */
    public function create_learning_outcome($record): stdClass {
        global $DB;

        $record = (array) $record;

        if (empty($record['courseid'])) {
            throw new coding_exception('local_learningoutcomes generator: courseid is required');
        }
        if (empty($record['shortname'])) {
            throw new coding_exception('local_learningoutcomes generator: shortname is required');
        }
        if (empty($record['fullname'])) {
            throw new coding_exception('local_learningoutcomes generator: fullname is required');
        }

        $id = \local_learningoutcomes\manager::create_outcome(
            (int) $record['courseid'],
            $record['fullname'],
            $record['shortname'],
            $record['description'] ?? null,
            FORMAT_HTML,
            !empty($record['scaleid']) ? (int) $record['scaleid'] : null
        );

        return $DB->get_record('grade_outcomes', ['id' => $id], '*', MUST_EXIST);
    }
}
