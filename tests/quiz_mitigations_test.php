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
 * Tests for quiz-attempt mitigation helpers.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_nocopydev\quiz_mitigations
 */
final class quiz_mitigations_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        honeypot::reset_caches();
    }

    public function test_is_attempt_page(): void {
        global $PAGE;

        $PAGE->set_url('/mod/quiz/attempt.php', ['attempt' => 1]);
        $PAGE->set_pagetype('mod-quiz-attempt');
        $this->assertTrue(quiz_mitigations::is_attempt_page($PAGE));

        $PAGE->set_pagetype('mod-quiz-review');
        $this->assertFalse(quiz_mitigations::is_attempt_page($PAGE));
    }

    public function test_forensic_token_is_stable_and_unique(): void {
        $one = quiz_mitigations::forensic_token(10, 20, 30);
        $again = quiz_mitigations::forensic_token(10, 20, 30);
        $other = quiz_mitigations::forensic_token(10, 20, 31);

        $this->assertSame(8, strlen($one));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $one);
        $this->assertSame($one, $again);
        $this->assertNotEquals($one, $other);
    }

    /**
     * Stem CSS must not force transparent ink; nested gapfill inputs stay typeable.
     *
     * @coversNothing
     */
    public function test_attempt_css_preserves_author_colour_and_gap_inputs(): void {
        $css = file_get_contents(dirname(__DIR__) . '/styles.css');
        $this->assertNotFalse($css);
        $this->assertStringNotContainsString('color: transparent', $css);
        $this->assertStringNotContainsString('--filter-nocopydev-ink', $css);
        $this->assertStringNotContainsString('-webkit-text-fill-color: transparent', $css);
        $this->assertStringNotContainsString('color: inherit !important', $css);
        $this->assertStringContainsString('user-select: text', $css);
        $this->assertStringContainsString('z-index: 3', $css);
    }

    public function test_js_config_includes_honeypot_and_policies(): void {
        global $PAGE, $USER;

        $this->setAdminUser();
        set_config('honeypot_author', 'Craig Keener', 'filter_nocopydev');
        set_config('honeypot_title', 'The Mercy Seat of the Nations', 'filter_nocopydev');
        set_config('enable_quiz_mitigations', '1', 'filter_nocopydev');

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $PAGE->set_url('/mod/quiz/attempt.php', ['attempt' => 42]);
        $PAGE->set_context($context);
        $PAGE->set_pagetype('mod-quiz-attempt');

        $config = quiz_mitigations::js_config($PAGE);
        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['domhoneypot']);
        $this->assertSame('Craig Keener', $config['honeypot']['author']);
        $this->assertArrayHasKey('essay', $config['policies']);
        $this->assertTrue($config['policies']['essay']['dom_honeypot']);
        $this->assertFalse($config['policies']['shortanswer']['dom_honeypot']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $config['token']);
        $this->assertNotEmpty($USER->id);
    }
}
