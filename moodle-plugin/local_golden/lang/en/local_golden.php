<?php
// Language strings for local_golden.

defined('MOODLE_INTERNAL') || die();

$string['pluginname']           = 'GOLDEN – Geospatial Model for Distance Education';
$string['golden:view']          = 'View the GOLDEN geospatial report';
$string['nav_golden']           = 'GOLDEN Map';
$string['page_title']           = 'GOLDEN – Student geospatial dashboard';
$string['settings_geoip_path']  = 'GeoLite2 database path';
$string['settings_geoip_path_desc'] = 'Absolute path to the MaxMind GeoLite2-City.mmdb file. Default: {$a}';
$string['settings_tile_url']    = 'Map tile URL template';
$string['settings_tile_url_desc']   = 'Leaflet tile-layer URL. Default is CARTO dark-matter. Use {s},{z},{x},{y} placeholders.';
$string['settings_max_users']   = 'Maximum users to geolocate';
$string['settings_max_users_desc']  = 'Cap on how many users to plot (performance guard). Default 5000.';
$string['filter_course']        = 'Course';
$string['filter_mode']          = 'Mode';
$string['filter_grade']         = 'Grade range';
$string['mode_cluster']         = 'Cluster';
$string['mode_choropleth']      = 'Choropleth (grade)';
$string['mode_hotspot']         = 'Hotspot';
$string['kpi_students']         = 'Students';
$string['kpi_countries']        = 'Countries';
$string['kpi_avg_grade']        = 'Avg. grade';
$string['kpi_courses']          = 'Courses';
$string['all_courses']          = 'All courses';
$string['geoip_missing']        = 'GeoLite2-City.mmdb not found at {$a}. Please download it from https://www.maxmind.com/ and place it in the configured path (or update plugin settings).';
$string['no_data']              = 'No geolocatable users were found. Ensure users have logged in at least once so Moodle has populated the <code>lastip</code> column.';
$string['privacy:metadata']     = 'The GOLDEN plugin reads the existing lastip column of mdl_user and resolves it to coordinates with a local MaxMind GeoLite2 database. No data is sent outside this Moodle server.';
