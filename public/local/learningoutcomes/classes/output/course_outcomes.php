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
 * Renderable for the course-level learning outcomes surface.
 *
 * Displayed prominently on the course main page for all enrolled users.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderable carrying the list of course learning outcomes for the course page.
 */
class course_outcomes implements renderable, templatable {

    /** @var stdClass[] Array of outcome records. */
    protected array $outcomes;

    /** @var int The course ID. */
    protected int $courseid;

    /** @var bool Whether the current user can manage outcomes. */
    protected bool $canmanage;

    /**
     * Constructor.
     *
     * @param int $courseid The course ID.
     * @param stdClass[] $outcomes Array of outcome records.
     * @param bool $canmanage Whether the current user can manage outcomes.
     */
    public function __construct(int $courseid, array $outcomes, bool $canmanage = false) {
        $this->courseid = $courseid;
        $this->outcomes = $outcomes;
        $this->canmanage = $canmanage;
    }

    /**
     * Exports data for the Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->courseid  = $this->courseid;
        $data->canmanage = $this->canmanage;
        $data->hasoutcomes = !empty($this->outcomes);

        $data->outcomes = [];
        foreach ($this->outcomes as $outcome) {
            $data->outcomes[] = (object) [
                'id'        => (int) $outcome->id,
                'shortname' => format_string($outcome->shortname),
                'fullname'  => format_string($outcome->fullname),
            ];
        }

        $data->manageurl = (new \moodle_url(
            '/local/learningoutcomes/manage.php',
            ['courseid' => $this->courseid]
        ))->out(false);

        return $data;
    }
}
