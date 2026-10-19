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
 * One-off copy of settings from filter_nocopydev, this plugin's former name.
 *
 * Moodle treats a renamed component as a new plugin, so without this a site
 * upgrading from No Copy / Dev Tools would lose its citations, lockdown
 * switches, and where the filter was turned on. The old plugin's data is
 * copied, not moved: it is removed when the admin uninstalls the old plugin.
 *
 * @package    filter_tripwire
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nocopydev_migration {
    /** Former filter name (filter_active / filter_config). */
    public const OLD_FILTER = 'nocopydev';

    /** Former component (config_plugins). */
    public const OLD_COMPONENT = 'filter_nocopydev';

    /** Current filter name. */
    public const NEW_FILTER = 'tripwire';

    /** Capability renames. */
    public const CAPABILITIES = [
        'filter/nocopydev:viewhoneypothits' => 'filter/tripwire:viewhoneypothits',
    ];

    /**
     * Copy everything from the old plugin, then disable the old filter.
     *
     * Site settings are copied over Tripwire's own; filter states and local
     * config already present for Tripwire are kept, so a rerun adds nothing.
     *
     * @return bool True if there was anything to migrate.
     */
    public static function run(): bool {
        if (!self::old_plugin_present()) {
            return false;
        }

        self::copy_settings();
        self::copy_filter_states();
        self::copy_local_config();
        self::copy_capability_overrides();
        self::disable_old_filter();
        return true;
    }

    /**
     * Whether filter_nocopydev left any data on this site.
     *
     * @return bool
     */
    public static function old_plugin_present(): bool {
        global $DB;

        return $DB->record_exists('config_plugins', ['plugin' => self::OLD_COMPONENT])
            || $DB->record_exists('filter_active', ['filter' => self::OLD_FILTER])
            || $DB->record_exists('filter_config', ['filter' => self::OLD_FILTER]);
    }

    /**
     * Site settings (config_plugins), except install bookkeeping.
     *
     * @return void
     */
    private static function copy_settings(): void {
        global $DB;

        $skip = ['version', 'installrunning'];
        foreach ($DB->get_records('config_plugins', ['plugin' => self::OLD_COMPONENT]) as $row) {
            if (in_array($row->name, $skip, true)) {
                continue;
            }
            set_config($row->name, $row->value, 'filter_' . self::NEW_FILTER);
        }
    }

    /**
     * On / off / disabled at site level and every local override.
     *
     * The site-level row keeps the old sort order, so Tripwire takes the old
     * filter's place in Manage filters.
     *
     * @return void
     */
    private static function copy_filter_states(): void {
        global $DB;

        foreach ($DB->get_records('filter_active', ['filter' => self::OLD_FILTER]) as $row) {
            $exists = ['filter' => self::NEW_FILTER, 'contextid' => $row->contextid];
            if ($DB->record_exists('filter_active', $exists)) {
                continue;
            }
            $DB->insert_record('filter_active', (object) [
                'filter' => self::NEW_FILTER,
                'contextid' => $row->contextid,
                'active' => $row->active,
                'sortorder' => $row->sortorder,
            ]);
        }
    }

    /**
     * Per-context citations and lockdown switches (filter_config).
     *
     * @return void
     */
    private static function copy_local_config(): void {
        global $DB;

        foreach ($DB->get_records('filter_config', ['filter' => self::OLD_FILTER]) as $row) {
            $exists = ['filter' => self::NEW_FILTER, 'contextid' => $row->contextid, 'name' => $row->name];
            if ($DB->record_exists('filter_config', $exists)) {
                continue;
            }
            $DB->insert_record('filter_config', (object) [
                'filter' => self::NEW_FILTER,
                'contextid' => $row->contextid,
                'name' => $row->name,
                'value' => $row->value,
            ]);
        }
    }

    /**
     * Role overrides of the renamed capabilities.
     *
     * install.php runs before Moodle registers this plugin's capabilities,
     * so register them first; archetype defaults are then overwritten by
     * whatever the site had set on the old capability.
     *
     * @return void
     */
    private static function copy_capability_overrides(): void {
        global $DB;

        $oldnames = array_keys(self::CAPABILITIES);
        [$insql, $params] = $DB->get_in_or_equal($oldnames);
        $overrides = $DB->get_records_select('role_capabilities', "capability $insql", $params);
        if (!$overrides) {
            return;
        }

        update_capabilities('filter_' . self::NEW_FILTER);
        foreach ($overrides as $row) {
            assign_capability(self::CAPABILITIES[$row->capability], $row->permission, $row->roleid, $row->contextid, true);
        }
    }

    /**
     * Turn the old filter off everywhere so the two never run together.
     *
     * @return void
     */
    private static function disable_old_filter(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $old = $DB->get_record('filter_active', ['filter' => self::OLD_FILTER, 'contextid' => $syscontext->id]);
        if ($old && (int) $old->active !== TEXTFILTER_DISABLED) {
            filter_set_global_state(self::OLD_FILTER, TEXTFILTER_DISABLED);
        }
    }
}
