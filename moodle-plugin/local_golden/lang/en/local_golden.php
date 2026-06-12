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
 * English language strings for local_golden.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']               = 'GOLDEN – Geospatial Model for Distance Education';
$string['golden:view']              = 'View the GOLDEN geospatial report';
$string['nav_golden']               = 'GOLDEN Map';
$string['page_title']               = 'GOLDEN – Student geospatial dashboard';

// Settings.
$string['settings_geoip_path']      = 'GeoLite2 database path';
$string['settings_geoip_path_desc'] = 'Absolute path to the MaxMind GeoLite2-City.mmdb file. Default: {$a}';
$string['settings_tile_url']        = 'Map tile URL template';
$string['settings_tile_url_desc']   = 'Leaflet tile-layer URL. Default is CARTO dark-matter. Use {s},{z},{x},{y} placeholders.';
$string['settings_max_users']       = 'Maximum users to geolocate';
$string['settings_max_users_desc']  = 'Cap on how many users to plot (performance guard). Default 5000.';

// Filter labels.
$string['filter_course']            = 'Course';
$string['filter_mode']              = 'Mode';
$string['filter_grade']             = 'Grade range';
$string['mode_cluster']             = 'Cluster';
$string['mode_choropleth']          = 'Choropleth (grade)';
$string['mode_hotspot']             = 'Hotspot';
$string['all_courses']              = 'All courses';
$string['apply']                    = 'Apply';

// KPIs / labels.
$string['label_metrics']            = '// Key metrics';
$string['label_filters']            = '// Filters';
$string['label_top_countries']      = '// Top countries';
$string['kpi_students']             = 'Students';
$string['kpi_countries']            = 'Countries';
$string['kpi_avg_grade']            = 'Avg. grade';
$string['kpi_courses']              = 'Courses';

// Errors / status.
$string['geoip_missing']            = 'GeoLite2-City.mmdb not found at {$a}. Please download it from https://www.maxmind.com/ and place it in the configured path (or update plugin settings).';
$string['no_data']                  = 'No geolocatable users were found. Ensure users have logged in at least once so Moodle has populated the lastip column.';
$string['error_prefix']             = 'GOLDEN';
$string['error_leaflet_missing']    = 'Leaflet failed to load. Check that this server can reach unpkg.com or self-host Leaflet.';

// Footer / credit.
$string['credit']                   = 'Maintained by {$a->name}, {$a->affiliation} · {$a->email}';
$string['author_name']              = 'Kamran Mir';
$string['author_affiliation']       = 'Institute of Geographical Information Systems (IGIS), National University of Sciences and Technology (NUST), Islamabad, Pakistan';
$string['author_email']             = 'kmir.phd21igis@student.nust.edu.pk';

// Privacy.
$string['privacy:metadata']         = 'The GOLDEN plugin reads the existing lastip column of mdl_user and resolves it to coordinates with a local MaxMind GeoLite2 database. No data is sent outside this Moodle server.';
