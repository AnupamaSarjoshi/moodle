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
 * Admin settings for local_learningoutcomes.
 *
 * Registers a "Learning outcomes" page under Site administration > Courses.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_learningoutcomes',
        new lang_string('settings:heading', 'local_learningoutcomes')
    );

    // --- Site master switch ---------------------------------------------------

    $settings->add(new admin_setting_configcheckbox(
        'local_learningoutcomes/enabled',
        new lang_string('settings:enabled', 'local_learningoutcomes'),
        new lang_string('settings:enabled_desc', 'local_learningoutcomes'),
        0  // Off by default; administrators opt-in per site.
    ));

    // --- Default for new courses ----------------------------------------------

    $settings->add(new admin_setting_configcheckbox(
        'local_learningoutcomes/coursesdefault',
        new lang_string('settings:coursesdefault', 'local_learningoutcomes'),
        new lang_string('settings:coursesdefault_desc', 'local_learningoutcomes'),
        1  // On by default when the site switch is on.
    ));

    // --- Minimum outcomes per course -----------------------------------------

    $settings->add(new admin_setting_configtext(
        'local_learningoutcomes/mincount',
        new lang_string('settings:mincount', 'local_learningoutcomes'),
        new lang_string('settings:mincount_desc', 'local_learningoutcomes'),
        3,       // Default: 3 outcomes.
        PARAM_INT
    ));

    // --- Enforcement mode ----------------------------------------------------

    $enforcementoptions = [
        'soft' => new lang_string('settings:enforcement_soft', 'local_learningoutcomes'),
        'hard' => new lang_string('settings:enforcement_hard', 'local_learningoutcomes'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_learningoutcomes/enforcement',
        new lang_string('settings:enforcement', 'local_learningoutcomes'),
        new lang_string('settings:enforcement_desc', 'local_learningoutcomes'),
        'soft',
        $enforcementoptions
    ));

    // Register the page under the "Courses" section of site administration.
    $ADMIN->add('courses', $settings);
}
