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
 * Per-context (category, course, activity) honeypot settings.
 *
 * Discovered by filter/manage.php when a teacher opens Filters in a course
 * or quiz and clicks Settings next to No Copy / Dev Tools. Blank fields
 * inherit from the parent context or the site default.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

// Core defines this as namespace core_filters in filter/classes/form/,
// which does not autoload as \core_filters\local_settings_form. Load it
// before the subclass is declared (Moodle 5.0 and 5.3).
if (!class_exists(\core_filters\local_settings_form::class, false)) {
    require_once($CFG->dirroot . '/filter/classes/form/local_settings_form.php');
}

/**
 * Local settings form for filter_nocopydev.
 *
 * Class name is required by filter/manage.php.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nocopydev_filter_local_settings_form extends \core_filters\local_settings_form {
    #[\Override]
    protected function definition_inner($mform) {
        $mform->addElement(
            'static',
            'localintro',
            '',
            get_string('local_honeypot_intro', 'filter_nocopydev')
        );

        $inherited = \filter_nocopydev\honeypot::from_parent_context($this->context);
        if ($inherited->is_configured()) {
            $mform->addElement(
                'static',
                'inherited',
                get_string('local_honeypot_inherited', 'filter_nocopydev'),
                s($inherited->short_label())
            );
        } else {
            $mform->addElement(
                'static',
                'inherited',
                get_string('local_honeypot_inherited', 'filter_nocopydev'),
                get_string('local_honeypot_inherited_none', 'filter_nocopydev')
            );
        }

        $mform->addElement(
            'advcheckbox',
            'honeypot_off',
            get_string('local_honeypot_off', 'filter_nocopydev'),
            get_string('local_honeypot_off_desc', 'filter_nocopydev')
        );

        $mform->addElement(
            'text',
            'honeypot_author',
            get_string('setting_honeypot_author', 'filter_nocopydev'),
            ['size' => 40]
        );
        $mform->setType('honeypot_author', PARAM_TEXT);

        $mform->addElement(
            'text',
            'honeypot_title',
            get_string('setting_honeypot_title', 'filter_nocopydev'),
            ['size' => 40]
        );
        $mform->setType('honeypot_title', PARAM_TEXT);

        $mform->addElement(
            'text',
            'honeypot_venue',
            get_string('setting_honeypot_venue', 'filter_nocopydev'),
            ['size' => 40]
        );
        $mform->setType('honeypot_venue', PARAM_TEXT);

        $mform->addElement(
            'textarea',
            'honeypot_claim',
            get_string('setting_honeypot_claim', 'filter_nocopydev'),
            ['rows' => 3, 'cols' => 40]
        );
        $mform->setType('honeypot_claim', PARAM_TEXT);
    }

    #[\Override]
    public function save_changes($data) {
        $data = (array) $data;
        $off = !empty($data['honeypot_off']);
        if ($off) {
            filter_set_local_config($this->filter, $this->context->id, 'honeypot_off', '1');
        } else {
            filter_unset_local_config($this->filter, $this->context->id, 'honeypot_off');
        }

        foreach (\filter_nocopydev\honeypot::FIELDS as $name) {
            $value = isset($data[$name]) ? trim((string) $data[$name]) : '';
            if ($value !== '') {
                filter_set_local_config($this->filter, $this->context->id, $name, $value);
            } else {
                filter_unset_local_config($this->filter, $this->context->id, $name);
            }
        }
    }
}
