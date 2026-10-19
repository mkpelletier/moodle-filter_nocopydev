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
 * Lockdown module that blocks copy, cut, paste, selection, the context menu,
 * and common dev tools shortcuts.
 *
 * Each block is switched on or off per context (activity → course → category
 * → site), and applies inside TinyMCE/Atto editors too. Editors remain
 * functional for typing and formatting (bold, italic, undo, etc).
 *
 * @module     filter_nocopydev/lockdown
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable camelcase -- option keys are the PHP config names. */

/**
 * Ctrl/Cmd + key shortcuts and the option that blocks each.
 */
const CTRL_KEY_OPTION = {
    'c': 'block_copy',
    'x': 'block_cut',
    'v': 'block_paste',
    'a': 'block_selection', // Select all.
    's': 'block_devtools', // Save page.
    'u': 'block_devtools', // View source.
};

const DEVTOOLS_SHIFT_KEYS = {
    'i': true, // Dev tools.
    'j': true, // Console.
    'c': true, // Element inspector.
};

/** Clipboard events and the option that blocks each. */
const EVENT_OPTION = {
    'copy': 'block_copy',
    'cut': 'block_cut',
    'paste': 'block_paste',
    'contextmenu': 'block_contextmenu',
};

/** TinyMCE clipboard controls and the option that hides each. */
const TINY_UI_OPTION = {
    'copy': 'block_copy',
    'cut': 'block_cut',
    'paste': 'block_paste',
    'pastetext': 'block_paste',
};

/** @var {Object} Effective switches for this page, set by init(). */
let options = {};

/**
 * @param {Object} map Name → option key.
 * @returns {string[]} Names whose option is on.
 */
const blockedNames = (map) => Object.keys(map).filter((name) => options[map[name]]);

/**
 * Whether a keydown is a blocked shortcut.
 *
 * @param {KeyboardEvent} e
 * @returns {boolean}
 */
const isBlockedKey = (e) => {
    if (e.key === 'F12') {
        return !!options.block_devtools;
    }
    if (!(e.ctrlKey || e.metaKey)) {
        return false;
    }
    const key = e.key.toLowerCase();
    if (e.shiftKey && DEVTOOLS_SHIFT_KEYS[key]) {
        return !!options.block_devtools;
    }
    return !!options[CTRL_KEY_OPTION[key]];
};

/**
 * @param {KeyboardEvent} e
 */
const handleKeydown = (e) => {
    if (isBlockedKey(e)) {
        e.preventDefault();
    }
};

/**
 * Prevent default on clipboard and context menu events.
 *
 * @param {Event} e
 */
const blockEvent = (e) => {
    e.preventDefault();
};

/**
 * Drop blocked clipboard tokens from a TinyMCE menu items string.
 *
 * @param {string} items
 * @returns {string}
 */
const stripClipboardMenuItems = (items) => {
    const names = blockedNames(TINY_UI_OPTION);
    if (typeof items !== 'string' || !names.length) {
        return items;
    }
    return items
        .replace(new RegExp('\\b(' + names.join('|') + ')\\b', 'gi'), '')
        .replace(/\s+/g, ' ')
        .replace(/\|\s*\|/g, '|')
        .replace(/^\s*\||\|\s*$/g, '')
        .trim();
};

/**
 * Ensure TinyMCE never puts blocked clipboard actions in the Edit menu.
 * Moodle builds that menu at init from removed_menuitems + menu.edit.items.
 *
 * @param {object|object[]} settings
 * @returns {object|object[]}
 */
const patchTinySettings = (settings) => {
    if (!settings || typeof settings !== 'object') {
        return settings;
    }
    if (Array.isArray(settings)) {
        return settings.map(patchTinySettings);
    }
    const names = blockedNames(TINY_UI_OPTION);
    if (!names.length) {
        return settings;
    }
    const patched = Object.assign({}, settings);
    const removedMenuItemsKey = 'removed_menuitems';
    const existing = patched[removedMenuItemsKey] ? String(patched[removedMenuItemsKey]) : '';
    patched[removedMenuItemsKey] = (existing + ' ' + names.join(' ')).trim();
    if (patched.menu && patched.menu.edit && patched.menu.edit.items) {
        patched.menu = Object.assign({}, patched.menu, {
            edit: Object.assign({}, patched.menu.edit, {
                items: stripClipboardMenuItems(patched.menu.edit.items),
            }),
        });
    }
    return patched;
};

