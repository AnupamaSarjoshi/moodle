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
 * External API functions for local_learningoutcomes.
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
 * External functions for activity-outcome tagging.
 */
class tag_activity extends external_api {

    /**
     * Describes the parameters for local_learningoutcomes_tag_activity.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'    => new external_value(PARAM_INT, 'Course ID'),
            'cmid'        => new external_value(PARAM_INT, 'Course module ID'),
            'outcomeids'  => new external_multiple_structure(
                new external_value(PARAM_INT, 'Outcome ID'),
                'IDs of outcomes to tag this activity with'
            ),
            'isdecorative' => new external_value(PARAM_BOOL, 'Mark activity as decorative / informational', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Tags a course module with the given set of learning outcomes.
     *
     * @param int $courseid The course ID.
     * @param int $cmid The course module ID.
     * @param int[] $outcomeids Outcome IDs to tag.
     * @param bool $isdecorative Whether the activity is decorative.
     * @return array{success: bool}
     */
    public static function execute(int $courseid, int $cmid, array $outcomeids, bool $isdecorative = false): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'     => $courseid,
            'cmid'         => $cmid,
            'outcomeids'   => $outcomeids,
            'isdecorative' => $isdecorative,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/learningoutcomes:manage', $context);

        // Validate cm belongs to this course.
        $cm = get_coursemodule_from_id(null, $params['cmid'], $params['courseid'], false, MUST_EXIST);

        manager::set_cm_outcomes(
            $params['cmid'],
            $params['courseid'],
            $params['outcomeids'],
            $params['isdecorative']
        );

        return ['success' => true];
    }

    /**
     * Describes the return value for local_learningoutcomes_tag_activity.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
        ]);
    }
}
