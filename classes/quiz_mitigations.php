<?php
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

namespace filter_nocopydev;

/**
 * Builds the quiz-attempt AMD config and the per-attempt forensic token.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_mitigations {

    /**
     * Whether this page should receive quiz-specific mitigations.
     *
     * Review pages are excluded so students cannot inspect the honeypot after
     * the sitting and reuse it on the next attempt.
     *
     * @param \moodle_page $page
     * @return bool
     */
    public static function is_attempt_page(\moodle_page $page): bool {
        return $page->pagetype === 'mod-quiz-attempt';
    }

    /**
     * Config passed to filter_nocopydev/quiz_mitigations#init.
     *
     * @param \moodle_page $page
     * @return array<string, mixed>
     */
    public static function js_config(\moodle_page $page): array {
        global $USER;

        $honeypot = honeypot::from_context($page->context);
        $attemptid = (int) $page->url->get_param('attempt');
        $token = self::forensic_token($attemptid, (int) $USER->id, (int) $page->context->id);

        $label = trim((string) get_config('filter_nocopydev', 'watermark_label'));
        if ($label === '') {
            $label = get_string('watermark_label_default', 'filter_nocopydev');
        }

        return [
            'enabled' => self::config_enabled('enable_quiz_mitigations'),
            'token' => $token,
            'watermarklabel' => $label,
            'domhoneypot' => self::config_enabled('enable_dom_honeypot') && $honeypot->is_configured(),
            'forensicwatermark' => self::config_enabled('enable_forensic_watermark'),
            'honeypot' => $honeypot->for_js(),
            'policies' => qtype_policy::for_js(),
        ];
    }

    /**
     * Load the AMD module with config.
     *
     * js_call_amd forbids argument strings over 1024 characters (Whoops turns
     * that developer warning into a fatal). moodle_page::add_header_html() does
     * not exist on Moodle 5.0/5.3. js_amd_inline has neither problem.
     *
     * @param \moodle_page $page
     */
    public static function require_amd(\moodle_page $page): void {
        $json = json_encode(
            self::js_config($page),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        );
        if ($json === false) {
            return;
        }
        $modname = 'filter_nocopydev/quiz_mitigations';
        $page->requires->js_amd_inline(
            "M.util.js_pending('{$modname}'); require(['{$modname}'], function(amd) {" .
            "amd.init({$json}); M.util.js_complete('{$modname}');});"
        );
    }

    /**
     * Short HMAC unique to this user/attempt, not reversible in the browser.
     *
     * @param int $attemptid
     * @param int $userid
     * @param int $contextid
     * @return string
     */
    public static function forensic_token(int $attemptid, int $userid, int $contextid): string {
        global $CFG;

        $material = $attemptid . ':' . $userid . ':' . $contextid;
        // passwordsaltmain is not set on new Moodle 5.3 installs (Whoops treats
        // the undefined $CFG property as an error). Fall back to the site id.
        $secret = !empty($CFG->passwordsaltmain) ? $CFG->passwordsaltmain : get_site_identifier();
        return substr(hash_hmac('sha256', $material, $secret), 0, 8);
    }

    /**
     * Moodle checkbox configs are stored as '0'/'1'; unset means use $default.
     *
     * @param string $name
     * @param bool $default
     * @return bool
     */
    private static function config_enabled(string $name, bool $default = true): bool {
        $raw = get_config('filter_nocopydev', $name);
        if ($raw === false || $raw === '') {
            return $default;
        }
        return (int) $raw === 1;
    }
}
