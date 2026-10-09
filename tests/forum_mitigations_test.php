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
 * Tests for the Q&A forum honeypot config.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_nocopydev\forum_mitigations
 */
final class forum_mitigations_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        honeypot::reset_caches();
    }

    /**
     * Create a forum with one staff-authored discussion and point $PAGE at it.
     *
     * @param \stdClass $course
     * @param string $type Forum type.
     * @return int Id of the discussion's first post.
     */
    private function set_up_forum_page(\stdClass $course, string $type): int {
        global $PAGE, $DB;

        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'type' => $type]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $teacher->id,
        ]);

        $context = \context_module::instance($forum->cmid);
        filter_set_local_config('nocopydev', $context->id, 'honeypot_author', 'Craig Keener');
        filter_set_local_config('nocopydev', $context->id, 'honeypot_title', 'The Mercy Seat of the Nations');

        $PAGE = new \moodle_page();
        $PAGE->set_url('/mod/forum/discuss.php', ['d' => $discussion->id]);
        $PAGE->set_cm(get_coursemodule_from_instance('forum', $forum->id), $course);
        $PAGE->set_pagetype('mod-forum-discuss');

        return (int) $DB->get_field('forum_discussions', 'firstpost', ['id' => $discussion->id]);
    }

    public function test_is_forum_page(): void {
        global $PAGE;

        $PAGE->set_url('/mod/forum/discuss.php', ['d' => 1]);
        $PAGE->set_pagetype('mod-forum-discuss');
        $this->assertTrue(forum_mitigations::is_forum_page($PAGE));

        $PAGE->set_pagetype('mod-forum-post');
        $this->assertTrue(forum_mitigations::is_forum_page($PAGE));

        $PAGE->set_pagetype('mod-quiz-attempt');
        $this->assertFalse(forum_mitigations::is_forum_page($PAGE));
    }

    public function test_student_on_qanda_forum_gets_question_post(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $firstpost = $this->set_up_forum_page($course, 'qanda');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $config = forum_mitigations::js_config($PAGE);
        $this->assertTrue($config['enabled']);
        $this->assertSame([$firstpost], $config['postids']);
        $this->assertStringContainsString('The Mercy Seat of the Nations', $config['instruction']);
    }

    public function test_other_forum_types_are_not_baited(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $this->set_up_forum_page($course, 'general');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $this->assertSame([], forum_mitigations::js_config($PAGE)['postids']);
    }

    public function test_teacher_is_not_baited(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $this->set_up_forum_page($course, 'qanda');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));

        $config = forum_mitigations::js_config($PAGE);
        $this->assertFalse($config['enabled']);
        $this->assertSame('', $config['instruction']);
        $this->assertSame([], $config['postids']);
    }

    public function test_setting_disables_forum_honeypot(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $this->set_up_forum_page($course, 'qanda');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));
        set_config('enable_forum_honeypot', '0', 'filter_nocopydev');

        $this->assertFalse(forum_mitigations::js_config($PAGE)['enabled']);
    }
}
