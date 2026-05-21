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
 * Defines the backup steps for local_learningoutcomes.
 *
 * Backs up:
 *  - local_lo_course_settings   (once per course)
 *  - local_lo_activity_outcome  (once per cm, grouped by cm)
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised backup plugin for local_learningoutcomes (course-level plugin).
 *
 * Moodle calls backup_local_plugin::define_course_plugin_structure() when
 * building a course-level backup.  We attach our data as child elements of
 * the <course> element.
 */
class backup_local_learningoutcomes_plugin extends backup_local_plugin {

    /**
     * Returns the subtree of backup elements that represent this plugin's data.
     *
     * Structure produced in the backup XML:
     *
     * <learningoutcomes>
     *   <course_settings>
     *     <enabled>…</enabled>
     *   </course_settings>
     *   <activity_outcomes>
     *     <activity_outcome id="…">
     *       <cmid>…</cmid>
     *       <outcomeid>…</outcomeid>
     *     </activity_outcome>
     *   </activity_outcomes>
     * </learningoutcomes>
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        // Root wrapper for this plugin's data (invisible optigroup container).
        $plugin = $this->get_plugin_element();

        // Visible XML wrapper element — gives the path:
        //   plugin_local_learningoutcomes_course/...
        // which matches what restore_local_learningoutcomes_plugin::get_pathfor() expects.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        // --- Course settings (single record) -----------------------------------
        $coursesettings = new backup_nested_element(
            'lo_course_settings',
            null,
            ['enabled']
        );
        $coursesettings->set_source_table(
            'local_lo_course_settings',
            ['courseid' => backup::VAR_COURSEID]
        );

        // --- Activity–outcome tags (all cm records for this course) ------------
        $activityoutcomes = new backup_nested_element('lo_activity_outcomes');
        $activityoutcome  = new backup_nested_element(
            'lo_activity_outcome',
            ['id'],
            ['cmid', 'outcomeid', 'timecreated', 'timemodified', 'usermodified']
        );
        $activityoutcomes->add_child($activityoutcome);

        $activityoutcome->set_source_table(
            'local_lo_activity_outcome',
            ['courseid' => backup::VAR_COURSEID]
        );

        // Annotate cmid so the restore step can remap it.
        $activityoutcome->annotate_ids('course_module', 'cmid');

        // Build the tree.
        $pluginwrapper->add_child($coursesettings);
        $pluginwrapper->add_child($activityoutcomes);

        return $plugin;
    }
}
