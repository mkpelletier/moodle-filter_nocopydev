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
 * Role checks for the copy/paste lockdown.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access {

    /**
     * Whether copy/paste lockdown should run on this page.
     *
     * Restricted roles (Student by default) are always locked. Teachers are
     * not, except on an in-progress quiz attempt page, which is how Preview
     * quiz is served — so staff can still test the filter.
     *
     * @param \moodle_page $page
     * @return bool
     */
    public static function should_lockdown(\moodle_page $page): bool {
        if (quiz_mitigations::is_attempt_page($page)) {
            return true;
        }
        return self::lockdown_applies($page->context);
    }

    /**
     * Whether the current user should receive copy/paste and context-menu lockdown.
     *
     * True if they hold (or have switched to) one of the configured roles in
     * this context. Teachers reviewing attempts are therefore unrestricted
     * unless Student is also assigned to them here.
     *
     * @param \context $context
     * @return bool
     */
    public static function lockdown_applies(\context $context): bool {
        global $USER, $COURSE;

        $restricted = self::restricted_role_ids();
        if ($restricted === []) {
            return false;
        }

        $courseid = self::course_id_for($context, $COURSE);
        if ($courseid && is_role_switched($courseid)) {
            $coursecontext = \context_course::instance($courseid);
            $switched = (int) ($USER->access['rsw'][$coursecontext->path] ?? 0);
            return $switched !== 0 && in_array($switched, $restricted, true);
        }

        if (empty($USER->id)) {
            return false;
        }

        foreach (get_user_roles($context, $USER->id, true) as $assignment) {
            if (in_array((int) $assignment->roleid, $restricted, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Role ids selected in plugin settings. Unset config means Student only.
     *
     * @return int[]
     */
    public static function restricted_role_ids(): array {
        $raw = get_config('filter_nocopydev', 'restrictedroles');
        if ($raw === false) {
            return self::student_role_ids();
        }
        if ($raw === '' || $raw === '0') {
            return [];
        }

        $ids = [];
        foreach (explode(',', (string) $raw) as $id) {
            $id = (int) trim($id);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * @return int[]
     */
    private static function student_role_ids(): array {
        $ids = [];
        foreach (get_archetype_roles('student') as $role) {
            $ids[] = (int) $role->id;
        }
        return $ids;
    }

    /**
     * @param \context $context
     * @param \stdClass|null $course
     * @return int
     */
    private static function course_id_for(\context $context, $course): int {
        if (!empty($course->id) && (int) $course->id !== SITEID) {
            return (int) $course->id;
        }
        $coursecontext = $context->get_course_context(false);
        if ($coursecontext) {
            return (int) $coursecontext->instanceid;
        }
        return 0;
    }
}
