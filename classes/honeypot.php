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
 * Builds the constructed-response citation honeypot.
 *
 * The payload is a real author paired with a fictional work. A student who
 * pastes the question into an AI will often receive a fluent citation that
 * looks scholarly; a marker who knows the field will recognise it immediately.
 *
 * Values resolve from the most specific filter context that set them:
 * quiz (module) → course → category → site defaults. Blank local fields
 * inherit, so a Pauline course can plant a Wright title without affecting
 * systematics.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class honeypot {
    /** Filter name as stored in filter_config (no filter_ prefix). */
    public const FILTERNAME = 'nocopydev';

    /** Local/site config keys that make up the citation. */
    public const FIELDS = [
        'honeypot_author',
        'honeypot_title',
        'honeypot_venue',
        'honeypot_claim',
    ];

    /** @var array<int, self> Request-lifetime cache keyed by context id. */
    private static array $resolved = [];

    /**
     * Drop the per-request resolution cache (used by unit tests).
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$resolved = [];
    }

    /** @var string */
    public readonly string $author;

    /** @var string */
    public readonly string $title;

    /** @var string */
    public readonly string $venue;

    /** @var string */
    public readonly string $claim;

    /**
     * Site-wide fallback from plugin settings. Empty until an admin fills them.
     *
     * @return self
     */
    public static function from_site_config(): self {
        return new self(
            (string) get_config('filter_nocopydev', 'honeypot_author'),
            (string) get_config('filter_nocopydev', 'honeypot_title'),
            (string) get_config('filter_nocopydev', 'honeypot_venue'),
            (string) get_config('filter_nocopydev', 'honeypot_claim'),
        );
    }

    /**
     * Effective honeypot for a context, walking parents then site defaults.
     *
     * Moodle's built-in filter localconfig is module-only and does not inherit
     * from the course, so this walk is required.
     *
     * @param \context $context
     * @return self
     */
    public static function from_context(\context $context): self {
        if (isset(self::$resolved[$context->id])) {
            return self::$resolved[$context->id];
        }

        $values = array_fill_keys(self::FIELDS, '');
        $chain = array_merge([$context], $context->get_parent_contexts(false));
        $blocked = false;
        foreach ($chain as $ctx) {
            $local = filter_get_local_config(self::FILTERNAME, $ctx->id);
            $hascustom = !empty($local['honeypot_author']) && !empty($local['honeypot_title']);
            // Off stops inheritance when this context (and more specific ones) have
            // not supplied their own author+title. A quiz can still opt back in.
            if (
                !empty($local['honeypot_off']) && !$hascustom
                && $values['honeypot_author'] === '' && $values['honeypot_title'] === ''
            ) {
                $blocked = true;
                break;
            }
            foreach (self::FIELDS as $field) {
                if ($values[$field] === '' && !empty($local[$field])) {
                    $values[$field] = (string) $local[$field];
                }
            }
            if (self::values_complete($values)) {
                break;
            }
        }

        if (!$blocked && !self::values_complete($values)) {
            $site = self::from_site_config();
            $fallbacks = [
                'honeypot_author' => $site->author,
                'honeypot_title' => $site->title,
                'honeypot_venue' => $site->venue,
                'honeypot_claim' => $site->claim,
            ];
            foreach ($fallbacks as $field => $value) {
                if ($values[$field] === '' && $value !== '') {
                    $values[$field] = $value;
                }
            }
        }

        if ($blocked) {
            $values = array_fill_keys(self::FIELDS, '');
        }

        $honeypot = new self(
            $values['honeypot_author'],
            $values['honeypot_title'],
            $values['honeypot_venue'],
            $values['honeypot_claim'],
        );
        self::$resolved[$context->id] = $honeypot;
        return $honeypot;
    }

    /**
     * What this context would inherit if its own fields were left blank.
     *
     * Used on the local settings form so a course editor can see the parent
     * citation before overriding it.
     *
     * @param \context $context
     * @return self
     */
    public static function from_parent_context(\context $context): self {
        $parent = $context->get_parent_context();
        if (!$parent) {
            return self::from_site_config();
        }
        return self::from_context($parent);
    }

    /**
     * Create a honeypot from explicit citation parts.
     *
     * @param string $author Real author a marker will recognise.
     * @param string $title Fictional title that author never published.
     * @param string $venue Fictional or misapplied venue/year.
     * @param string $claim Optional invented scholarly claim.
     */
    public function __construct(string $author, string $title, string $venue, string $claim = '') {
        $this->author = trim($author);
        $this->title = trim($title);
        $this->venue = trim($venue);
        $this->claim = trim($claim);
    }

    /**
     * Whether enough of the citation is configured to inject.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return $this->author !== '' && $this->title !== '';
    }

    /**
     * Instruction a DOM scraper or AI paste will treat as part of the question.
     *
     * @return string
     */
    public function instruction_text(): string {
        if (!$this->is_configured()) {
            return '';
        }

        $data = (object) [
            'author' => $this->author,
            'title' => $this->title,
            'venue' => $this->venue,
            'claimsuffix' => '',
        ];
        if ($this->claim !== '') {
            $data->claimsuffix = get_string('honeypot_claimsuffix', 'filter_nocopydev', $this->claim);
        }

        if ($this->venue === '') {
            return get_string('honeypot_instruction_novenue', 'filter_nocopydev', $data);
        }
        return get_string('honeypot_instruction', 'filter_nocopydev', $data);
    }

    /**
     * Compact citation for pixel watermarks and marker-facing previews.
     *
     * @return string
     */
    public function short_label(): string {
        if (!$this->is_configured()) {
            return '';
        }

        $parts = [$this->author, $this->title];
        if ($this->venue !== '') {
            $parts[] = $this->venue;
        }
        return implode(', ', $parts);
    }

    /**
     * True if constructed-response text appears to have taken the bait.
     *
     * Title match is the high-confidence signal; author-alone is too common
     * in legitimate seminary essays.
     *
     * @param string $text Student response (plain or HTML).
     * @return bool
     */
    public function appears_in(string $text): bool {
        if (!$this->is_configured() || $text === '') {
            return false;
        }

        return preg_match($this->title_pattern(), $this->strip_html($text)) === 1;
    }

    /**
     * Wrap every occurrence of the fictional title in a marker-facing highlight.
     *
     * Only text between tags is searched, so attributes and URLs are left
     * alone. A title split by inline markup part-way through is not matched.
     *
     * @param string $html Filtered HTML about to be displayed.
     * @return string
     */
    public function highlight_in(string $html): string {
        if (!$this->is_configured() || $html === '') {
            return $html;
        }

        $segments = preg_split('~(<[^>]*>)~', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($segments === false) {
            return $html;
        }

        $pattern = $this->title_pattern();
        $open = '<mark class="filter-nocopydev-hit" title="' . s(get_string('honeypot_hit', 'filter_nocopydev')) . '">';
        foreach ($segments as $index => $segment) {
            // Odd indexes are the captured tags.
            if ($index % 2 === 1 || $segment === '') {
                continue;
            }
            $marked = preg_replace_callback($pattern, fn(array $match): string => $open . $match[0] . '</mark>', $segment);
            if ($marked !== null) {
                $segments[$index] = $marked;
            }
        }
        return implode('', $segments);
    }

    /**
     * Case-insensitive regex for the title, as it may appear in student HTML.
     *
     * Tolerates whatever an editor or a model does to spacing, apostrophes
     * and ampersands (non-breaking spaces, curly quotes, HTML entities).
     *
     * @return string
     */
    private function title_pattern(): string {
        $apostrophe = "(?:'|\u{2019}|\u{2018}|&\\#0?39;|&apos;|&rsquo;|&lsquo;)";
        $words = [];
        foreach (preg_split('/\s+/u', $this->title, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $pattern = '';
            foreach (mb_str_split($word) as $char) {
                $pattern .= match ($char) {
                    "'", "\u{2019}", "\u{2018}" => $apostrophe,
                    '&' => '(?:&amp;|&)',
                    default => preg_quote($char, '~'),
                };
            }
            $words[] = $pattern;
        }
        return '~' . implode('(?:\s|\x{00A0}|&nbsp;|&\#160;)+', $words) . '~iu';
    }

    /**
     * Config payload for the quiz-attempt AMD module.
     *
     * @return array<string, string>
     */
    public function for_js(): array {
        return [
            'author' => $this->author,
            'title' => $this->title,
            'venue' => $this->venue,
            'instruction' => $this->instruction_text(),
            'shortlabel' => $this->short_label(),
        ];
    }

    /**
     * Whether every citation field has been filled.
     *
     * @param array $values Resolved citation field values.
     * @return bool
     */
    private static function values_complete(array $values): bool {
        foreach (self::FIELDS as $field) {
            if ($values[$field] === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Strip tags and decode entities for title matching.
     *
     * @param string $text
     * @return string
     */
    private function strip_html(string $text): string {
        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
