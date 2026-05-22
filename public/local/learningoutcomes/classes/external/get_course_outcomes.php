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
 * External API: get learning outcomes for a course.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_learningoutcomes\manager;

/**
 * Returns the learning outcomes available for a course and the current
 * tag state for a given course module.
 */
class get_course_outcomes extends external_api {

    /**
     * Parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'cmid'     => new external_value(PARAM_INT, 'Course module ID (0 = no CM context)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns outcomes and, when cmid > 0, the IDs already tagged to that CM.
     *
     * @param int $courseid The course ID.
     * @param int $cmid Optional course module ID.
     * @return array
     */
    public static function execute(int $courseid, int $cmid = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid'     => $cmid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/learningoutcomes:view', $context);

        $outcomes = manager::get_available_outcomes($params['courseid']);
        $tagged   = $params['cmid'] > 0
            ? manager::get_cm_outcome_ids($params['cmid'], $params['courseid'])
            : [];
        $isdecorative = $params['cmid'] > 0
            ? manager::get_cm_decorative($params['cmid'], $params['courseid'])
            : false;

        $result = [];
        foreach ($outcomes as $outcome) {
            $result[] = [
                'id'          => (int) $outcome->id,
                'shortname'   => $outcome->shortname,
                'fullname'    => $outcome->fullname,
                'tagged'      => in_array((int) $outcome->id, $tagged),
            ];
        }

        return [
            'outcomes'    => $result,
            'isdecorative' => $isdecorative,
        ];
    }

    /**
     * Return structure.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'outcomes' => new external_multiple_structure(
                new external_single_structure([
                    'id'        => new external_value(PARAM_INT, 'Outcome ID'),
                    'shortname' => new external_value(PARAM_TEXT, 'Short name / code'),
                    'fullname'  => new external_value(PARAM_TEXT, 'Full outcome statement'),
                    'tagged'    => new external_value(PARAM_BOOL, 'Whether this outcome is tagged to the given CM'),
                ])
            ),
            'isdecorative' => new external_value(PARAM_BOOL, 'Whether the teacher has marked this CM as decorative/informational'),
        ]);
    }
}
