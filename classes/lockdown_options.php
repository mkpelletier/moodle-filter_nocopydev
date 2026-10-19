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
 * Which lockdown actions are blocked in a context.
 *
 * Each switch resolves from the most specific filter context that set it:
 * activity → course → category → site setting. All default to blocked.
 * A context stores a switch only when it differs from its parent, so a
 * later change on the course still reaches activities that never chose.
 *
 * @package    filter_tripwire
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lockdown_options {
    /** Config keys, shared by site settings and local filter config. */
    public const OPTIONS = [
        'block_copy',
        'block_cut',
        'block_paste',
        'block_selection',
        'block_contextmenu',
        'block_devtools',
    ];

    /** @var array<int, array<string, bool>> Request-lifetime cache keyed by context id. */
    private static array $resolved = [];

    /**
     * Drop the per-request resolution cache (used by unit tests).
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$resolved = [];
    }

    /**
     * Site defaults. An unset setting means blocked.
     *
     * @return array<string, bool>
     */
    public static function site_defaults(): array {
        $values = [];
        foreach (self::OPTIONS as $option) {
            $raw = get_config('filter_tripwire', $option);
            $values[$option] = $raw === false || $raw === '' || (int) $raw === 1;
        }
        return $values;
    }

    /**
     * Effective switches for a context, walking parents then site defaults.
     *
     * @param \context $context
     * @return array<string, bool>
     */
    public static function for_context(\context $context): array {
        if (isset(self::$resolved[$context->id])) {
            return self::$resolved[$context->id];
        }

        $parent = $context->get_parent_context();
        $values = $parent ? self::for_context($parent) : self::site_defaults();
        $local = filter_get_local_config(honeypot::FILTERNAME, $context->id);
        foreach (self::OPTIONS as $option) {
            if (isset($local[$option]) && $local[$option] !== '') {
                $values[$option] = (int) $local[$option] === 1;
            }
        }

        self::$resolved[$context->id] = $values;
        return $values;
    }

    /**
     * What this context would get if it set nothing itself.
     *
     * @param \context $context
     * @return array<string, bool>
     */
    public static function from_parent_context(\context $context): array {
        $parent = $context->get_parent_context();
        return $parent ? self::for_context($parent) : self::site_defaults();
    }

    /**
     * Save the switches chosen on a local settings form.
     *
     * A switch equal to the inherited value is unset rather than stored.
     *
     * @param \context $context
     * @param array $data Submitted form values keyed by option.
     * @return void
     */
    public static function save_local(\context $context, array $data): void {
        $inherited = self::from_parent_context($context);
        foreach (self::OPTIONS as $option) {
            $blocked = !empty($data[$option]);
            if ($blocked === $inherited[$option]) {
                filter_unset_local_config(honeypot::FILTERNAME, $context->id, $option);
            } else {
                filter_set_local_config(honeypot::FILTERNAME, $context->id, $option, $blocked ? '1' : '0');
            }
        }
        self::reset_caches();
    }

    /**
     * Whether anything at all is blocked.
     *
     * @param bool[] $values Switches keyed by option.
     * @return bool
     */
    public static function any_blocked(array $values): bool {
        return in_array(true, $values, true);
    }

    /**
     * Options for filter_tripwire/lockdown#init.
     *
     * Paste is always allowed on forum pages: replies are often drafted
     * elsewhere, and a pasted reply brings the honeypot back verbatim.
     *
     * @param \moodle_page $page
     * @return array<string, bool>
     */
    public static function for_page(\moodle_page $page): array {
        $values = self::for_context($page->context);
        if (forum_mitigations::is_forum_page($page)) {
            $values['block_paste'] = false;
        }
        return $values;
    }
}
