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
 * Filter that injects JavaScript to prevent copy/paste and developer tools access.
 *
 * This filter uses setup() to inject a page-wide AMD lockdown script once per page.
 * The filter() method injects a <noscript> overlay to block access when JS is disabled.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {

    /** @var bool Whether the noscript block has been injected on this page. */
    private static bool $noscriptinjected = false;

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
        if (!isset($activefilters['nocopydev'])) {
            return;
        }

        if (!$page->requires->should_create_one_time_item_now('filter_nocopydev-lockdown')) {
            return;
        }

        $page->requires->js_call_amd('filter_nocopydev/lockdown', 'init');
    }

    #[\Override]
    public function filter($text, array $options = []) {
        // Inject the noscript overlay once per page on the first filtered text block.
        if (!self::$noscriptinjected) {
            self::$noscriptinjected = true;
            $warning = get_string('noscriptwarning', 'filter_nocopydev');
            $noscript = '<noscript><div style="position:fixed;top:0;left:0;width:100%;height:100%;'
                . 'background:#fff;z-index:999999;display:flex;align-items:center;justify-content:center;'
                . 'font-size:1.5rem;padding:2rem;text-align:center;">'
                . s($warning)
                . '</div></noscript>';
            return $noscript . $text;
        }
        return $text;
    }
}
