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
 * Tests for the text filter noscript overlay.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_nocopydev\text_filter
 */
final class text_filter_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        text_filter::reset_caches();
    }

    public function test_filter_injects_noscript_for_student(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_context($context);
        $PAGE->set_pagetype('course-view');

        $filter = new text_filter($context, []);
        $out = $filter->filter('Visible content');
        $this->assertStringContainsString('Visible content', $out);
        $this->assertStringContainsString('<noscript', $out);
        $this->assertStringContainsString('JavaScript must be enabled', $out);

        $second = $filter->filter('Second block');
        $this->assertSame('Second block', $second);
    }

    public function test_filter_skips_noscript_for_teacher_off_attempt(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_context($context);
        $PAGE->set_pagetype('course-view');

        $filter = new text_filter($context, []);
        $out = $filter->filter('Teacher content');
        $this->assertSame('Teacher content', $out);
    }
}
