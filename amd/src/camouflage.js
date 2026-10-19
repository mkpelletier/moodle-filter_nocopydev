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
 * Camouflaged citation honeypot shared by the quiz and forum mitigations.
 *
 * @module     filter_tripwire/camouflage
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const HONEYPOT_CLASS = 'filter-tripwire-honeypot';

/**
 * Same-colour-as-background paragraph at the end of the host.
 *
 * In-flow real text (not opacity 0) so page extractors keep it. Not
 * aria-hidden: Leo drops those nodes. Colour is sampled from the host
 * background (fallback #fff). Faculty should know screen readers will speak it.
 *
 * @param {Element} host
 * @param {string} instruction
 */
export const injectHoneypot = (host, instruction) => {
    if (!instruction || host.querySelector('.' + HONEYPOT_CLASS)) {
        return;
    }

    host.appendChild(document.createComment(' ' + instruction + ' '));

    const camouflage = matchingBackground(host);
    const para = document.createElement('p');
    para.className = HONEYPOT_CLASS;
    para.textContent = instruction;
    para.style.setProperty('--filter-tripwire-camouflage', camouflage);
    para.style.color = camouflage;
    host.appendChild(para);
};

/**
 * First opaque background in the ancestor chain, else white.
 *
 * @param {Element} el
 * @returns {string}
 */
const matchingBackground = (el) => {
    let node = el;
    while (node && node !== document.documentElement) {
        const rgb = parseRgb(window.getComputedStyle(node).backgroundColor);
        if (rgb && rgb.a > 0) {
            return 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')';
        }
        node = node.parentElement;
    }
    return '#fff';
};

/**
 * @param {string} value
 * @returns {?Object}
 */
export const parseRgb = (value) => {
    const match = value && value.match(
        /rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+))?\s*\)/i
    );
    if (!match) {
        return null;
    }
    return {
        r: Number(match[1]),
        g: Number(match[2]),
        b: Number(match[3]),
        a: match[4] === undefined ? 1 : Number(match[4]),
    };
};
