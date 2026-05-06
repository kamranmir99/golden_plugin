# GOLDEN – Product Requirements Document

## Original Problem Statement
Build a Moodle plugin ("GOLDEN" – **G**eospatial m**O**de**L** for **D**istance **E**ducatio**N**) that reads users' IP addresses from the Moodle users table and generates a WebGIS map (Leaflet) showing student clusters, per-course filters, thematic maps by overall grade or specific-course grade, and hotspot maps. Admin-only. Intended to be uploadable to the Moodle plugin repo.

## User Choices
- Deliverable: **Both** the Moodle PHP plugin package AND a live demo React + FastAPI web app.
- IP → coordinates: **MaxMind GeoLite2 (local .mmdb)**.
- Plugin type: **local plugin** (`local_golden`).
- Features: cluster, per-course filter, choropleth by grade (overall + per-course), hotspot.
- Moodle target: **4.3+**.

## Architecture
- **Moodle plugin** `/app/moodle-plugin/local_golden/` (PHP) → packed as `/app/frontend/public/local_golden.zip`.
  - `version.php`, `lib.php`, `settings.php`, `db/access.php`, `lang/en/`, `index.php`, `ajax.php`, `classes/{geoip_resolver, data_service}.php`, `amd/src/map.js`, `styles.css`.
  - Capability `local/golden:view` (manager role). Uses MaxMind GeoLite2 via `geoip2/geoip2` composer lib (fallback if missing).
- **Demo backend** (FastAPI + MongoDB). Seeds 450 synthetic students across 50 city anchors and 8 courses.
  - Endpoints: `/api/courses`, `/api/students`, `/api/stats`, `/api/hotspots`, `/api/plugin-info`.
- **Demo frontend** (React + react-leaflet + MarkerCluster + leaflet.heat + recharts).
  - `/` — marketing landing + install steps + plugin download.
  - `/dashboard` — full-screen Leaflet dashboard with glassmorphism panels.
- **Design system**: dark GIS-pro (cyan #00E5FF + amber #FFAB00), Chivo + IBM Plex Sans + IBM Plex Mono.

## Done (2026-02-06)
- Complete Moodle plugin with all features, zipped for download.
- Demo web app landing page and interactive dashboard (cluster, choropleth, hotspot modes).
- Course filter, grade-range slider, CSV export, top-countries + distribution analytics.
- Seeded MongoDB with realistic geo-distributed student data.

## Done (2026-02-06 — iter 2)
- **Country-polygon choropleth**: 177-country GeoJSON layer (Natural Earth 110m) shaded by grade. Hover tooltip shows country + students + avg grade.
- **Drill-through**: Click a country polygon OR a "Top countries" list item to open a Sheet with a Table of all students in that country (name / city / IP / grade).
- **Date-range cohort filter**: shadcn Calendar Popover with two-month range mode, integrated with `last_access_from/to` query params on every endpoint.
- **Moodle CI**: `.github/workflows/moodle-ci.yml` runs `moodle-plugin-ci` (codechecker, phpunit, behat, validate, phpdoc, mustache, grunt) across PHP 8.1/8.2 × Moodle 4.3/4.4/main.
- **Privacy provider** added (`local_golden\privacy\provider` as `null_provider`) for plugin-checker compliance.
- Plugin version bumped to `2026020200`. Repackaged ZIP.

## Backlog
- P1: add admin authentication to the demo dashboard.
- P2: drill from polygon-hover into hotspot break-down.
- P2: support IPv6 + proxy header trust list configurable in plugin settings.
- P3: migrate FastAPI startup event → lifespan handler.
- P3: replace string-comparison date filter with stored datetime values.
