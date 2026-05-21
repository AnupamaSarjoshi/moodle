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
 * Behat data generator for local_learningoutcomes.
 *
 * Enables the standard Moodle Behat step:
 *   Given the following "local_learningoutcomes > learning outcomes" exist: …
 *
 * @package   local_learningoutcomes
 * @category  test
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_learningoutcomes_generator extends behat_generator_base {

    /**
     * Returns the entities this generator can create via the standard Behat step.
     *
     * @return array[]
     */
    protected function get_creatable_entities(): array {
        return [
            'learning outcomes' => [
                'singular'      => 'learning outcome',
                'datagenerator' => 'learning_outcome',
                'required'      => ['shortname', 'fullname', 'course'],
                'switchids'     => ['course' => 'courseid'],
            ],
        ];
    }
}
