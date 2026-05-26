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
 * Course-level Learning Outcomes settings form.
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

/**
 * Form for toggling learning outcomes on/off for a single course.
 *
 * The enabled field uses a tri-state select:
 *   '' (empty) = inherit site default
 *   '1'        = enabled
 *   '0'        = disabled
 */
class course_settings_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement(
            'header',
            'courselearningoutcomes',
            get_string('courseenabled', 'local_learningoutcomes')
        );

        $options = [
            ''  => get_string('courseinherit', 'local_learningoutcomes'),
            '1' => get_string('courseon', 'local_learningoutcomes'),
            '0' => get_string('courseoff', 'local_learningoutcomes'),
        ];

        $mform->addElement(
            'select',
            'enabled',
            get_string('courseenabled', 'local_learningoutcomes'),
            $options
        );
        $mform->addHelpButton('enabled', 'courseenabled', 'local_learningoutcomes');
        $mform->setDefault('enabled', '');

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Left-aligned save button only — the Back button on the page replaces Cancel.
        $mform->addElement('html', '<div class="mt-3 mb-3">'
            . '<button type="submit" name="submitbutton" value="1" class="btn btn-primary">'
            . get_string('savechanges') . '</button></div>');
    }

    /**
     * Validates form data.
     *
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @return array Validation errors, keyed by field name.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!isset($data['courseid']) || $data['courseid'] < 1) {
            $errors['courseid'] = get_string('error:invalidcourse', 'local_learningoutcomes');
        }

        return $errors;
    }
}
