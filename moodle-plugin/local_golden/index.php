<?php
// Main GOLDEN dashboard page (admin-only).
//
// @package    local_golden
// @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

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

// Leaflet + plugins from CDN.
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'));
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css'));
$PAGE->requires->css(new moodle_url('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css'));
$PAGE->requires->css(new moodle_url('/local/golden/styles.css'));

$tileurl  = get_config('local_golden', 'tile_url');
if (empty($tileurl)) {
    $tileurl = 'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png';
}
$ajaxurl  = (new moodle_url('/local/golden/ajax.php'))->out(false);
$courses  = \local_golden\data_service::list_courses();

$bootjson = json_encode([
    'ajaxurl'  => $ajaxurl,
    'tileurl'  => $tileurl,
]);

echo $OUTPUT->header();
?>
<div id="golden-root">
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
      <select id="golden-course" class="golden-input">
        <option value="0"><?php echo get_string('all_courses', 'local_golden'); ?></option>
        <?php foreach ($courses as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>"><?php echo s($c['code']); ?> — <?php echo s($c['name']); ?></option>
        <?php endforeach; ?>
      </select>

      <label class="golden-sublabel"><?php echo get_string('filter_mode', 'local_golden'); ?></label>
      <div class="golden-toggle">
        <button type="button" data-mode="cluster" class="is-active"><?php echo get_string('mode_cluster', 'local_golden'); ?></button>
        <button type="button" data-mode="choropleth"><?php echo get_string('mode_choropleth', 'local_golden'); ?></button>
        <button type="button" data-mode="hotspot"><?php echo get_string('mode_hotspot', 'local_golden'); ?></button>
      </div>

      <label class="golden-sublabel"><?php echo get_string('filter_grade', 'local_golden'); ?></label>
      <div class="golden-range">
        <input type="number" id="golden-min" min="0" max="100" value="0" class="golden-input">
        <span>–</span>
        <input type="number" id="golden-max" min="0" max="100" value="100" class="golden-input">
        <button type="button" id="golden-apply" class="golden-btn-primary">Apply</button>
      </div>
    </div>

    <div class="golden-panel">
      <div class="golden-label">// Top countries</div>
      <ol id="golden-top-countries" class="golden-list"></ol>
    </div>
  </aside>
</div>

<div class="golden-credit">
  <span>GOLDEN — maintained by <strong>Kamran Mir</strong>, IGIS, National University of Sciences and Technology (NUST), Islamabad, Pakistan ·
    <a href="mailto:kmir.phd21igis@student.nust.edu.pk">kmir.phd21igis@student.nust.edu.pk</a></span>
</div>

<!-- Leaflet + plugins (loaded synchronously before our init code) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
// GOLDEN dashboard – inline so it always runs after the DOM is parsed.
(function () {
    'use strict';

    var BOOT = <?php echo $bootjson; ?>;

    function init() {
        if (typeof L === 'undefined') {
            alert('GOLDEN: Leaflet failed to load. Check that this server can reach unpkg.com or self-host Leaflet.');
            return;
        }
        if (!document.getElementById('golden-map')) {
            return; // Page partially rendered – nothing to do.
        }

        var map = L.map('golden-map', { worldCopyJump: true, zoomControl: true }).setView([20, 10], 2);
        L.tileLayer(BOOT.tileurl, { subdomains: 'abcd', attribution: '© OpenStreetMap © CARTO' }).addTo(map);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}{r}.png',
                    { subdomains: 'abcd', opacity: 0.9 }).addTo(map);

        var clusterLayer    = L.markerClusterGroup({ maxClusterRadius: 60 });
        var choroplethLayer = L.layerGroup();
        var heatLayer       = null;
        var CHOROPLETH      = ["#0A2530", "#114659", "#166E87", "#0CB2CC", "#00E5FF"];

        function gradeColor(g) {
            if (g >= 85) return CHOROPLETH[4];
            if (g >= 75) return CHOROPLETH[3];
            if (g >= 65) return CHOROPLETH[2];
            if (g >= 55) return CHOROPLETH[1];
            return CHOROPLETH[0];
        }

        var currentMode   = 'cluster';
        var currentPoints = [];

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c];
            });
        }

        function popupHtml(p) {
            return '<div style="min-width:220px">'
                 + '<div style="font-weight:700;font-size:14px;margin-bottom:4px">'
                 +   escapeHtml(p.first_name + ' ' + p.last_name) + '</div>'
                 + '<div style="font-family:monospace;font-size:10px;color:#A1A8B4;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px">'
                 +   escapeHtml(p.city) + ' · ' + escapeHtml(p.country) + '</div>'
                 + '<div style="font-size:12px;line-height:1.7">'
                 +   '<div>IP <span style="float:right;font-family:monospace">' + escapeHtml(p.ip) + '</span></div>'
                 +   '<div>Email <span style="float:right;font-family:monospace">' + escapeHtml(p.email) + '</span></div>'
                 +   '<div>Grade <span style="float:right;font-family:monospace;color:#00E5FF;font-weight:600">'
                 +     Number(p.grade).toFixed(1) + '%</span></div>'
                 + '</div></div>';
        }

        function render() {
            if (map.hasLayer(clusterLayer)) map.removeLayer(clusterLayer);
            if (map.hasLayer(choroplethLayer)) map.removeLayer(choroplethLayer);
            if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }

            if (currentMode === 'cluster') {
                clusterLayer = L.markerClusterGroup({ maxClusterRadius: 60 });
                currentPoints.forEach(function (p) {
                    var m = L.circleMarker([p.lat, p.lng], { radius: 5, color: '#FFAB00', fillColor: '#FFAB00', fillOpacity: 0.85, weight: 1 });
                    m.bindPopup(popupHtml(p));
                    clusterLayer.addLayer(m);
                });
                clusterLayer.addTo(map);
            } else if (currentMode === 'choropleth') {
                choroplethLayer = L.layerGroup();
                currentPoints.forEach(function (p) {
                    var c = gradeColor(p.grade);
                    var m = L.circleMarker([p.lat, p.lng], { radius: 6, color: c, fillColor: c, fillOpacity: 0.85, weight: 1 });
                    m.bindPopup(popupHtml(p));
                    choroplethLayer.addLayer(m);
                });
                choroplethLayer.addTo(map);
            } else if (currentMode === 'hotspot') {
                var heat = currentPoints.map(function (p) {
                    return [p.lat, p.lng, Math.max(0.1, (100 - p.grade) / 100)];
                });
                if (typeof L.heatLayer === 'function') {
                    heatLayer = L.heatLayer(heat, {
                        radius: 28, blur: 22, maxZoom: 10, minOpacity: 0.35,
                        gradient: { 0.1: '#4A0080', 0.3: '#B20066', 0.5: '#E63900', 0.7: '#FFAB00', 1.0: '#FFEA00' }
                    }).addTo(map);
                }
            }
        }

        function setText(id, val) {
            var el = document.getElementById(id);
            if (el) el.textContent = (val == null ? '—' : val);
        }

        function fetchData() {
            var courseEl = document.getElementById('golden-course');
            var minEl    = document.getElementById('golden-min');
            var maxEl    = document.getElementById('golden-max');
            var courseid = courseEl ? courseEl.value : 0;
            var mingrade = minEl ? minEl.value : 0;
            var maxgrade = maxEl ? maxEl.value : 100;

            var url = BOOT.ajaxurl
                    + '?courseid=' + encodeURIComponent(courseid)
                    + '&mingrade=' + encodeURIComponent(mingrade)
                    + '&maxgrade=' + encodeURIComponent(maxgrade);

            fetch(url, { credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) {
                        throw new Error('HTTP ' + r.status + ' from ' + url);
                    }
                    return r.text().then(function (t) {
                        try { return JSON.parse(t); }
                        catch (e) {
                            throw new Error('Server returned non-JSON. First 200 chars: ' + t.substring(0, 200));
                        }
                    });
                })
                .then(function (data) {
                    if (data && data.error) {
                        alert('GOLDEN: ' + (data.message || data.error));
                        return;
                    }
                    currentPoints = (data && data.points) || [];
                    var s = (data && data.stats) || {};
                    setText('kpi-students',  s.total_students);
                    setText('kpi-countries', s.countries_covered);
                    setText('kpi-avg',       s.avg_grade);

                    var ol = document.getElementById('golden-top-countries');
                    if (ol) {
                        ol.innerHTML = '';
                        (s.top_countries || []).forEach(function (c) {
                            var li = document.createElement('li');
                            li.innerHTML = '<span>' + escapeHtml(c.country)
                                         + '</span><span class="count">' + c.students + '</span>';
                            ol.appendChild(li);
                        });
                    }
                    render();
                })
                .catch(function (e) {
                    alert('GOLDEN: failed to load data — ' + (e && e.message ? e.message : e));
                });
        }

        // Wire up controls (all elements are guaranteed to exist – we are inside DOMContentLoaded).
        var toggles = document.querySelectorAll('.golden-toggle button');
        for (var i = 0; i < toggles.length; i++) {
            (function (btn) {
                btn.addEventListener('click', function () {
                    for (var j = 0; j < toggles.length; j++) toggles[j].classList.remove('is-active');
                    btn.classList.add('is-active');
                    currentMode = btn.getAttribute('data-mode');
                    render();
                });
            })(toggles[i]);
        }

        var applyBtn  = document.getElementById('golden-apply');
        var courseSel = document.getElementById('golden-course');
        if (applyBtn)  { applyBtn.addEventListener('click', fetchData); }
        if (courseSel) { courseSel.addEventListener('change', fetchData); }

        fetchData();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
<?php
echo $OUTPUT->footer();
