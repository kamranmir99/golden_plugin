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
 * GOLDEN admin dashboard page.
 *
 * Reads access checks via admin_externalpage_setup, builds the renderable
 * dashboard and renders it through the Mustache template. All client-side
 * behaviour is delegated to the local_golden/dashboard AMD module.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Hooks the page into the admin tree (Site admin → Reports → GOLDEN Map).
admin_externalpage_setup('local_golden_report');

$context = context_system::instance();
require_capability('local/golden:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/golden/index.php'));
$PAGE->set_title(get_string('page_title', 'local_golden'));
$PAGE->set_heading(get_string('pluginname', 'local_golden'));

// Resolve the tile URL (admin setting, fallback to CARTO dark-matter).
$tileurl = get_config('local_golden', 'tile_url');
if (empty($tileurl)) {
    $tileurl = 'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png';
}

// Leaflet + plugins. Moodle aggregates this plugin's own styles.css automatically;
// we only enqueue third-party CDN CSS here.
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'));
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css'));
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css'));
$PAGE->requires->js(new moodle_url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'), true);
$PAGE->requires->js(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js'), true);
$PAGE->requires->js(new moodle_url('https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js'), true);

// Initialise the AMD module with the admin-configured tile URL.
$PAGE->requires->js_call_amd('local_golden/dashboard', 'init', [['tileurl' => $tileurl]]);

// Build the renderable + render through the Mustache template.
$courses    = \local_golden\data_service::list_courses();
$renderable = new \local_golden\output\dashboard($courses);
$renderer   = $PAGE->get_renderer('core');

echo $OUTPUT->header();
echo $renderer->render_from_template('local_golden/dashboard', $renderable->export_for_template($renderer));
echo $OUTPUT->footer();
