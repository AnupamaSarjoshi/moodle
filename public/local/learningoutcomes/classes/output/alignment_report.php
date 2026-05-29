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
 * Renderable for the course alignment report.
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
use local_learningoutcomes\manager;

/**
 * Builds the two-way coverage data for the alignment report.
 *
 * Two-way means:
 *   1. Outcomes with no supporting activities (gap in activities).
 *   2. Non-decorative activities with no outcome tag (gap in outcomes).
 */
class alignment_report implements renderable, templatable {

    /** @var int The course ID. */
    protected int $courseid;

    /**
     * Constructor.
     *
     * @param int $courseid
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Exports data for the alignment_report Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $DB;

        $data = new stdClass();
        $data->courseid = $this->courseid;

        $outcomes = manager::get_available_outcomes($this->courseid);
        $data->hasoutcomes = !empty($outcomes);

        if (!$data->hasoutcomes) {
            $data->outcomes           = [];
            $data->untaggedactivities = [];
            $data->score              = 0;
            $data->tagged             = 0;
            $data->untagged           = 0;
            $data->decorative         = 0;
            $data->manageurl = (new \moodle_url(
                '/local/learningoutcomes/manage.php',
                ['courseid' => $this->courseid]
            ))->out(false);

            return $data;
        }

        // Build a map of outcomeid → list of cm names.
        $tagrecords  = manager::get_course_activity_tags($this->courseid);
        $modinfo     = get_fast_modinfo($this->courseid);
        $allcms      = $modinfo->get_cms();

        // Reverse index: outcomeid → [cminfo, ...]
        $outcomeactivities = [];
        foreach ($tagrecords as $cmid => $tags) {
            foreach ($tags as $tag) {
                $outcomeactivities[(int) $tag->outcomeid][] = $cmid;
            }
        }

        // Build outcomes with their coverage status.
        $data->outcomes = [];
        foreach ($outcomes as $outcome) {
            $oid = (int) $outcome->id;
            $cmids = $outcomeactivities[$oid] ?? [];

            $activities = [];
            foreach ($cmids as $cmid) {
                if (isset($allcms[$cmid])) {
                    $activities[] = (object) [
                        'name'    => format_string($allcms[$cmid]->name),
                        'viewurl' => $allcms[$cmid]->url ? $allcms[$cmid]->url->out(false) : null,
                    ];
                }
            }

            $data->outcomes[] = (object) [
                'id'           => $oid,
                'shortname'    => format_string($outcome->shortname),
                'fullname'     => format_string($outcome->fullname),
                'hasactivities' => !empty($activities),
                'activities'   => $activities,
                'activitycount' => count($activities),
                'tagurl'       => null, // Tagging is done per-activity via tag.php.
                'siteoutcome'  => empty($outcome->courseid), // True for site-wide outcomes.
            ];
        }

        // Untagged non-decorative activities.
        $score    = manager::get_alignment_score($this->courseid);
        $taggedbycm = array_keys($tagrecords);

        $data->untaggedactivities = [];
        foreach ($allcms as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            if (manager::is_decorative($cm)) {
                continue;
            }
            if (!in_array($cm->id, $taggedbycm)) {
                $data->untaggedactivities[] = (object) [
                    'name'   => format_string($cm->name),
                    'tagurl' => (new \moodle_url(
                        '/local/learningoutcomes/tag.php',
                        [
                            'courseid'  => $this->courseid,
                            'cmid'      => $cm->id,
                            'returnurl' => (new \moodle_url(
                                '/local/learningoutcomes/alignment.php',
                                ['courseid' => $this->courseid]
                            ))->out_as_local_url(false),
                        ]
                    ))->out(false),
                ];
            }
        }

        $data->score      = $score['score'];
        $data->tagged     = $score['tagged'];
        $data->untagged   = $score['untagged'];
        $data->decorative = $score['decorative'];
        $data->manageurl  = (new \moodle_url(
            '/local/learningoutcomes/manage.php',
            ['courseid' => $this->courseid]
        ))->out(false);

        $data->hasuntaggedactivities = !empty($data->untaggedactivities);
        $data->scorepercent = number_format($score['score'], 0);
        $data->fullyaligned = $score['score'] >= 100.0;

        return $data;
    }
}
