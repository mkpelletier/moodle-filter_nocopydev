// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Q&A forum citation honeypot.
 *
 * The question post of each discussion carries the camouflaged citation.
 * Peer posts are left alone: a Q&A forum hides them until the student has
 * answered, so the bait can only come back from the question itself.
 *
 * @module     filter_nocopydev/forum_mitigations
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {injectHoneypot} from 'filter_nocopydev/camouflage';

/**
 * Initialise the forum honeypot.
 *
 * @param {Object} config
 */
export const init = (config) => {
    if (!config || !config.enabled || !config.instruction) {
        return;
    }
    const postids = config.postids || [];
    if (!postids.length) {
        return;
    }

    // Both the standard and nested-v2 post templates put the message in this id.
    const enhance = () => {
        postids.forEach((id) => {
            const host = document.getElementById('post-content-' + id);
            if (host) {
                injectHoneypot(host, config.instruction);
            }
        });
    };

    enhance();
    // Posts are re-rendered client-side after an in-page reply.
    const observer = new MutationObserver(enhance);
    observer.observe(document.body, {childList: true, subtree: true});
};
