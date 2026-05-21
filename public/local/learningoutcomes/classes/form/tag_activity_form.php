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
 * Tag activity form — links a course module to one or more learning outcomes.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningoutcomes\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for tagging a course module with one or more learning outcomes.
 */
class tag_activity_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;

        $outcomes = $this->_customdata['outcomes'] ?? [];
        $courseid = $this->_customdata['courseid'] ?? 0;
        $cmid     = $this->_customdata['cmid'] ?? 0;

        $mform->addElement(
            'header',
            'tagheader',
            get_string('tagactivity', 'local_learningoutcomes')
        );

        if (empty($outcomes)) {
            $manageurl = new \moodle_url('/local/learningoutcomes/manage.php', ['courseid' => $courseid]);
            $mform->addElement(
                'static',
                'nooutcomes',
                '',
                get_string('nooutcomestotag', 'local_learningoutcomes', $manageurl->out(false))
            );
        } else {
            // Multi-select checkboxes for each outcome.
            $checkboxgroup = [];
            foreach ($outcomes as $outcome) {
                $label = format_string($outcome->shortname . ' — ' . $outcome->fullname);
                $checkboxgroup[] = $mform->createElement(
                    'checkbox',
                    'outcome_' . $outcome->id,
                    $label
                );
                $mform->addElement(
                    'advcheckbox',
                    'outcomes[' . $outcome->id . ']',
                    $label,
                    '',
                    ['group' => 1],
                    [0, 1]
                );
            }

            $mform->addHelpButton('outcomes[' . array_key_first($outcomes) . ']', 'tagactivity', 'local_learningoutcomes');
        }

        // Decorative activity flag.
        $mform->addElement(
            'advcheckbox',
            'isdecorative',
            get_string('decorativeactivity', 'local_learningoutcomes'),
            '',
            [],
            [0, 1]
        );
        $mform->addHelpButton('isdecorative', 'decorativeactivity', 'local_learningoutcomes');

        // Hidden fields.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $this->add_action_buttons(true, get_string('savechanges'));
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

        if (empty($data['cmid'])) {
            $errors['cmid'] = get_string('error:invalidcm', 'local_learningoutcomes');
        }

        return $errors;
    }
}
