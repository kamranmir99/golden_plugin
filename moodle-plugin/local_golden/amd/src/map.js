// GOLDEN Leaflet dashboard — runs on a Moodle admin page.
(function () {
  const root = document.getElementById('golden-root');
  if (!root) return;
  const boot = JSON.parse(root.dataset.boot);

  const map = L.map('golden-map', { worldCopyJump: true, zoomControl: true }).setView([20, 10], 2);
  L.tileLayer(boot.tileurl, { subdomains: 'abcd', attribution: '© OpenStreetMap © CARTO' }).addTo(map);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', opacity: 0.9 }).addTo(map);

  let clusterLayer = L.markerClusterGroup({ maxClusterRadius: 60 });
  let choroplethLayer = L.layerGroup();
  let heatLayer = null;
  const CHOROPLETH = ["#0A2530", "#114659", "#166E87", "#0CB2CC", "#00E5FF"];
  function gradeColor(g) {
    if (g >= 85) return CHOROPLETH[4];
    if (g >= 75) return CHOROPLETH[3];
    if (g >= 65) return CHOROPLETH[2];
    if (g >= 55) return CHOROPLETH[1];
    return CHOROPLETH[0];
  }

  let currentMode = 'cluster';
  let currentPoints = [];

  function render() {
    [clusterLayer, choroplethLayer].forEach(l => map.removeLayer(l));
    if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }

    if (currentMode === 'cluster') {
      clusterLayer = L.markerClusterGroup({ maxClusterRadius: 60 });
      currentPoints.forEach(p => {
        const m = L.circleMarker([p.lat, p.lng], { radius: 5, color: '#FFAB00', fillColor: '#FFAB00', fillOpacity: 0.85, weight: 1 });
        m.bindPopup(popupHtml(p));
        clusterLayer.addLayer(m);
      });
      clusterLayer.addTo(map);
    } else if (currentMode === 'choropleth') {
      choroplethLayer = L.layerGroup();
      currentPoints.forEach(p => {
        const c = gradeColor(p.grade);
        const m = L.circleMarker([p.lat, p.lng], { radius: 6, color: c, fillColor: c, fillOpacity: 0.85, weight: 1 });
        m.bindPopup(popupHtml(p));
        choroplethLayer.addLayer(m);
      });
      choroplethLayer.addTo(map);
    } else if (currentMode === 'hotspot') {
      const heat = currentPoints.map(p => [p.lat, p.lng, Math.max(0.1, (100 - p.grade) / 100)]);
      heatLayer = L.heatLayer(heat, {
        radius: 28, blur: 22, maxZoom: 10, minOpacity: 0.35,
        gradient: { 0.1: '#4A0080', 0.3: '#B20066', 0.5: '#E63900', 0.7: '#FFAB00', 1.0: '#FFEA00' }
      }).addTo(map);
    }
  }

  function popupHtml(p) {
    return '<div style="min-width:220px">'
         + '<div style="font-weight:700;font-size:14px;margin-bottom:4px">' + escapeHtml(p.first_name + ' ' + p.last_name) + '</div>'
         + '<div style="font-family:monospace;font-size:10px;color:#A1A8B4;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px">' + escapeHtml(p.city) + ' · ' + escapeHtml(p.country) + '</div>'
         + '<div style="font-size:12px;line-height:1.7">'
         + '<div>IP <span style="float:right;font-family:monospace">' + escapeHtml(p.ip) + '</span></div>'
         + '<div>Email <span style="float:right;font-family:monospace">' + escapeHtml(p.email) + '</span></div>'
         + '<div>Grade <span style="float:right;font-family:monospace;color:#00E5FF;font-weight:600">' + Number(p.grade).toFixed(1) + '%</span></div>'
         + '</div></div>';
  }

  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }

  function fmt(n) { return n === null || n === undefined ? '—' : n; }

  function fetchData() {
    const courseid = document.getElementById('golden-course').value;
    const mingrade = document.getElementById('golden-min').value;
    const maxgrade = document.getElementById('golden-max').value;
    const url = boot.ajaxurl + '?courseid=' + encodeURIComponent(courseid) + '&mingrade=' + encodeURIComponent(mingrade) + '&maxgrade=' + encodeURIComponent(maxgrade);
    fetch(url, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        if (data.error) { alert(data.message); return; }
        currentPoints = data.points || [];
        const s = data.stats || {};
        document.getElementById('kpi-students').textContent   = fmt(s.total_students);
        document.getElementById('kpi-countries').textContent  = fmt(s.countries_covered);
        document.getElementById('kpi-avg').textContent        = fmt(s.avg_grade);

        const ol = document.getElementById('golden-top-countries');
        ol.innerHTML = '';
        (s.top_countries || []).forEach(c => {
          const li = document.createElement('li');
          li.innerHTML = '<span>' + escapeHtml(c.country) + '</span><span class="count">' + c.students + '</span>';
          ol.appendChild(li);
        });
        render();
      })
      .catch(e => alert('GOLDEN: failed to load data (' + e + ')'));
  }

  // Events
  document.querySelectorAll('.golden-toggle button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.golden-toggle button').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      currentMode = btn.dataset.mode;
      render();
    });
  });
  document.getElementById('golden-apply').addEventListener('click', fetchData);
  document.getElementById('golden-course').addEventListener('change', fetchData);

  fetchData();
})();
