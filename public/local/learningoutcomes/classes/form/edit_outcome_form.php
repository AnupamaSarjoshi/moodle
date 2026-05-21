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
 * Edit / create form for a course-scoped learning outcome.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes\form;

use moodleform;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Form for creating and editing a course-scoped learning outcome.
 */
class edit_outcome_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition(): void {
        global $CFG, $COURSE;

        $mform = $this->_form;

        $mform->addElement(
            'header',
            'outcomeheader',
            get_string('outcome', 'grades')
        );

        // --- Full outcome statement ------------------------------------------

        $mform->addElement(
            'textarea',
            'fullname',
            get_string('outcomefullname', 'local_learningoutcomes'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addHelpButton('fullname', 'outcomefullname', 'local_learningoutcomes');

        // --- Short name / code -----------------------------------------------

        $mform->addElement(
            'text',
            'shortname',
            get_string('outcomeshortname', 'local_learningoutcomes'),
            'size="20"'
        );
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_NOTAGS);
        $mform->addHelpButton('shortname', 'outcomeshortname', 'local_learningoutcomes');

        // --- Optional scale --------------------------------------------------

        $scales = $this->_get_scale_options();
        $mform->addElement(
            'select',
            'scaleid',
            get_string('outcomescale', 'local_learningoutcomes'),
            $scales
        );
        $mform->setDefault('scaleid', 0);
        $mform->addHelpButton('scaleid', 'outcomescale', 'local_learningoutcomes');

        // --- Optional description --------------------------------------------

        $editoroptions = [
            'maxfiles' => 0,
            'maxbytes' => 0,
            'context'  => $this->_customdata['context'] ?? null,
        ];

        $mform->addElement(
            'editor',
            'description_editor',
            get_string('outcomedescription', 'local_learningoutcomes'),
            null,
            $editoroptions
        );
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addHelpButton('description_editor', 'outcomedescription', 'local_learningoutcomes');

        // --- Hidden fields ---------------------------------------------------

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Validates form data server-side.
     *
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @return array Validation errors, keyed by field name.
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);

        // Check shortname uniqueness within the course (exclude current record on edit).
        $params = ['courseid' => $data['courseid'], 'shortname' => $data['shortname']];
        $existing = $DB->get_field('grade_outcomes', 'id', $params);
        if ($existing && $existing != $data['id']) {
            $errors['shortname'] = get_string('error:outcomeduplicate', 'local_learningoutcomes');
        }

        return $errors;
    }

    /**
     * Builds the scale select options, with a leading "none" option.
     *
     * @return array<int|string, string>
     */
    private function _get_scale_options(): array {
        $courseid = $this->_customdata['courseid'] ?? 0;

        $options = [0 => get_string('none')];

        if ($courseid && $local = \grade_scale::fetch_all_local($courseid)) {
            $options[-1] = '-- ' . get_string('scalescustom', 'grades');
            foreach ($local as $scale) {
                $options[$scale->id] = $scale->get_name();
            }
        }

        if ($global = \grade_scale::fetch_all_global()) {
            $options[-2] = '-- ' . get_string('scalesstandard', 'grades');
            foreach ($global as $scale) {
                $options[$scale->id] = $scale->get_name();
            }
        }

        return $options;
    }
}
