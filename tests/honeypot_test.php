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
 * Tests for the citation honeypot.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_nocopydev\honeypot
 */
final class honeypot_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        honeypot::reset_caches();
    }

    public function test_not_configured_without_author_and_title(): void {
        $empty = new honeypot('', '', '', '');
        $this->assertFalse($empty->is_configured());
        $this->assertSame('', $empty->instruction_text());

        $authoronly = new honeypot('Craig Keener', '', '', '');
        $this->assertFalse($authoronly->is_configured());
    }

    public function test_instruction_includes_venue_and_claim(): void {
        $honeypot = new honeypot(
            'Craig Keener',
            'The Mercy Seat of the Nations',
            'JSNT 41 (2019)',
            'his distinction between honor-forensic and kinship-participatory justification'
        );
        $this->assertTrue($honeypot->is_configured());
        $text = $honeypot->instruction_text();
        $this->assertStringContainsString('Craig Keener', $text);
        $this->assertStringContainsString('The Mercy Seat of the Nations', $text);
        $this->assertStringContainsString('JSNT 41 (2019)', $text);
        $this->assertStringContainsString('honor-forensic', $text);
        $this->assertSame(
            'Craig Keener, The Mercy Seat of the Nations, JSNT 41 (2019)',
            $honeypot->short_label()
        );
    }

    public function test_instruction_without_venue(): void {
        $honeypot = new honeypot('Craig Keener', 'The Mercy Seat of the Nations', '', '');
        $text = $honeypot->instruction_text();
        $this->assertStringContainsString('The Mercy Seat of the Nations', $text);
        $this->assertStringNotContainsString('()', $text);
    }

    public function test_appears_in_matches_title_not_author_alone(): void {
        $honeypot = new honeypot('Craig Keener', 'The Mercy Seat of the Nations', 'JSNT 41 (2019)', '');
        $this->assertTrue($honeypot->appears_in('See Keener, The Mercy Seat of the Nations, on Romans 3.'));
        $this->assertFalse($honeypot->appears_in('Keener’s Romans commentary discusses justification.'));
        $this->assertFalse($honeypot->appears_in(''));
    }

    public function test_from_site_config(): void {
        set_config('honeypot_author', 'Craig Keener', 'filter_nocopydev');
        set_config('honeypot_title', 'The Mercy Seat of the Nations', 'filter_nocopydev');
        set_config('honeypot_venue', 'JSNT 41 (2019)', 'filter_nocopydev');
        set_config('honeypot_claim', '', 'filter_nocopydev');

        $honeypot = honeypot::from_site_config();
        $this->assertTrue($honeypot->is_configured());
        $this->assertSame('Craig Keener', $honeypot->author);
        $this->assertSame('The Mercy Seat of the Nations', $honeypot->title);
    }

    public function test_course_overrides_site_and_quiz_overrides_course(): void {
        set_config('honeypot_author', 'Site Author', 'filter_nocopydev');
        set_config('honeypot_title', 'Site Title', 'filter_nocopydev');

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $coursecontext = \context_course::instance($course->id);
        $modcontext = \context_module::instance($quiz->cmid);

        filter_set_local_config('nocopydev', $coursecontext->id, 'honeypot_author', 'Craig Keener');
        filter_set_local_config('nocopydev', $coursecontext->id, 'honeypot_title', 'Course Title');

        $fromcourse = honeypot::from_context($coursecontext);
        $this->assertSame('Craig Keener', $fromcourse->author);
        $this->assertSame('Course Title', $fromcourse->title);

        honeypot::reset_caches();
        filter_set_local_config('nocopydev', $modcontext->id, 'honeypot_author', 'Quiz Author');
        filter_set_local_config('nocopydev', $modcontext->id, 'honeypot_title', 'Quiz Title');

        $fromquiz = honeypot::from_context($modcontext);
        $this->assertSame('Quiz Author', $fromquiz->author);
        $this->assertSame('Quiz Title', $fromquiz->title);
    }

    public function test_honeypot_off_blocks_site_fallback(): void {
        set_config('honeypot_author', 'Site Author', 'filter_nocopydev');
        set_config('honeypot_title', 'Site Title', 'filter_nocopydev');

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        filter_set_local_config('nocopydev', $coursecontext->id, 'honeypot_off', '1');

        $honeypot = honeypot::from_context($coursecontext);
        $this->assertFalse($honeypot->is_configured());
    }

    public function test_quiz_custom_citation_wins_over_course_off(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $coursecontext = \context_course::instance($course->id);
        $modcontext = \context_module::instance($quiz->cmid);

        filter_set_local_config('nocopydev', $coursecontext->id, 'honeypot_off', '1');
        filter_set_local_config('nocopydev', $modcontext->id, 'honeypot_author', 'Quiz Author');
        filter_set_local_config('nocopydev', $modcontext->id, 'honeypot_title', 'Quiz Title');

        $honeypot = honeypot::from_context($modcontext);
        $this->assertTrue($honeypot->is_configured());
        $this->assertSame('Quiz Author', $honeypot->author);
        $this->assertSame('Quiz Title', $honeypot->title);
    }
}
