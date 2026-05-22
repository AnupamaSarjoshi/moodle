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
 * Renders a colour-coded nudge panel adjacent to each assessable activity on
 * the course page, allowing editing teachers to tag activities to outcomes
 * without leaving the course view.
 *
 * State coding (shown in the <details> summary):
 *   amber  ⚠  — activity needs at least one outcome (not yet tagged, not decorative)
 *   green  ✓  — at least one outcome is tagged
 *   grey   —  — teacher has marked the activity as informational/decorative
 *
 * A course-level alert banner is injected above the course content showing how
 * many non-decorative activities still need outcome tags.
 *
 * @module     local_learningoutcomes/tag_activity
 * @copyright  2026 Moodle Pty Ltd
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getStrings} from 'core/str';

/** CSS selector for activity list items on the course page (li wrapper). */
const SELECTOR_ACTIVITY = '[data-for="cmitem"]';
/** Attribute on the cmitem element that holds the course module ID. */
const ATTR_CMID = 'data-id';
/** CSS class applied to the injected tagging panel wrapper div. */
const CLASS_PANEL_WRAPPER = 'lo-tag-panel mt-1';
/** data-* attribute on the course-level nudge banner. */
const ATTR_BANNER = 'data-lo-nudge-banner';

/**
 * Fetches learning outcomes for a course from the server.
 *
 * @param {number} courseid
 * @param {number} cmid
 * @returns {Promise<{outcomes: Array, isdecorative: boolean}>}
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
 * Returns the nudge state string: 'tagged' | 'decorative' | 'untagged'.
 *
 * @param {Array}   outcomes      Array of {tagged: bool} objects.
 * @param {boolean} isdecorative
 * @returns {string}
 */
const nudgeState = (outcomes, isdecorative) => {
    if (isdecorative) {
        return 'decorative';
    }
    if (outcomes.some(o => o.tagged)) {
        return 'tagged';
    }
    return 'untagged';
};

/**
 * Refreshes the summary element of a panel's <details> to reflect the
 * current nudge state.
 *
 * @param {HTMLElement} panelWrapper
 * @param {string}      state         'tagged' | 'decorative' | 'untagged'
 * @param {Array}       outcomes
 * @param {Object}      strings       Map of string key → localised text.
 */
const refreshSummary = (panelWrapper, state, outcomes, strings) => {
    const details = panelWrapper.querySelector('details.lo-tag-details');
    if (!details) {
        return;
    }
    const summary = details.querySelector('summary');
    if (!summary) {
        return;
    }

    // Remove existing state classes.
    details.classList.remove('lo-state-untagged', 'lo-state-tagged', 'lo-state-decorative');

    if (state === 'tagged') {
        const taggedNames = outcomes
            .filter(o => o.tagged)
            .map(o => o.shortname)
            .join(', ');
        summary.innerHTML =
            `<span class="lo-nudge-icon text-success me-1" aria-hidden="true">&#10003;</span>` +
            `<span class="fw-semibold">${strings.tagactivity}</span>` +
            `<span class="text-muted ms-1 small">(${taggedNames})</span>`;
        details.classList.add('lo-state-tagged');
    } else if (state === 'decorative') {
        summary.innerHTML =
            `<span class="lo-nudge-icon text-muted me-1" aria-hidden="true">&#8212;</span>` +
            `<span class="fw-semibold text-muted">${strings.tagactivity}</span>` +
            `<span class="badge bg-secondary ms-1 small">${strings.decorativebadge}</span>`;
        details.classList.add('lo-state-decorative');
    } else {
        // untagged — amber nudge.
        summary.innerHTML =
            `<span class="lo-nudge-icon text-warning me-1" aria-hidden="true">&#9888;</span>` +
            `<span class="fw-semibold">${strings.tagactivity}</span>` +
            `<span class="text-warning ms-1 small">${strings.addoutcome}</span>`;
        details.classList.add('lo-state-untagged');
        // Auto-open for visibility on first load.
        details.open = true;
    }
};

/**
 * Refreshes the course-level nudge banner.
 *
 * Counts `[data-lo-state="untagged"]` panels and updates (or removes) the
 * banner accordingly.
 *
 * @param {string} nudgeTemplate  Template string with {count}, {activities}, and {url} placeholders.
 * @param {string} reportUrl
 * @param {Object} strings        Loaded lang strings (must include nudgeactivity / nudgeactivities).
 */
const refreshBanner = (nudgeTemplate, reportUrl, strings) => {
    const untaggedCount = document.querySelectorAll('[data-lo-state="untagged"]').length;
    let banner = document.querySelector(`[${ATTR_BANNER}]`);

    if (untaggedCount === 0) {
        if (banner) {
            banner.remove();
        }
        return;
    }

    const activityWord = untaggedCount === 1 ? strings.nudgeactivity : strings.nudgeactivities;
    const text = nudgeTemplate
        .replace('{count}', untaggedCount)
        .replace('{activities}', activityWord)
        .replace('{url}', reportUrl);

    if (!banner) {
        banner = document.createElement('div');
        banner.setAttribute(ATTR_BANNER, '1');
        banner.className = 'alert alert-warning d-flex align-items-center gap-2 lo-nudge-banner mb-3';
        banner.setAttribute('role', 'alert');

        const courseContent = document.querySelector('.course-content, #region-main');
        if (courseContent) {
            courseContent.insertAdjacentElement('beforebegin', banner);
        }
    }

    banner.innerHTML = `<span aria-hidden="true">&#9888;</span><span>${text}</span>`;
};

