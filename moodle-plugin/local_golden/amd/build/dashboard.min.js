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
 * GOLDEN admin dashboard AMD module.
 *
 * @module     local_golden/dashboard
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    'use strict';

    var CHOROPLETH = ['#0A2530', '#114659', '#166E87', '#0CB2CC', '#00E5FF'];

    function gradeColor(g) {
        if (g >= 85) { return CHOROPLETH[4]; }
        if (g >= 75) { return CHOROPLETH[3]; }
        if (g >= 65) { return CHOROPLETH[2]; }
        if (g >= 55) { return CHOROPLETH[1]; }
        return CHOROPLETH[0];
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'})[c];
        });
    }

    return {
        /**
         * Initialise the GOLDEN dashboard.
         *
         * @param {Object} config
         * @param {string} config.tileurl Leaflet tile URL template.
         */
        init: function(config) {
            var tileurl = config && config.tileurl
                ? config.tileurl
                : 'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png';

            $(document).ready(function() {
                if (typeof window.L === 'undefined') {
                    Str.get_strings([
                        {key: 'error_prefix', component: 'local_golden'},
                        {key: 'error_leaflet_missing', component: 'local_golden'}
                    ]).done(function(strs) {
                        Notification.alert(strs[0], strs[1]);
                    });
                    return;
                }
                var L = window.L;

                var $root = $('#golden-root');
                if (!$root.length) {
                    return;
                }

                var map = L.map('golden-map', {worldCopyJump: true, zoomControl: true}).setView([20, 10], 2);
                L.tileLayer(tileurl, {subdomains: 'abcd', attribution: '© OpenStreetMap © CARTO'}).addTo(map);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}{r}.png',
                            {subdomains: 'abcd', opacity: 0.9}).addTo(map);

                var clusterLayer    = L.markerClusterGroup ? L.markerClusterGroup({maxClusterRadius: 60}) : L.layerGroup();
                var choroplethLayer = L.layerGroup();
                var heatLayer       = null;
                var currentMode     = 'cluster';
                var currentPoints   = [];

                function popupHtml(p) {
                    return '<div style="min-width:220px">'
                         + '<div style="font-weight:700;font-size:14px;margin-bottom:4px">'
                         +   escapeHtml(p.firstname + ' ' + p.lastname) + '</div>'
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
                    if (map.hasLayer(clusterLayer)) { map.removeLayer(clusterLayer); }
                    if (map.hasLayer(choroplethLayer)) { map.removeLayer(choroplethLayer); }
                    if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }

                    if (currentMode === 'cluster') {
                        clusterLayer = L.markerClusterGroup
                            ? L.markerClusterGroup({maxClusterRadius: 60})
                            : L.layerGroup();
                        currentPoints.forEach(function(p) {
                            var m = L.circleMarker([p.lat, p.lng], {
                                radius: 5, color: '#FFAB00', fillColor: '#FFAB00', fillOpacity: 0.85, weight: 1
                            });
                            m.bindPopup(popupHtml(p));
                            clusterLayer.addLayer(m);
                        });
                        clusterLayer.addTo(map);
                    } else if (currentMode === 'choropleth') {
                        choroplethLayer = L.layerGroup();
                        currentPoints.forEach(function(p) {
                            var c = gradeColor(p.grade);
                            var m = L.circleMarker([p.lat, p.lng], {
                                radius: 6, color: c, fillColor: c, fillOpacity: 0.85, weight: 1
                            });
                            m.bindPopup(popupHtml(p));
                            choroplethLayer.addLayer(m);
                        });
                        choroplethLayer.addTo(map);
                    } else if (currentMode === 'hotspot' && typeof L.heatLayer === 'function') {
                        var heat = currentPoints.map(function(p) {
                            return [p.lat, p.lng, Math.max(0.1, (100 - p.grade) / 100)];
                        });
                        heatLayer = L.heatLayer(heat, {
                            radius: 28, blur: 22, maxZoom: 10, minOpacity: 0.35,
                            gradient: {0.1: '#4A0080', 0.3: '#B20066', 0.5: '#E63900', 0.7: '#FFAB00', 1.0: '#FFEA00'}
                        }).addTo(map);
                    }
                }

                function fetchData() {
                    var courseid = parseInt($('#golden-course').val(), 10) || 0;
                    var mingrade = parseFloat($('#golden-min').val()) || 0;
                    var maxgrade = parseFloat($('#golden-max').val()) || 100;

                    var calls = Ajax.call([{
                        methodname: 'local_golden_get_map_data',
                        args: {courseid: courseid, mingrade: mingrade, maxgrade: maxgrade}
                    }]);

                    calls[0].then(function(response) {
                        if (response.warnings && response.warnings.length) {
                            Str.get_string('error_prefix', 'local_golden').done(function(prefix) {
                                Notification.alert(prefix, response.warnings[0].message);
                            });
                            return;
                        }
                        currentPoints = response.points || [];
                        var s = response.stats || {};

                        $('#kpi-students').text(s.total_students == null ? '—' : s.total_students);
                        $('#kpi-countries').text(s.countries_covered == null ? '—' : s.countries_covered);
                        $('#kpi-avg').text(s.avg_grade == null ? '—' : s.avg_grade);

                        var $ol = $('#golden-top-countries').empty();
                        (s.top_countries || []).forEach(function(c) {
                            $ol.append(
                                '<li><span>' + escapeHtml(c.country)
                              + '</span><span class="count">' + c.students + '</span></li>'
                            );
                        });

                        render();
                        return;
                    }).fail(Notification.exception);
                }

                // Wire up toggle buttons.
                $('.golden-toggle button').on('click', function() {
                    $('.golden-toggle button').removeClass('is-active');
                    $(this).addClass('is-active');
                    currentMode = $(this).data('mode');
                    render();
                });

                // Wire up apply/course controls.
                $('#golden-apply').on('click', fetchData);
                $('#golden-course').on('change', fetchData);

                fetchData();
            });
        }
    };
});
