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
 * AMD module for the learning outcomes activity-tagging widget.
 *
 * Renders a dropdown/panel adjacent to each activity card on the course page,
 * allowing editing teachers to tag activities to outcomes without leaving the
 * course view.
 *
 * @module     local_learningoutcomes/tag_activity
 * @copyright  2026 Moodle Pty Ltd
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

/** CSS selector for activity items on the course page. */
const SELECTOR_ACTIVITY = '[data-activityname]';
/** Attribute on the cm element that holds the course module ID. */
const ATTR_CMID = 'data-cmid';
/** Attribute on the cm element that holds the course ID. */
const ATTR_COURSEID = 'data-courseid';

/**
 * Fetches learning outcomes for a course from the server.
 *
 * @param {number} courseid
 * @param {number} cmid
 * @returns {Promise<Array>}
 */
const fetchOutcomes = (courseid, cmid) => Ajax.call([{
    methodname: 'local_learningoutcomes_get_course_outcomes',
    args: {courseid, cmid},
}])[0];

/**
 * Saves the selected outcomes for a course module.
 *
 * @param {number} courseid
 * @param {number} cmid
 * @param {number[]} outcomeids
 * @param {boolean} isdecorative
 * @returns {Promise<{success: boolean}>}
 */
const saveOutcomes = (courseid, cmid, outcomeids, isdecorative) => Ajax.call([{
    methodname: 'local_learningoutcomes_tag_activity',
    args: {courseid, cmid, outcomeids, isdecorative},
}])[0];

/**
 * Builds and inserts the tagging panel for a single activity element.
 *
 * @param {HTMLElement} activityEl  The activity element (e.g. li.activity).
 * @param {number}      courseid
 * @param {number}      cmid
 */
const initActivityPanel = async(activityEl, courseid, cmid) => {
    let outcomes;
    try {
        outcomes = await fetchOutcomes(courseid, cmid);
    } catch (e) {
        Notification.exception(e);
        return;
    }

    const tagLabel = await getString('tagactivity', 'local_learningoutcomes');
    const saveLabel = await getString('savechanges');
    const decorativeLabel = await getString('decorativeactivity', 'local_learningoutcomes');

    const templateContext = {
        cmid,
        courseid,
        outcomes,
        taglabel: tagLabel,
        savelabel: saveLabel,
        decorativelabel: decorativeLabel,
    };

    const {html, js} = await Templates.renderForPromise(
        'local_learningoutcomes/tag_activity_panel',
        templateContext
    );

    // Insert the panel immediately after the activity's action menu area.
    const actionMenu = activityEl.querySelector('.actions') ?? activityEl;
    const panelWrapper = document.createElement('div');
    panelWrapper.className = 'lo-tag-panel mt-1';
    actionMenu.insertAdjacentElement('afterend', panelWrapper);
    Templates.appendNodeContents(panelWrapper, html, js);

    // Wire up the save button inside the panel.
    panelWrapper.addEventListener('click', async(e) => {
        const saveBtn = e.target.closest('[data-action="lo-save"]');
        if (!saveBtn) {
            return;
        }

        e.preventDefault();
        const panel = saveBtn.closest('.lo-tag-panel');
        const checkedBoxes = [...panel.querySelectorAll('input[type="checkbox"][data-outcomeid]:checked')];
        const selectedIds = checkedBoxes.map(cb => parseInt(cb.dataset.outcomeid, 10));
        const isdecorative = panel.querySelector('[data-decorative]')?.checked ?? false;

        try {
            await saveOutcomes(courseid, cmid, selectedIds, isdecorative);
            // Brief visual confirmation.
            saveBtn.textContent = '✓';
            saveBtn.disabled = true;
            setTimeout(() => {
                saveBtn.textContent = saveLabel;
                saveBtn.disabled = false;
            }, 2000);
        } catch (err) {
            Notification.exception(err);
        }
    });
};

/**
 * Initialises the tagging widget on course pages where the user has the
 * manage capability.
 *
 * @param {number} courseid The current course ID.
 */
export const init = (courseid) => {
    const activities = document.querySelectorAll(SELECTOR_ACTIVITY);
    activities.forEach((el) => {
        const cmid = parseInt(el.getAttribute(ATTR_CMID) ?? el.closest('[data-cmid]')?.getAttribute('data-cmid') ?? '0', 10);
        if (cmid > 0) {
            initActivityPanel(el, courseid, cmid);
        }
    });
};
