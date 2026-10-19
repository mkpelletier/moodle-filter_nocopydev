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

namespace filter_tripwire;

/**
 * Builds the Q&A forum honeypot config.
 *
 * Only Q&A forums are baited. A student there cannot read peers' answers
 * until they have posted their own, so a fictional title in a first answer
 * can only have come from the question. In every other forum type the first
 * student to take the bait publishes it to the rest of the class.
 *
 * @package    filter_tripwire
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_mitigations {
    /** Forum type that receives the citation honeypot. */
    public const BAITED_TYPE = 'qanda';

    /**
     * Whether this is a forum page (discussion, reply form, forum view).
     *
     * @param \moodle_page $page
     * @return bool
     */
    public static function is_forum_page(\moodle_page $page): bool {
        return str_starts_with((string) $page->pagetype, 'mod-forum-');
    }

    /**
     * Ids of the question posts (first post of each discussion) in a Q&A forum.
     *
     * Empty for any other forum type, or when the page is not a forum.
     *
     * @param \moodle_page $page
     * @return int[]
     */
    public static function question_post_ids(\moodle_page $page): array {
        global $DB;

        $cm = $page->cm;
        if (!$cm || $cm->modname !== 'forum') {
            return [];
        }
        if ($DB->get_field('forum', 'type', ['id' => $cm->instance]) !== self::BAITED_TYPE) {
            return [];
        }

        $ids = $DB->get_fieldset_select('forum_discussions', 'firstpost', 'forum = ?', [$cm->instance]);
        return array_values(array_map('intval', $ids));
    }

    /**
     * Config passed to filter_tripwire/forum_mitigations#init.
     *
     * Only restricted roles (Student by default) receive the bait, so staff
     * reading the thread never see or quote it. Switch role to Student to test.
     *
     * @param \moodle_page $page
     * @return array<string, mixed>
     */
    public static function js_config(\moodle_page $page): array {
        $honeypot = honeypot::from_context($page->context);
        $enabled = quiz_mitigations::config_enabled('enable_forum_honeypot')
            && $honeypot->is_configured()
            && access::lockdown_applies($page->context);

        return [
            'enabled' => $enabled,
            'instruction' => $enabled ? $honeypot->instruction_text() : '',
            'postids' => $enabled ? self::question_post_ids($page) : [],
        ];
    }

    /**
     * Load the AMD module with config, if there is anything to bait.
     *
     * Inline for the same reason as quiz_mitigations::require_amd(): the
     * config can exceed js_call_amd's 1024-character argument limit.
     *
     * @param \moodle_page $page
     */
    public static function require_amd(\moodle_page $page): void {
        $config = self::js_config($page);
        if (!$config['enabled'] || $config['postids'] === []) {
            return;
        }

        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        if ($json === false) {
            return;
        }
        $modname = 'filter_tripwire/forum_mitigations';
        $page->requires->js_amd_inline(
            "M.util.js_pending('{$modname}'); require(['{$modname}'], function(amd) {" .
            "amd.init({$json}); M.util.js_complete('{$modname}');});"
        );
    }
}
