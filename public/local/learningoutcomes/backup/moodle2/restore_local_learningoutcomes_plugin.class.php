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
 * Defines the restore steps for local_learningoutcomes.
 *
 * Restores:
 *  - local_lo_course_settings   (once per course)
 *  - local_lo_activity_outcome  (per cm, with cmid remapping)
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised restore plugin for local_learningoutcomes (course-level plugin).
 *
 * Moodle calls restore_local_plugin::define_course_plugin_structure() to get
 * the restore steps attached to the <course> element in the backup XML.
 */
class restore_local_learningoutcomes_plugin extends restore_local_plugin {

    /**
     * Returns the restore path elements for this plugin.
     *
     * Matches the XML structure emitted by the backup class:
     *
     *   plugin_local_learningoutcomes_course/lo_course_settings
     *   plugin_local_learningoutcomes_course/lo_activity_outcomes/lo_activity_outcome
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [
            new restore_path_element(
                'local_learningoutcomes_course_settings',
                $this->get_pathfor('/lo_course_settings')
            ),
            new restore_path_element(
                'local_learningoutcomes_activity_outcome',
                $this->get_pathfor('/lo_activity_outcomes/lo_activity_outcome')
            ),
        ];
    }

    // -------------------------------------------------------------------------
    // Handlers — called once per matching XML element
    // -------------------------------------------------------------------------

    /**
     * Restore one course_settings record.
     *
     * @param array $data Raw data from XML.
     */
    public function process_local_learningoutcomes_course_settings(array $data): void {
        global $DB;

        $data = (object) $data;

        // If a settings record already exists for this course (e.g. duplication
        // into the same site), update it; otherwise insert.
        $existing = $DB->get_record(
            'local_lo_course_settings',
            ['courseid' => $this->get_courseid()]
        );
        if ($existing) {
            $existing->enabled      = $data->enabled ?? null;
            $existing->timemodified = time();
            $existing->usermodified = $this->get_task()->get_userid();
            $DB->update_record('local_lo_course_settings', $existing);
        } else {
            $record = new stdClass();
            $record->courseid    = $this->get_courseid();
            $record->enabled     = $data->enabled ?? null;
            $record->timecreated = time();
            $record->timemodified = time();
            $record->usermodified = $this->get_task()->get_userid();
            $DB->insert_record('local_lo_course_settings', $record);
        }
    }

    /**
     * Restore one activity_outcome mapping record.
     *
     * @param array $data Raw data from XML.
     */
    public function process_local_learningoutcomes_activity_outcome(array $data): void {
        global $DB;

        $data = (object) $data;

        // Remap the old cmid to the new cmid in the restored course.
        $newcmid = $this->get_mappingid('course_module', $data->cmid);
        if (!$newcmid) {
            // If the activity no longer exists (e.g. partial restore), skip.
            return;
        }

        // Avoid duplicates caused by repeated restores into the same course.
        $existing = $DB->record_exists(
            'local_lo_activity_outcome',
            [
                'courseid'  => $this->get_courseid(),
                'cmid'      => $newcmid,
                'outcomeid' => $data->outcomeid,
            ]
        );
        if ($existing) {
            return;
        }

        $record = new stdClass();
        $record->courseid     = $this->get_courseid();
        $record->cmid         = $newcmid;
        $record->outcomeid    = $data->outcomeid;
        $record->timecreated  = time();
        $record->timemodified = time();
        $record->usermodified = $this->get_task()->get_userid();
        $DB->insert_record('local_lo_activity_outcome', $record);
    }
}
