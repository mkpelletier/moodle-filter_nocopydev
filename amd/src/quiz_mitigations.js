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
 * Per-qtype quiz-attempt mitigations.
 *
 * Essay: white-on-white citation honeypot plus a pixel overlay that carries
 * the short citation, so a snip may still be echoed by a vision model.
 *
 * Selected response (MCQ, true/false, matching): forensic watermark and
 * print blocking only. A canary cannot appear in a click-to-answer submission.
 *
 * @module     filter_nocopydev/quiz_mitigations
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const ENHANCED_ATTR = 'data-nocopydev-mitigated';
const CONFIG_ID = 'filter-nocopydev-quiz-config';

const DEFAULT_POLICY = {
    dom_honeypot: false,
    pixel_honeypot: false,
    forensic_watermark: true,
    block_print: true,
    cover: 'formulation',
};

/**
 * Initialise quiz-attempt mitigations.
 *
 * Config is read from a JSON script tag so we stay under Moodle's 1024-character
 * js_call_amd limit. An explicit object is still accepted for tests.
 *
 * @param {Object} [config]
 */
export const init = (config) => {
    if (!config) {
        config = readPageConfig();
    }
    if (!config || !config.enabled) {
        return;
    }

    document.body.classList.add('filter-nocopydev-quizattempt');

    if (shouldBlockPrint(config)) {
        blockPrint();
    }

    enhanceAll(config);
    observeQuestions(config);
};

/**
 * @returns {?Object}
 */
const readPageConfig = () => {
    const node = document.getElementById(CONFIG_ID);
    if (!node || !node.textContent) {
        return null;
    }
    try {
        return JSON.parse(node.textContent);
    } catch (e) {
        return null;
    }
};

/**
 * @param {Object} config
 * @returns {boolean}
 */
const shouldBlockPrint = (config) => {
    const policies = config.policies || {};
    return Object.values(policies).some((policy) => policy && policy.block_print);
};

/**
 * Block Ctrl/Cmd+P. CSS hides the attempt under @media print as a backstop.
 */
const blockPrint = () => {
    document.addEventListener('keydown', (e) => {
        const ctrl = e.ctrlKey || e.metaKey;
        if (ctrl && e.key.toLowerCase() === 'p') {
            e.preventDefault();
        }
    }, true);
};

/**
 * @param {Object} config
 */
const observeQuestions = (config) => {
    const observer = new MutationObserver(() => {
        enhanceAll(config);
    });
    observer.observe(document.body, {childList: true, subtree: true});
};

/**
 * @param {Object} config
 */
const enhanceAll = (config) => {
    document.querySelectorAll('.que').forEach((que) => {
        if (que.getAttribute(ENHANCED_ATTR)) {
            return;
        }
        que.setAttribute(ENHANCED_ATTR, '1');
        applyMitigations(que, config);
    });
};

/**
 * @param {Element} que
 * @param {Object} config
 * @returns {Object}
 */
const policyFor = (que, config) => {
    const policies = config.policies || {};
    for (const cls of que.classList) {
        if (Object.prototype.hasOwnProperty.call(policies, cls)) {
            return policies[cls];
        }
    }
    return policies.default || DEFAULT_POLICY;
};

/**
 * @param {Element} que
 * @param {Object} config
 */
const applyMitigations = (que, config) => {
    const policy = policyFor(que, config);
    const cover = resolveCover(que, policy.cover);

    if (config.domhoneypot && policy.dom_honeypot) {
        injectDomHoneypot(que, config);
    }

    if (config.forensicwatermark && (policy.forensic_watermark || policy.pixel_honeypot)) {
        injectWatermark(cover, watermarkText(policy, config));
    }
};

/**
 * @param {Element} que
 * @param {string} cover
 * @returns {Element}
 */
const resolveCover = (que, cover) => {
    if (cover === 'qtext') {
        return que.querySelector('.qtext') || que.querySelector('.formulation') || que;
    }
    return que.querySelector('.formulation') || que;
};

