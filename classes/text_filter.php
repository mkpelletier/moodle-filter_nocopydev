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
 * Filter that injects JavaScript to prevent copy/paste and developer tools access.
 *
 * This filter uses setup() to inject a page-wide AMD lockdown script once per page,
 * quiz-attempt mitigations (citation honeypot, forensic watermark) on attempt pages,
 * and the citation honeypot on Q&A forum questions.
 * The filter() method injects a <noscript> overlay to block access when JS is disabled,
 * and highlights the fictional honeypot title for markers.
 *
 * @package    filter_tripwire
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /** @var bool Whether the noscript block has been injected on this page. */
    private static bool $noscriptinjected = false;

    /**
     * Reset per-page noscript state (used by unit tests).
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$noscriptinjected = false;
    }

    #[\Override]
    public function setup($page, $context) {
        // Gate activation on the page's primary context, not the $context passed in.
        // setup() runs once per filter_manager, and a manager is created per context —
        // so when format_text() runs for inline module content (e.g. format_simple
        // rendering mod_page bodies on the course view), setup() is called with that
        // module context. If the filter is locally overridden ON anywhere in that
        // module's context tree, this would inject the page-global lockdown JS even
        // though the filter is off for the page itself. The lockdown is page-scoped,
        // so it must reflect the page context's effective state.
        $activefilters = filter_get_active_in_context($page->context);
        if (!isset($activefilters['tripwire'])) {
            return;
        }

        if (
            access::should_lockdown($page)
            && $page->requires->should_create_one_time_item_now('filter_tripwire-lockdown')
        ) {
            $options = lockdown_options::for_page($page);
            if (lockdown_options::any_blocked($options)) {
                $page->requires->js_call_amd('filter_tripwire/lockdown', 'init', [$options]);
            }
        }

        if (
            quiz_mitigations::is_attempt_page($page)
            && $page->requires->should_create_one_time_item_now('filter_tripwire-quiz-mitigations')
        ) {
            quiz_mitigations::require_amd($page);
        }

        if (
            forum_mitigations::is_forum_page($page)
            && $page->requires->should_create_one_time_item_now('filter_tripwire-forum-mitigations')
        ) {
            forum_mitigations::require_amd($page);
        }
    }

    #[\Override]
    public function filter($text, array $options = []) {
        $text = $this->highlight_honeypot_hits($text);

        // Inject the noscript overlay once per page on the first filtered text block.
        // Skip for roles that are not copy-restricted, except quiz attempt/preview.
        if (!self::$noscriptinjected) {
            self::$noscriptinjected = true;
            global $PAGE;
            if (!($PAGE instanceof \moodle_page) || !access::should_lockdown($PAGE)) {
                return $text;
            }
            $warning = get_string('noscriptwarning', 'filter_tripwire');
            $noscript = '<noscript><div style="position:fixed;top:0;left:0;width:100%;height:100%;'
                . 'background:#fff;z-index:999999;display:flex;align-items:center;justify-content:center;'
                . 'font-size:1.5rem;padding:2rem;text-align:center;">'
                . s($warning)
                . '</div></noscript>';
            return $noscript . $text;
        }
        return $text;
    }

    /**
     * Mark the fictional honeypot title wherever a marker reads student work.
     *
     * The honeypot itself is added in the browser, so the title only reaches
     * this text when someone has written it into a response.
     *
     * @param string $text
     * @return string
     */
    private function highlight_honeypot_hits($text) {
        if (!is_string($text) || $text === '') {
            return $text;
        }
        $honeypot = honeypot::from_context($this->context);
        if (!$honeypot->is_configured() || !has_capability('filter/tripwire:viewhoneypothits', $this->context)) {
            return $text;
        }
        return $honeypot->highlight_in($text);
    }
}
