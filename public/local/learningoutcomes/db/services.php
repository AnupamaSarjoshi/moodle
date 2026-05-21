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
 * External functions registration for local_learningoutcomes.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_learningoutcomes_get_course_outcomes' => [
        'classname'   => \local_learningoutcomes\external\get_course_outcomes::class,
        'description' => 'Returns all learning outcomes available for a course.',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'local_learningoutcomes_tag_activity' => [
        'classname'   => \local_learningoutcomes\external\tag_activity::class,
        'description' => 'Tags a course module with one or more learning outcomes.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
