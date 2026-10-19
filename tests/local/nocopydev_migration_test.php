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

namespace filter_tripwire\local;

/**
 * Tests for the copy of settings from filter_nocopydev.
 *
 * @package    filter_tripwire
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_tripwire\local\nocopydev_migration
 */
final class nocopydev_migration_test extends \advanced_testcase {
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        // A dev site may still have filter_nocopydev installed; start as a site that never had it.
        $DB->delete_records('config_plugins', ['plugin' => 'filter_nocopydev']);
        $DB->delete_records('filter_active', ['filter' => 'nocopydev']);
        $DB->delete_records('filter_config', ['filter' => 'nocopydev']);
        $DB->delete_records('role_capabilities', ['capability' => 'filter/nocopydev:viewhoneypothits']);
    }

    public function test_nothing_to_do_on_a_fresh_site(): void {
        $this->assertFalse(nocopydev_migration::run());
    }

    public function test_copies_everything_and_disables_old_filter(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $teacherrole = $DB->get_field('role', 'id', ['shortname' => 'teacher']);

        // What a site running filter_nocopydev 1.3.0 would hold.
        set_config('version', '2026101900', 'filter_nocopydev');
        set_config('block_paste', '0', 'filter_nocopydev');
        set_config('honeypot_author', 'Craig Keener', 'filter_nocopydev');
        $DB->delete_records('filter_active', ['filter' => 'tripwire']);
        $DB->insert_record('filter_active', (object) [
            'filter' => 'nocopydev', 'contextid' => $syscontext->id, 'active' => TEXTFILTER_OFF, 'sortorder' => 3,
        ]);
        $DB->insert_record('filter_active', (object) [
            'filter' => 'nocopydev', 'contextid' => $coursecontext->id, 'active' => TEXTFILTER_ON, 'sortorder' => 0,
        ]);
        $DB->insert_record('filter_config', (object) [
            'filter' => 'nocopydev', 'contextid' => $coursecontext->id, 'name' => 'honeypot_title', 'value' => 'The Romans Roadway',
        ]);
        $DB->insert_record('role_capabilities', (object) [
            'contextid' => $syscontext->id, 'roleid' => $teacherrole, 'capability' => 'filter/nocopydev:viewhoneypothits',
            'permission' => CAP_PROHIBIT, 'timemodified' => time(), 'modifierid' => 0,
        ]);

        $this->assertTrue(nocopydev_migration::run());

        // Settings, but not the old version number.
        $this->assertSame('0', get_config('filter_tripwire', 'block_paste'));
        $this->assertSame('Craig Keener', get_config('filter_tripwire', 'honeypot_author'));
        $this->assertNotSame('2026101900', get_config('filter_tripwire', 'version'));

        // Filter states, with the old site-level slot.
        $site = $DB->get_record('filter_active', ['filter' => 'tripwire', 'contextid' => $syscontext->id]);
        $this->assertEquals(TEXTFILTER_OFF, $site->active);
        $this->assertEquals(TEXTFILTER_ON, $DB->get_field('filter_active', 'active',
            ['filter' => 'tripwire', 'contextid' => $coursecontext->id]));

        // Local citation.
        $this->assertSame('The Romans Roadway', filter_get_local_config('tripwire', $coursecontext->id)['honeypot_title']);

        // Role override.
        $this->assertEquals(CAP_PROHIBIT, $DB->get_field('role_capabilities', 'permission', [
            'contextid' => $syscontext->id, 'roleid' => $teacherrole, 'capability' => 'filter/tripwire:viewhoneypothits',
        ]));

        // Old filter is off everywhere.
        $this->assertEquals(TEXTFILTER_DISABLED, $DB->get_field('filter_active', 'active',
            ['filter' => 'nocopydev', 'contextid' => $syscontext->id]));

        // A second run changes nothing.
        $this->assertTrue(nocopydev_migration::run());
        $this->assertSame(1, $DB->count_records('filter_config', ['filter' => 'tripwire']));
        $this->assertSame(2, $DB->count_records('filter_active', ['filter' => 'tripwire']));
    }
}
