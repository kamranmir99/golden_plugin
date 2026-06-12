# GOLDEN – Geospatial Model for Distance Education

A Moodle local plugin that turns your Moodle instance into a WebGIS. It geolocates every active user from the `lastip` column of `mdl_user` with a local MaxMind GeoLite2 database, then renders a Leaflet map with three views:

1. **Cluster** — every student as a clustered marker.
2. **Choropleth** — students coloured by their overall grade (or a single course grade).
3. **Hotspot** — kernel-density heatmap weighted by inverse grade, to surface regions where learners need academic support.

All processing is performed locally; **no data ever leaves your Moodle server**.

---

## Repository naming convention

When publishing the source to GitHub (or any VCS), name the repository:

```
moodle-local_golden
```

This follows the official Moodle pattern `moodle-{plugintype}_{pluginname}`. The directory inside Moodle remains `local/golden/` and the plugin component remains `local_golden` — only the **repository** name uses the dashed prefix.

## Requirements

- Moodle **3.8** or newer
- PHP **7.1** or newer (PHP **7.4 / 8.x** also supported)
- A MaxMind **GeoLite2-City.mmdb** file (free, registration required at <https://www.maxmind.com/>)

The official MaxMind DB reader library (Apache 2.0) is **bundled** with the plugin under `lib/MaxMind/` — no Composer step is required.

## Installation

1. Unzip `local_golden.zip` into the Moodle codebase under `<MOODLE_ROOT>/local/golden/`.
2. Visit **Site administration → Notifications** so Moodle runs the upgrade.
3. Download `GeoLite2-City.mmdb` from MaxMind and place it at `<MOODLE_ROOT>/local/golden/data/GeoLite2-City.mmdb` (or override the path in plugin settings).
4. Visit **Site administration → Reports → GOLDEN Map**.

## Capability

`local/golden:view` — granted to the **Manager** archetype by default.

## Architecture

| Layer | Files |
|---|---|
| Page entry | `index.php` (data-fetch + access + render only) |
| Output API | `classes/output/dashboard.php` (renderable + templatable) |
| Template | `templates/dashboard.mustache` (all HTML markup) |
| AMD module | `amd/src/dashboard.js` + `amd/build/dashboard.min.js` |
| External Service | `classes/external/get_map_data.php` + `db/services.php` |
| Domain | `classes/data_service.php`, `classes/geoip_resolver.php` |
| Privacy | `classes/privacy/provider.php` (null_provider) |

The browser calls the External Service via `core/ajax` — no custom AJAX endpoint, no bespoke JSON wire format.

## Privacy

Only `mdl_user.lastip`, `mdl_course` records and grades obtained through the standard grading API are read. Geolocation runs locally via MaxMind GeoLite2. No outbound network requests to third parties.

## License

GPL v3 or later — see `LICENSE`.

## Author / Maintainer

**Kamran Mir**  
Institute of Geographical Information Systems (IGIS)  
National University of Sciences and Technology (NUST)  
Islamabad, Pakistan  
✉ <kmir.phd21igis@student.nust.edu.pk>
