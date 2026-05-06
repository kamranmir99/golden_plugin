<?php
// Main GOLDEN dashboard page (admin-only).

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/golden:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/golden/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('page_title', 'local_golden'));
$PAGE->set_heading(get_string('pluginname', 'local_golden'));

// Leaflet + plugins from CDN.
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'));
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css'));
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css'));
$PAGE->requires->css(new moodle_url('/local/golden/styles.css'));
$PAGE->requires->js(new moodle_url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'), true);
$PAGE->requires->js(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js'), true);
$PAGE->requires->js(new moodle_url('https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js'), true);

$tileurl  = get_config('local_golden', 'tile_url') ?: 'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png';
$ajaxurl  = (new moodle_url('/local/golden/ajax.php'))->out(false);
$courses  = \local_golden\data_service::list_courses();

$bootdata = [
    'ajaxurl'  => $ajaxurl,
    'tileurl'  => $tileurl,
    'courses'  => $courses,
    'strings'  => [
        'all_courses'   => get_string('all_courses', 'local_golden'),
        'mode_cluster'  => get_string('mode_cluster', 'local_golden'),
        'mode_choropleth' => get_string('mode_choropleth', 'local_golden'),
        'mode_hotspot'  => get_string('mode_hotspot', 'local_golden'),
        'kpi_students'  => get_string('kpi_students', 'local_golden'),
        'kpi_countries' => get_string('kpi_countries', 'local_golden'),
        'kpi_avg_grade' => get_string('kpi_avg_grade', 'local_golden'),
        'filter_course' => get_string('filter_course', 'local_golden'),
        'filter_mode'   => get_string('filter_mode', 'local_golden'),
        'filter_grade'  => get_string('filter_grade', 'local_golden'),
        'no_data'       => get_string('no_data', 'local_golden'),
    ],
];

echo $OUTPUT->header();
?>
<div id="golden-root" data-boot='<?php echo s(json_encode($bootdata)); ?>'>
  <div class="golden-map" id="golden-map"></div>
  <aside class="golden-sidebar">
    <div class="golden-panel">
      <div class="golden-label">// Key metrics</div>
      <div class="golden-kpis">
        <div><span class="golden-kpi-label"><?php echo get_string('kpi_students', 'local_golden'); ?></span><span class="golden-kpi" id="kpi-students">—</span></div>
        <div><span class="golden-kpi-label"><?php echo get_string('kpi_countries', 'local_golden'); ?></span><span class="golden-kpi" id="kpi-countries">—</span></div>
        <div><span class="golden-kpi-label"><?php echo get_string('kpi_avg_grade', 'local_golden'); ?></span><span class="golden-kpi golden-accent" id="kpi-avg">—</span></div>
        <div><span class="golden-kpi-label"><?php echo get_string('kpi_courses', 'local_golden'); ?></span><span class="golden-kpi" id="kpi-courses"><?php echo count($courses); ?></span></div>
      </div>
    </div>

    <div class="golden-panel">
      <div class="golden-label">// Filters</div>
      <label class="golden-sublabel"><?php echo get_string('filter_course', 'local_golden'); ?></label>
      <select id="golden-course" class="golden-input"><option value="0"><?php echo get_string('all_courses', 'local_golden'); ?></option>
        <?php foreach ($courses as $c): ?>
          <option value="<?php echo $c['id']; ?>"><?php echo s($c['code']); ?> — <?php echo s($c['name']); ?></option>
        <?php endforeach; ?>
      </select>

      <label class="golden-sublabel"><?php echo get_string('filter_mode', 'local_golden'); ?></label>
      <div class="golden-toggle">
        <button data-mode="cluster" class="is-active"><?php echo get_string('mode_cluster', 'local_golden'); ?></button>
        <button data-mode="choropleth"><?php echo get_string('mode_choropleth', 'local_golden'); ?></button>
        <button data-mode="hotspot"><?php echo get_string('mode_hotspot', 'local_golden'); ?></button>
      </div>

      <label class="golden-sublabel"><?php echo get_string('filter_grade', 'local_golden'); ?></label>
      <div class="golden-range">
        <input type="number" id="golden-min" min="0" max="100" value="0" class="golden-input">
        <span>–</span>
        <input type="number" id="golden-max" min="0" max="100" value="100" class="golden-input">
        <button id="golden-apply" class="golden-btn-primary">Apply</button>
      </div>
    </div>

    <div class="golden-panel">
      <div class="golden-label">// Top countries</div>
      <ol id="golden-top-countries" class="golden-list"></ol>
    </div>
  </aside>
</div>
<script src="<?php echo new moodle_url('/local/golden/amd/src/map.js'); ?>"></script>
<div class="golden-credit">
  <span>GOLDEN — maintained by <strong>Kamran Mir</strong>, IGIS, National University of Sciences and Technology (NUST), Islamabad, Pakistan ·
    <a href="mailto:kmir.phd21igis@student.nust.edu.pk">kmir.phd21igis@student.nust.edu.pk</a></span>
</div>
<?php
echo $OUTPUT->footer();
