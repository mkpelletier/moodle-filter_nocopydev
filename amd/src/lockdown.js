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
 * Lockdown module that prevents copy/paste, right-click, and common dev tools shortcuts.
 *
 * Copy/paste and context menu are blocked everywhere including inside TinyMCE/Atto editors.
 * Editors remain functional for typing and formatting (bold, italic, undo, etc).
 *
 * @module     filter_nocopydev/lockdown
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const BLOCKED_CTRL_KEYS = {
    'c': true, // Copy.
    'x': true, // Cut.
    'v': true, // Paste.
    'a': true, // Select all.
    's': true, // Save page.
    'u': true, // View source.
};

const BLOCKED_SHIFT_KEYS = {
    'i': true, // Dev tools.
    'j': true, // Console.
    'c': true, // Element inspector.
};

/**
 * Handle keydown events and block restricted shortcuts.
 *
 * @param {KeyboardEvent} e
 */
const handleKeydown = (e) => {
    const ctrl = e.ctrlKey || e.metaKey;

    if (e.key === 'F12') {
        e.preventDefault();
        return;
    }

    if (!ctrl) {
        return;
    }

    const key = e.key.toLowerCase();

    if (e.shiftKey && BLOCKED_SHIFT_KEYS[key]) {
        e.preventDefault();
        return;
    }

    if (BLOCKED_CTRL_KEYS[key]) {
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
 * Lock down a single TinyMCE editor instance.
 *
 * @param {object} editor A TinyMCE editor instance.
 */
const lockdownEditor = (editor) => {
    editor.on('paste copy cut contextmenu', (e) => {
        e.preventDefault();
    });
    editor.on('keydown', (e) => {
        const ctrl = e.ctrlKey || e.metaKey;
        if (e.key === 'F12') {
            e.preventDefault();
            return;
        }
        if (!ctrl) {
            return;
        }
        const key = e.key.toLowerCase();
        if (e.shiftKey && BLOCKED_SHIFT_KEYS[key]) {
            e.preventDefault();
            return;
        }
        if (BLOCKED_CTRL_KEYS[key]) {
            e.preventDefault();
        }
    });
};

/**
 * Find and lock down all current and future TinyMCE editors.
 */
const lockdownTinyMCE = () => {
    if (typeof window.tinymce === 'undefined') {
        return;
    }

    // Lock down any editors that already exist.
    window.tinymce.get().forEach(lockdownEditor);

    // Lock down editors that get created later.
    window.tinymce.on('AddEditor', (e) => {
        e.editor.on('init', () => {
            lockdownEditor(e.editor);
        });
    });
};

/**
 * Initialise the lockdown module.
 */
export const init = () => {
    document.addEventListener('keydown', handleKeydown, true);
    document.addEventListener('copy', blockEvent, true);
    document.addEventListener('cut', blockEvent, true);
    document.addEventListener('paste', blockEvent, true);
    document.addEventListener('contextmenu', blockEvent, true);

    // Disable text selection globally, allow it in editors for formatting.
    const style = document.createElement('style');
    style.textContent = `
        body {
            -webkit-user-select: none;
            user-select: none;
        }
        [contenteditable="true"],
        .tox, .tox-tinymce, .tox *,
        .editor_atto, .editor_atto * {
            -webkit-user-select: auto !important;
            user-select: auto !important;
        }
    `;
    document.head.appendChild(style);

    // Lock down TinyMCE - may not be loaded yet, so also watch for it.
    lockdownTinyMCE();
    if (typeof window.tinymce === 'undefined') {
        const observer = new MutationObserver(() => {
            if (typeof window.tinymce !== 'undefined') {
                observer.disconnect();
                lockdownTinyMCE();
            }
        });
        observer.observe(document.body, {childList: true, subtree: true});
    }
};
