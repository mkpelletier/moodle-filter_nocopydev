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
 * Admin settings for filter_nocopydev.
 *
 * @package    filter_nocopydev
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading(
    'filter_nocopydev/lockdownheading',
    get_string('setting_lockdownheading', 'filter_nocopydev'),
    get_string('setting_lockdownheading_desc', 'filter_nocopydev')
));

$settings->add(new admin_setting_pickroles(
    'filter_nocopydev/restrictedroles',
    get_string('setting_restrictedroles', 'filter_nocopydev'),
    get_string('setting_restrictedroles_desc', 'filter_nocopydev'),
    ['student']
));

$settings->add(new admin_setting_heading(
    'filter_nocopydev/quizheading',
    get_string('setting_quizheading', 'filter_nocopydev'),
    get_string('setting_quizheading_desc', 'filter_nocopydev')
));

$settings->add(new admin_setting_configcheckbox(
    'filter_nocopydev/enable_quiz_mitigations',
    get_string('setting_enable_quiz_mitigations', 'filter_nocopydev'),
    get_string('setting_enable_quiz_mitigations_desc', 'filter_nocopydev'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_nocopydev/enable_dom_honeypot',
    get_string('setting_enable_dom_honeypot', 'filter_nocopydev'),
    get_string('setting_enable_dom_honeypot_desc', 'filter_nocopydev'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_nocopydev/enable_forensic_watermark',
    get_string('setting_enable_forensic_watermark', 'filter_nocopydev'),
    get_string('setting_enable_forensic_watermark_desc', 'filter_nocopydev'),
    1
));

$settings->add(new admin_setting_configtext(
    'filter_nocopydev/watermark_label',
    get_string('setting_watermark_label', 'filter_nocopydev'),
    get_string('setting_watermark_label_desc', 'filter_nocopydev'),
    get_string('watermark_label_default', 'filter_nocopydev')
));

$settings->add(new admin_setting_heading(
    'filter_nocopydev/honeypotheading',
    get_string('setting_honeypotheading', 'filter_nocopydev'),
    get_string('setting_honeypotheading_desc', 'filter_nocopydev')
));

$settings->add(new admin_setting_configtext(
    'filter_nocopydev/honeypot_author',
    get_string('setting_honeypot_author', 'filter_nocopydev'),
    get_string('setting_honeypot_author_desc', 'filter_nocopydev'),
    ''
));

$settings->add(new admin_setting_configtext(
    'filter_nocopydev/honeypot_title',
    get_string('setting_honeypot_title', 'filter_nocopydev'),
    get_string('setting_honeypot_title_desc', 'filter_nocopydev'),
    ''
));

$settings->add(new admin_setting_configtext(
    'filter_nocopydev/honeypot_venue',
    get_string('setting_honeypot_venue', 'filter_nocopydev'),
    get_string('setting_honeypot_venue_desc', 'filter_nocopydev'),
    ''
));

$settings->add(new admin_setting_configtextarea(
    'filter_nocopydev/honeypot_claim',
    get_string('setting_honeypot_claim', 'filter_nocopydev'),
    get_string('setting_honeypot_claim_desc', 'filter_nocopydev'),
    ''
));