/**
 * Same-colour-as-background paragraph at the end of the stem.
 *
 * In-flow real text (not opacity 0) so page extractors keep it. Not
 * aria-hidden: Leo drops those nodes. Colour is sampled from the question
 * background (fallback #fff). Faculty should know screen readers will speak it.
 *
 * @param {Element} que
 * @param {Object} config
 */
const injectDomHoneypot = (que, config) => {
    const instruction = config.honeypot && config.honeypot.instruction;
    if (!instruction) {
        return;
    }

    const host = que.querySelector('.qtext') || que.querySelector('.formulation') || que;
    if (host.querySelector('.filter-nocopydev-honeypot')) {
        return;
    }

    host.appendChild(document.createComment(' ' + instruction + ' '));

    const camouflage = matchingBackground(host);
    const para = document.createElement('p');
    para.className = 'filter-nocopydev-honeypot';
    para.textContent = instruction;
    para.style.setProperty('--filter-nocopydev-camouflage', camouflage);
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
 * @param {Object} policy
 * @param {Object} config
 * @returns {string}
 */
const watermarkText = (policy, config) => {
    const token = config.token || '';
    const parts = [];
    if (policy.pixel_honeypot && config.honeypot && config.honeypot.shortlabel) {
        parts.push(config.honeypot.shortlabel);
    } else if (config.watermarklabel) {
        parts.push(config.watermarklabel);
    }
    if (token) {
        parts.push(token);
    }
    return parts.join(' · ');
};

/**
 * @param {Element} cover
 * @param {string} text
 */
const injectWatermark = (cover, text) => {
    if (!text || cover.querySelector(':scope > .filter-nocopydev-watermark')) {
        return;
    }

    const computed = window.getComputedStyle(cover);
    if (computed.position === 'static') {
        cover.style.position = 'relative';
    }

    const overlay = document.createElement('div');
    overlay.className = 'filter-nocopydev-watermark';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.style.backgroundImage = svgTiling(text, inkColor(cover));
    cover.insertBefore(overlay, cover.firstChild);
};

/**
 * @param {Element} el
 * @returns {string}
 */
const inkColor = (el) => {
    const own = parseRgb(window.getComputedStyle(el).backgroundColor);
    if (own && own.a > 0) {
        return luminance(own) < 0.4 ? 'rgba(255,255,255,0.55)' : 'rgba(0,0,0,0.55)';
    }
    const body = parseRgb(window.getComputedStyle(document.body).backgroundColor);
    if (body && luminance(body) < 0.4) {
        return 'rgba(255,255,255,0.55)';
    }
    return 'rgba(0,0,0,0.55)';
};

/**
 * @param {string} value
 * @returns {?Object}
 */
const parseRgb = (value) => {
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

/**
 * @param {{r: number, g: number, b: number}} rgb
 * @returns {number}
 */
const luminance = (rgb) => (0.2126 * rgb.r + 0.7152 * rgb.g + 0.0722 * rgb.b) / 255;

/**
 * @param {string} text
 * @param {string} fill
 * @returns {string}
 */
const svgTiling = (text, fill) => {
    const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="420" height="140">' +
        '<text x="10" y="80" fill="' + fill + '" font-size="13" font-family="Georgia, serif" ' +
        'transform="rotate(-18 10 80)">' + escapeXml(text) + '</text></svg>';
    return 'url("data:image/svg+xml,' + encodeURIComponent(svg) + '")';
};

/**
 * @param {string} value
 * @returns {string}
 */
const escapeXml = (value) => value.replace(/[&<>"']/g, (ch) => {
    if (ch === '&') {
        return '&amp;';
    }
    if (ch === '<') {
        return '&lt;';
    }
    if (ch === '>') {
        return '&gt;';
    }
    if (ch === '"') {
        return '&quot;';
    }
    return '&apos;';
});