/**
 * Hide any blocked clipboard controls that slipped into the rendered chrome.
 *
 * @param {ParentNode} root
 */
const hideTinyClipboardNodes = (root) => {
    if (!root || !root.querySelectorAll) {
        return;
    }
    const names = blockedNames(TINY_UI_OPTION);
    root.querySelectorAll('[data-mce-name]').forEach((el) => {
        const name = (el.getAttribute('data-mce-name') || '').toLowerCase();
        if (names.indexOf(name) !== -1) {
            el.hidden = true;
            el.setAttribute('aria-hidden', 'true');
            el.style.display = 'none';
        }
    });
};

/**
 * Lock down a single TinyMCE editor instance.
 *
 * @param {object} editor A TinyMCE editor instance.
 */
const lockdownEditor = (editor) => {
    if (editor.__nocopydevLocked) {
        return;
    }
    editor.__nocopydevLocked = true;

    const events = blockedNames(EVENT_OPTION);
    if (events.length) {
        editor.on(events.join(' '), (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    }
    editor.on('keydown', handleKeydown);
    const hide = () => {
        hideTinyClipboardNodes(editor.getContainer());
        hideTinyClipboardNodes(document.body);
    };
    if (editor.initialized) {
        hide();
    } else {
        editor.on('init', hide);
    }
};

/**
 * Wrap tinymce.init so Moodle's editor is created without blocked clipboard menu items.
 *
 * @returns {boolean} True if TinyMCE is present and patched.
 */
const installTinyPatch = () => {
    if (typeof window.tinymce === 'undefined') {
        return false;
    }
    if (window.tinymce.__nocopydevPatched) {
        return true;
    }
    window.tinymce.__nocopydevPatched = true;

    const originalInit = window.tinymce.init.bind(window.tinymce);
    window.tinymce.init = (settings) => originalInit(patchTinySettings(settings));

    window.tinymce.on('AddEditor', (e) => {
        lockdownEditor(e.editor);
    });
    window.tinymce.get().forEach(lockdownEditor);
    return true;
};

/**
 * Find and lock down all current and future TinyMCE editors.
 */
const lockdownTinyMCE = () => {
    if (installTinyPatch()) {
        return;
    }
    const started = Date.now();
    const timer = window.setInterval(() => {
        if (installTinyPatch() || Date.now() - started > 20000) {
            window.clearInterval(timer);
        }
    }, 20);
};

/**
 * Page CSS for the switches that are on.
 *
 * @returns {string}
 */
const lockdownCss = () => {
    let css = '';
    if (options.block_selection) {
        // Disable text selection globally; keep form controls and editors typeable.
        css += `
        body {
            -webkit-user-select: none;
            user-select: none;
        }
        input, textarea, select, button,
        [contenteditable="true"],
        .tox, .tox-tinymce, .tox *,
        .editor_atto, .editor_atto * {
            -webkit-user-select: text !important;
            user-select: text !important;
        }`;
    }
    const selectors = ['.tox', '.tox-tinymce-aux', '.tox-silver-sink']
        .flatMap((root) => blockedNames(TINY_UI_OPTION).map((name) => root + ' [data-mce-name="' + name + '"]'));
    if (selectors.length) {
        css += `
        ${selectors.join(',\n        ')} {
            display: none !important;
        }`;
    }
    return css;
};

/**
 * Initialise the lockdown module.
 *
 * @param {Object} config Switches keyed by option (block_copy, block_cut,
 *     block_paste, block_selection, block_contextmenu, block_devtools).
 */
export const init = (config) => {
    options = config || {};

    document.addEventListener('keydown', handleKeydown, true);
    blockedNames(EVENT_OPTION).forEach((type) => {
        document.addEventListener(type, blockEvent, true);
    });

    const css = lockdownCss();
    if (css) {
        const style = document.createElement('style');
        style.textContent = css;
        document.head.appendChild(style);
    }

    lockdownTinyMCE();
    const observer = new MutationObserver((mutations) => {
        installTinyPatch();
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    hideTinyClipboardNodes(node);
                }
            });
        });
    });
    observer.observe(document.documentElement, {childList: true, subtree: true});
};
