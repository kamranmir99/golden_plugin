<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for local_golden.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // 1) Link under Site administration → Reports → GOLDEN Map.
    $ADMIN->add('reports', new admin_externalpage(
        'local_golden_report',
        get_string('nav_golden', 'local_golden'),
        new moodle_url('/local/golden/index.php'),
        'local/golden:view'
    ));

    // 2) Plugin settings page under Site administration → Plugins → Local plugins.
    $settings = new admin_settingpage('local_golden', get_string('pluginname', 'local_golden'));
    $ADMIN->add('localplugins', $settings);

    $defaultpath = $CFG->dirroot . '/local/golden/data/GeoLite2-City.mmdb';

    $settings->add(new admin_setting_configtext(
        'local_golden/geoip_path',
        get_string('settings_geoip_path', 'local_golden'),
        get_string('settings_geoip_path_desc', 'local_golden', $defaultpath),
        $defaultpath,
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_golden/tile_url',
        get_string('settings_tile_url', 'local_golden'),
        get_string('settings_tile_url_desc', 'local_golden'),
        'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_golden/max_users',
        get_string('settings_max_users', 'local_golden'),
        get_string('settings_max_users_desc', 'local_golden'),
        '5000',
        PARAM_INT
    ));
}
