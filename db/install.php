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

/**
 * Install steps for filter_tripwire.
 *
 * @package    filter_tripwire
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Carry settings over from filter_nocopydev, the plugin's former name.
 *
 * @return bool
 */
function xmldb_filter_tripwire_install() {
    if (\filter_tripwire\local\nocopydev_migration::run()) {
        $message = get_string('migrated_from_nocopydev', 'filter_tripwire');
        if (CLI_SCRIPT) {
            mtrace($message);
        } else {
            \core\notification::info($message);
        }
    }
    return true;
}
