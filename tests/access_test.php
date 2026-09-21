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
 * Tests for role-based copy/paste lockdown.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_nocopydev\access
 */
final class access_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_student_is_restricted_teacher_is_not(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->setUser($student);
        $this->assertTrue(access::lockdown_applies($context));

        $this->setUser($teacher);
        $this->assertFalse(access::lockdown_applies($context));
    }

    public function test_empty_restricted_roles_locks_nobody(): void {
        set_config('restrictedroles', '', 'filter_nocopydev');
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->assertFalse(access::lockdown_applies($context));
        $this->assertSame([], access::restricted_role_ids());
    }

    public function test_guest_is_not_restricted(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $this->setGuestUser();
        $this->assertFalse(access::lockdown_applies($context));
    }

    public function test_should_lockdown_on_attempt_page_even_for_teacher(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_context(\context_course::instance($course->id));
        $PAGE->set_pagetype('course-view');
        $this->assertFalse(access::should_lockdown($PAGE));

        $PAGE->set_pagetype('mod-quiz-attempt');
        $this->assertTrue(access::should_lockdown($PAGE));
    }
}