/**
 * Builds and inserts the tagging panel for a single activity element.
 *
 * @param {HTMLElement} activityEl  The li[data-for="cmitem"] element.
 * @param {number}      courseid
 * @param {number}      cmid
 * @param {Object}      strings     Localised strings map.
 * @param {string}      reportUrl   URL of the alignment report.
 * @param {string}      nudgeTpl    Nudge banner template string.
 */
const initActivityPanel = async(activityEl, courseid, cmid, strings, reportUrl, nudgeTpl) => {
    let data;
    try {
        data = await fetchOutcomes(courseid, cmid);
    } catch (e) {
        Notification.exception(e);
        return;
    }

    const {outcomes, isdecorative} = data;
    const state = nudgeState(outcomes, isdecorative);

    const templateContext = {
        cmid,
        courseid,
        outcomes,
        isdecorative,
        taglabel: strings.tagactivity,
        savelabel: strings.savechanges,
        decorativelabel: strings.decorativeactivity,
        addoutcomelabel: strings.addoutcome,
    };

    const {html, js} = await Templates.renderForPromise(
        'local_learningoutcomes/tag_activity_panel',
        templateContext
    );

    // Insert the panel immediately after the activity card div.
    const activityCard = activityEl.querySelector('[data-region="activity-card"]') ?? activityEl;
    const panelWrapper = document.createElement('div');
    panelWrapper.className = CLASS_PANEL_WRAPPER;
    panelWrapper.dataset.loState = state;
    activityCard.insertAdjacentElement('afterend', panelWrapper);
    Templates.appendNodeContents(panelWrapper, html, js);

    // Apply initial summary styling.
    refreshSummary(panelWrapper, state, outcomes, strings);

    // Wire up save button.
    panelWrapper.addEventListener('click', async(e) => {
        const saveBtn = e.target.closest('[data-action="lo-save"]');
        if (!saveBtn) {
            return;
        }

        e.preventDefault();
        const panel = saveBtn.closest('.lo-tag-details');
        const checkedBoxes = [...panel.querySelectorAll('input[type="checkbox"][data-outcomeid]:checked')];
        const selectedIds = checkedBoxes.map(cb => parseInt(cb.dataset.outcomeid, 10));
        const decorativeCheck = panel.querySelector('[data-decorative]');
        const newIsDecorative = decorativeCheck?.checked ?? false;

        try {
            await saveOutcomes(courseid, cmid, selectedIds, newIsDecorative);

            // Update local state.
            const updatedOutcomes = outcomes.map(o => ({
                ...o,
                tagged: selectedIds.includes(o.id),
            }));
            const newState = nudgeState(updatedOutcomes, newIsDecorative);
            panelWrapper.dataset.loState = newState;
            refreshSummary(panelWrapper, newState, updatedOutcomes, strings);
            refreshBanner(nudgeTpl, reportUrl, strings);

            // Brief visual confirmation on the button.
            saveBtn.textContent = '✓';
            saveBtn.disabled = true;
            setTimeout(() => {
                saveBtn.textContent = strings.savechanges;
                saveBtn.disabled = false;
            }, 2000);
        } catch (err) {
            Notification.exception(err);
        }
    });

    return state;
};

/**
 * Initialises the tagging widget on course pages where the user has the
 * manage capability.
 *
 * @param {number} courseid   The current course ID.
 * @param {string} reportUrl  URL of the alignment-report page for this course.
 */
export const init = async(courseid, reportUrl) => {
    const activities = document.querySelectorAll(SELECTOR_ACTIVITY);
    if (!activities.length) {
        return;
    }

    // Load all strings up-front (one round-trip).
    const strKeys = [
        {key: 'tagactivity',        component: 'local_learningoutcomes'},
        {key: 'decorativeactivity', component: 'local_learningoutcomes'},
        {key: 'addoutcome',         component: 'local_learningoutcomes'},
        {key: 'nudge:untagged',     component: 'local_learningoutcomes'},
        {key: 'decorativebadge',    component: 'local_learningoutcomes'},
        {key: 'savechanges'},
        {key: 'nudge:activity',     component: 'local_learningoutcomes'},
        {key: 'nudge:activities',   component: 'local_learningoutcomes'},
    ];
    const strValues = await getStrings(strKeys);
    const strings = {
        tagactivity:        strValues[0],
        decorativeactivity: strValues[1],
        addoutcome:         strValues[2],
        nudgeuntagged:      strValues[3],
        decorativebadge:    strValues[4],
        savechanges:        strValues[5],
        nudgeactivity:      strValues[6],
        nudgeactivities:    strValues[7],
    };

    // Build a simple template for the nudge banner — {count} and {url} are
    // replaced at render time so the string can be authored in lang PHP.
    const nudgeTpl = strings.nudgeuntagged;

    // Initialise all panels in parallel.
    await Promise.all([...activities].map(el => {
        const cmid = parseInt(el.getAttribute(ATTR_CMID) ?? '0', 10);
        if (cmid > 0) {
            return initActivityPanel(el, courseid, cmid, strings, reportUrl, nudgeTpl);
        }
        return Promise.resolve();
    }));

    // Show course-level banner once all panels are ready.
    refreshBanner(nudgeTpl, reportUrl, strings);
};
