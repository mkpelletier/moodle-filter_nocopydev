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
 * Tests for per-qtype mitigation policy.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_nocopydev\qtype_policy
 */
final class qtype_policy_test extends \advanced_testcase {
    public function test_essay_has_honeypot_shortanswer_does_not(): void {
        $defs = qtype_policy::definitions();

        $this->assertTrue($defs['essay']['dom_honeypot']);
        $this->assertTrue($defs['essay']['pixel_honeypot']);
        $this->assertSame(qtype_policy::COVER_QTEXT, $defs['essay']['cover']);

        $this->assertFalse($defs['shortanswer']['dom_honeypot']);
        $this->assertFalse($defs['shortanswer']['pixel_honeypot']);
        $this->assertTrue($defs['shortanswer']['forensic_watermark']);
    }

    public function test_selected_response_has_no_citation(): void {
        $defs = qtype_policy::definitions();
        foreach (['multichoice', 'truefalse', 'match'] as $qtype) {
            $this->assertFalse($defs[$qtype]['dom_honeypot'], $qtype);
            $this->assertFalse($defs[$qtype]['pixel_honeypot'], $qtype);
            $this->assertTrue($defs[$qtype]['forensic_watermark'], $qtype);
            $this->assertSame(qtype_policy::COVER_FORMULATION, $defs[$qtype]['cover'], $qtype);
        }
    }

    public function test_for_js_matches_definitions(): void {
        $this->assertSame(qtype_policy::definitions(), qtype_policy::for_js());
        $this->assertArrayHasKey('default', qtype_policy::for_js());
        $this->assertFalse(qtype_policy::for_js()['default']['dom_honeypot']);
    }
}
