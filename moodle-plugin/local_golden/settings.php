<?php
// Admin settings for local_golden.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
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
