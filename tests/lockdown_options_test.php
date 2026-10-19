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
 * Tests for the per-context lockdown switches.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_nocopydev\lockdown_options
 */
final class lockdown_options_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        lockdown_options::reset_caches();
    }

    public function test_everything_blocked_by_default(): void {
        $course = $this->getDataGenerator()->create_course();
        $values = lockdown_options::for_context(\context_course::instance($course->id));

        $this->assertSame(array_fill_keys(lockdown_options::OPTIONS, true), $values);
        $this->assertTrue(lockdown_options::any_blocked($values));
    }

    public function test_site_setting_off_reaches_courses(): void {
        set_config('block_paste', '0', 'filter_nocopydev');
        $course = $this->getDataGenerator()->create_course();
        $values = lockdown_options::for_context(\context_course::instance($course->id));

        $this->assertFalse($values['block_paste']);
        $this->assertTrue($values['block_copy']);
    }

    public function test_activity_inherits_course_and_can_override(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $coursecontext = \context_course::instance($course->id);
        $modcontext = \context_module::instance($quiz->cmid);

        filter_set_local_config('nocopydev', $coursecontext->id, 'block_devtools', '0');
        filter_set_local_config('nocopydev', $modcontext->id, 'block_copy', '0');

        $values = lockdown_options::for_context($modcontext);
        $this->assertFalse($values['block_devtools']);
        $this->assertFalse($values['block_copy']);
        $this->assertTrue($values['block_cut']);

        // An activity can block again what its course allows.
        filter_set_local_config('nocopydev', $modcontext->id, 'block_devtools', '1');
        lockdown_options::reset_caches();
        $this->assertTrue(lockdown_options::for_context($modcontext)['block_devtools']);
    }

    public function test_save_local_stores_only_differences(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $coursecontext = \context_course::instance($course->id);
        $modcontext = \context_module::instance($quiz->cmid);

        // Everything ticked except paste, as the form would submit it.
        $data = array_fill_keys(lockdown_options::OPTIONS, 1);
        $data['block_paste'] = 0;
        lockdown_options::save_local($modcontext, $data);

        $local = filter_get_local_config('nocopydev', $modcontext->id);
        $this->assertSame(['block_paste' => '0'], array_intersect_key($local, $data));

        // A later course change still reaches the quiz.
        filter_set_local_config('nocopydev', $coursecontext->id, 'block_copy', '0');
        lockdown_options::reset_caches();
        $this->assertFalse(lockdown_options::for_context($modcontext)['block_copy']);
    }

    public function test_paste_always_allowed_on_forum_pages(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $PAGE->set_url('/mod/forum/discuss.php', ['d' => 1]);
        $PAGE->set_context(\context_course::instance($course->id));
        $PAGE->set_pagetype('mod-forum-discuss');
        $values = lockdown_options::for_page($PAGE);
        $this->assertFalse($values['block_paste']);
        $this->assertTrue($values['block_copy']);
    }

    public function test_nothing_blocked(): void {
        $this->assertFalse(lockdown_options::any_blocked(array_fill_keys(lockdown_options::OPTIONS, false)));
    }
}
