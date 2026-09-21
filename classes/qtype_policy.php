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
 * Per-qtype mitigation policy for quiz attempt pages.
 *
 * Selected-response items leak through capture (a snip of stem + options is a
 * complete prompt; the submission never echoes a canary). Constructed-response
 * items leak through generation (the model writes the answer, so a citation
 * honeypot can come back in the script).
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_policy {
    /**
     * Overlay the prompt only — used when the answer box must stay clean.
     */
    public const COVER_QTEXT = 'qtext';

    /**
     * Overlay stem and options/table — used when the snip target is the whole item.
     */
    public const COVER_FORMULATION = 'formulation';

    /**
     * Policy keyed by the CSS class Moodle puts on `.que` (qtype name).
     *
     * @return array<string, array{
     *     dom_honeypot: bool,
     *     pixel_honeypot: bool,
     *     forensic_watermark: bool,
     *     block_print: bool,
     *     cover: string
     * }>
     */
    public static function definitions(): array {
        $constructed = [
            'dom_honeypot' => true,
            'pixel_honeypot' => true,
            'forensic_watermark' => true,
            'block_print' => true,
            'cover' => self::COVER_QTEXT,
        ];
        $selected = [
            'dom_honeypot' => false,
            'pixel_honeypot' => false,
            'forensic_watermark' => true,
            'block_print' => true,
            'cover' => self::COVER_FORMULATION,
        ];
        $calculated = [
            'dom_honeypot' => false,
            'pixel_honeypot' => false,
            'forensic_watermark' => true,
            'block_print' => true,
            'cover' => self::COVER_FORMULATION,
        ];

        return [
            // Essay: honeypot can be echoed in a long answer.
            'essay' => $constructed,
            'essayautograde' => $constructed,

            // Short answer: a citation makes the model write a paragraph
            // instead of the expected word, which can alert the student.
            'shortanswer' => [
                'dom_honeypot' => false,
                'pixel_honeypot' => false,
                'forensic_watermark' => true,
                'block_print' => true,
                'cover' => self::COVER_QTEXT,
            ],

            // Cloze may mix dropdowns and short-answer blanks; carry the
            // honeypot so short-answer subparts can still take the bait.
            'multianswer' => array_merge($constructed, ['cover' => self::COVER_FORMULATION]),

            // Selected response: submission is a click. Forensic watermark
            // only — never a citation the student could notice on a T/F.
            'multichoice' => $selected,
            'truefalse' => $selected,
            'match' => $selected,
            'randomsamatch' => $selected,
            'gapselect' => $selected,
            'ddwtos' => $selected,
            'ddimageortext' => $selected,
            'ddmarker' => $selected,
            'calculatedmulti' => $selected,

            // Per-attempt values already break neighbour-sharing; watermark
            // is for leak attribution, not a citation honeypot.
            'numerical' => $calculated,
            'calculated' => $calculated,
            'calculatedsimple' => $calculated,

            'default' => $selected,
        ];
    }

    /**
     * Policy object map for the AMD module.
     *
     * @return array<string, array<string, bool|string>>
     */
    public static function for_js(): array {
        return self::definitions();
    }
}
