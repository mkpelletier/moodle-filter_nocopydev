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
 * Admin settings for filter_tripwire.
 *
 * @package    filter_tripwire
 * @copyright  2026 Mathieu Pelletier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading(
    'filter_tripwire/lockdownheading',
    get_string('setting_lockdownheading', 'filter_tripwire'),
    get_string('setting_lockdownheading_desc', 'filter_tripwire')
));

$settings->add(new admin_setting_pickroles(
    'filter_tripwire/restrictedroles',
    get_string('setting_restrictedroles', 'filter_tripwire'),
    get_string('setting_restrictedroles_desc', 'filter_tripwire'),
    ['student']
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/block_copy',
    get_string('setting_block_copy', 'filter_tripwire'),
    get_string('setting_block_copy_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/block_cut',
    get_string('setting_block_cut', 'filter_tripwire'),
    get_string('setting_block_cut_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/block_paste',
    get_string('setting_block_paste', 'filter_tripwire'),
    get_string('setting_block_paste_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/block_selection',
    get_string('setting_block_selection', 'filter_tripwire'),
    get_string('setting_block_selection_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/block_contextmenu',
    get_string('setting_block_contextmenu', 'filter_tripwire'),
    get_string('setting_block_contextmenu_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/block_devtools',
    get_string('setting_block_devtools', 'filter_tripwire'),
    get_string('setting_block_devtools_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_heading(
    'filter_tripwire/quizheading',
    get_string('setting_quizheading', 'filter_tripwire'),
    get_string('setting_quizheading_desc', 'filter_tripwire')
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/enable_quiz_mitigations',
    get_string('setting_enable_quiz_mitigations', 'filter_tripwire'),
    get_string('setting_enable_quiz_mitigations_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/enable_dom_honeypot',
    get_string('setting_enable_dom_honeypot', 'filter_tripwire'),
    get_string('setting_enable_dom_honeypot_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/enable_forensic_watermark',
    get_string('setting_enable_forensic_watermark', 'filter_tripwire'),
    get_string('setting_enable_forensic_watermark_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_configtext(
    'filter_tripwire/watermark_label',
    get_string('setting_watermark_label', 'filter_tripwire'),
    get_string('setting_watermark_label_desc', 'filter_tripwire'),
    get_string('watermark_label_default', 'filter_tripwire')
));

$settings->add(new admin_setting_heading(
    'filter_tripwire/forumheading',
    get_string('setting_forumheading', 'filter_tripwire'),
    get_string('setting_forumheading_desc', 'filter_tripwire')
));

$settings->add(new admin_setting_configcheckbox(
    'filter_tripwire/enable_forum_honeypot',
    get_string('setting_enable_forum_honeypot', 'filter_tripwire'),
    get_string('setting_enable_forum_honeypot_desc', 'filter_tripwire'),
    1
));

$settings->add(new admin_setting_heading(
    'filter_tripwire/honeypotheading',
    get_string('setting_honeypotheading', 'filter_tripwire'),
    get_string('setting_honeypotheading_desc', 'filter_tripwire')
));

$settings->add(new admin_setting_configtext(
    'filter_tripwire/honeypot_author',
    get_string('setting_honeypot_author', 'filter_tripwire'),
    get_string('setting_honeypot_author_desc', 'filter_tripwire'),
    ''
));

$settings->add(new admin_setting_configtext(
    'filter_tripwire/honeypot_title',
    get_string('setting_honeypot_title', 'filter_tripwire'),
    get_string('setting_honeypot_title_desc', 'filter_tripwire'),
    ''
));

$settings->add(new admin_setting_configtext(
    'filter_tripwire/honeypot_venue',
    get_string('setting_honeypot_venue', 'filter_tripwire'),
    get_string('setting_honeypot_venue_desc', 'filter_tripwire'),
    ''
));

$settings->add(new admin_setting_configtextarea(
    'filter_tripwire/honeypot_claim',
    get_string('setting_honeypot_claim', 'filter_tripwire'),
    get_string('setting_honeypot_claim_desc', 'filter_tripwire'),
    ''
));
