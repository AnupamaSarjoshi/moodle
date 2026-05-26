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
 * Renderable for the activity-level learning outcomes surface.
 *
 * Displayed on each activity's entry page showing which learning outcomes
 * this activity contributes to.
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
 * Renderable for the list of outcomes an activity supports.
 */
class activity_outcomes implements renderable, templatable {

    /** @var int The course ID. */
    protected int $courseid;

    /** @var int The course module ID. */
    protected int $cmid;

    /** @var stdClass[] Outcome records linked to this cm. */
    protected array $outcomes;

    /** @var bool Whether the current user can manage outcomes. */
    protected bool $canmanage;

    /** @var \moodle_url|null The URL to return to after managing outcomes. */
    protected ?\moodle_url $returnurl;

    /**
     * Constructor.
     *
     * @param int $courseid The course ID.
     * @param int $cmid The course module ID.
     * @param stdClass[] $outcomes Outcome records for this cm.
     * @param bool $canmanage Whether the current user can manage outcomes.
     * @param \moodle_url|null $returnurl URL to return to after managing (e.g. the activity page).
     */
    public function __construct(int $courseid, int $cmid, array $outcomes, bool $canmanage = false,
            ?\moodle_url $returnurl = null) {
        $this->courseid   = $courseid;
        $this->cmid       = $cmid;
        $this->outcomes   = $outcomes;
        $this->canmanage  = $canmanage;
        $this->returnurl  = $returnurl;
    }

    /**
     * Exports data for the Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->courseid    = $this->courseid;
        $data->cmid        = $this->cmid;
        $data->canmanage   = $this->canmanage;
        $data->hasoutcomes = !empty($this->outcomes);

        $data->outcomes = [];
        foreach ($this->outcomes as $outcome) {
            $data->outcomes[] = (object) [
                'id'        => (int) $outcome->id,
                'shortname' => format_string($outcome->shortname),
                'fullname'  => format_string($outcome->fullname),
            ];
        }

        // Teachers link to the management page; students get the read-only view page.
        $manageParams = ['courseid' => $this->courseid];
        if ($this->canmanage && $this->returnurl !== null) {
            $manageParams['returnurl'] = $this->returnurl->out(false);
        }
        $data->courseoutcomesurl = (new \moodle_url(
            $this->canmanage
                ? '/local/learningoutcomes/manage.php'
                : '/local/learningoutcomes/view.php',
            $manageParams
        ))->out(false);

        return $data;
    }
}
