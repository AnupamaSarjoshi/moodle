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
 * Language strings for local_learningoutcomes.
 *
 * @package   local_learningoutcomes
 * @copyright 2026 Moodle Pty Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin name.
$string['pluginname'] = 'Learning outcomes';

// Privacy.
$string['privacy:metadata'] = 'The learning outcomes plugin stores course-content data only. It does not store any personal data.';

// Capabilities.
$string['learningoutcomes:manage'] = 'Manage learning outcomes for a course';
$string['learningoutcomes:view'] = 'View learning outcomes for a course';
$string['learningoutcomes:viewreport'] = 'View the alignment report for a course';

// Admin settings.
$string['settings:heading'] = 'Learning outcomes';
$string['settings:enabled'] = 'Enable learning outcomes';
$string['settings:enabled_desc'] = 'When enabled, teachers can define course-level learning outcomes and tag activities to them. Students see which outcomes each activity supports.';
$string['settings:coursesdefault'] = 'Enable for new courses by default';
$string['settings:coursesdefault_desc'] = 'When the feature is enabled, this setting controls whether it is switched on automatically for newly created courses.';
$string['settings:mincount'] = 'Minimum learning outcomes per course';
$string['settings:mincount_desc'] = 'The minimum number of learning outcomes a course should have before it is considered set up. The default is 3.';
$string['settings:enforcement'] = 'Enforcement mode';
$string['settings:enforcement_desc'] = 'Controls how the minimum outcomes requirement is communicated to teachers. <strong>Soft</strong> mode shows a strong nudge but never blocks saving or publishing. <strong>Hard</strong> mode blocks course save and publish until the minimum is met.';
$string['settings:enforcement_soft'] = 'Soft (strong nudge, never blocks)';
$string['settings:enforcement_hard'] = 'Hard (blocks save and publish)';

// Course management page.
$string['manageoutcomes'] = 'Manage learning outcomes';
$string['courseoutcomes'] = 'Course learning outcomes';
$string['addoutcome'] = 'Add learning outcome';
$string['editoutcome'] = 'Edit learning outcome';
$string['deleteoutcome'] = 'Delete learning outcome';
$string['deleteoutcomeconfirm'] = 'Are you sure you want to delete the learning outcome "{$a}"? This will also remove all activity tags linked to it.';
$string['nooutcomes'] = 'No learning outcomes have been defined for this course yet.';
$string['outcomeadded'] = 'Learning outcome added successfully.';
$string['outcomeupdated'] = 'Learning outcome updated successfully.';
$string['outcomedeleted'] = 'Learning outcome deleted successfully.';

// Outcome form fields.
$string['outcomefullname'] = 'Learning outcome statement';
$string['outcomefullname_help'] = 'A clear, concise statement of what students will be able to do, know, or understand by the end of this course. For example: "Explain the key principles of constructive alignment."';
$string['outcomeshortname'] = 'Short name (code)';
$string['outcomeshortname_help'] = 'An optional short code or abbreviation for this outcome, for example "LO1". Used in the alignment report and activity-tagging interface.';
$string['outcomescale'] = 'Assessment scale (optional)';
$string['outcomescale_help'] = 'You may optionally link this outcome to an assessment scale. This is not required.';
$string['outcomedescription'] = 'Description (optional)';
$string['outcomedescription_help'] = 'An extended description of the outcome, if needed.';

// Activity tagging.
$string['tagactivities'] = 'Tag activities';
$string['tagactivity'] = 'Link to learning outcomes';
$string['tagactivity_help'] = 'Select the learning outcomes this activity contributes to. Students will see these links on the activity page.';
$string['nooutcomestotag'] = 'No learning outcomes are defined for this course. <a href="{$a}">Add learning outcomes</a> before tagging activities.';
$string['activitytagsaved'] = 'Activity outcome tags saved.';
$string['selectoutcomes'] = 'Select learning outcomes';
$string['decorativeactivity'] = 'This is an informational or decorative activity';
$string['decorativeactivity_help'] = 'Mark this activity as informational or decorative if it does not contribute to any learning outcome (for example, a label used as a section heading or an announcement forum). It will not appear as an untagged gap in the alignment report.';

// Student-facing strings.
$string['learningoutcomes'] = 'Learning outcomes';
$string['activitysupports'] = 'This activity supports the following learning outcomes:';
$string['nonetagged'] = 'No learning outcomes have been linked to this activity.';
$string['viewalloutcomes'] = 'View all course learning outcomes';
$string['coursepageheading'] = 'What you will learn';

// Alignment report.
$string['alignmentreport'] = 'Learning outcomes alignment';
$string['alignmentreport_desc'] = 'This report shows how well your course activities and assessments are aligned to your learning outcomes.';
$string['outcomeswithoutactivities'] = 'Learning outcomes without supporting activities';
$string['activitieswithoutoutcomes'] = 'Activities without learning outcomes';
$string['activitiestaggedto'] = 'Activities supporting this outcome';
$string['outcomecoverage'] = 'Coverage';
$string['fullycovered'] = 'Fully covered';
$string['partiallycovered'] = 'Partially covered';
$string['notcovered'] = 'Not covered';
$string['alignmentscore'] = 'Alignment score';
$string['alignmentscore_help'] = 'The percentage of non-decorative activities that are tagged to at least one learning outcome.';
$string['decorativecount'] = '{$a} informational / decorative activities (excluded from gap analysis)';
$string['taggedcount'] = '{$a} activities tagged to at least one outcome';
$string['untaggedcount'] = '{$a} activities with no outcome tag';
$string['nooutcomesdefined'] = 'No learning outcomes have been defined for this course.';
$string['addoutcomeslink'] = 'Add learning outcomes';
$string['reportheading'] = 'Alignment report';
$string['outcomerow'] = 'Learning outcome';
$string['activityrow'] = 'Activity';

// Nudge / incomplete course notices.
$string['nudge:incomplete'] = 'This course has fewer than the recommended {$a->min} learning outcomes. <a href="{$a->url}">Add learning outcomes</a> to help students understand what they will achieve.';
$string['nudge:untagged'] = '{$a->count} {$a->activities} in this course {$a->are} not linked to any learning outcome. <a href="{$a->url}">View the alignment report</a> to identify and close the gaps.';
$string['nudge:activity'] = 'singular';
$string['nudge:activities'] = 'plural';
$string['nudge:is'] = 'is';
$string['nudge:are'] = 'are';

// Course settings (inline toggle on course settings page).
$string['courseenabled'] = 'Enable learning outcomes for this course';
$string['courseenabled_desc'] = 'When enabled, teachers can define learning outcomes and tag activities. Students see outcomes on the course page and each activity page.';
$string['courseinherit'] = 'Use site default';
$string['courseon'] = 'Enabled';
$string['courseoff'] = 'Disabled';

// Errors.
$string['error:invalidcourse'] = 'Invalid course ID.';
$string['error:invalidoutcome'] = 'Invalid learning outcome ID.';
$string['error:invalidcm'] = 'Invalid course module ID.';
$string['error:nopermission'] = 'You do not have permission to manage learning outcomes for this course.';
$string['error:outcomeduplicate'] = 'A learning outcome with this short name already exists in this course.';
$string['error:mincount'] = 'This course requires at least {$a} learning outcomes before it can be published.';
