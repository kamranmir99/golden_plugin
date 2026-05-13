GOLDEN – Geospatial Model for Distance Education
A Moodle local plugin that turns your Moodle instance into a WebGIS. It geolocates every active user from the lastip column of mdl_user with a local MaxMind GeoLite2 database, then renders a Leaflet map with three views:

Cluster — every student as a clustered marker.
Choropleth — students coloured by their overall grade (or a single course grade).
Hotspot — kernel-density heatmap weighted by inverse grade, to surface regions where learners need academic support.
All processing is performed locally; no data ever leaves your Moodle server.# Here are your Instructions

Requirements
Moodle 3.8 or newer
PHP 7.1 or newer (PHP 7.4 / 8.x also supported)
A MaxMind GeoLite2-City.mmdb file (free, registration required at https://www.maxmind.com/)
The official MaxMind DB reader library (Apache 2.0) is bundled with the plugin under lib/MaxMind/ — no Composer step is required.

Installation
Unzip local_golden.zip into the Moodle codebase under:

<MOODLE_ROOT>/local/golden/
Visit Site administration → Notifications so Moodle runs the upgrade.

Download GeoLite2-City.mmdb from MaxMind and place it at:

<MOODLE_ROOT>/local/golden/data/GeoLite2-City.mmdb
Or configure a custom path in Site administration → Plugins → Local plugins → GOLDEN.

(Recommended) Install the official reader:

That's it — the MaxMind DB Reader is bundled with the plugin, no Composer step required.

Visit Site administration → Reports → GOLDEN Map.

Capability
local/golden:view — granted to the Manager archetype by default. Grant it to any role you want to expose the dashboard to.

Privacy
The plugin only reads mdl_user.lastip, mdl_course and the grade API. Geolocation is resolved locally via MaxMind GeoLite2. No outbound network requests are made to third-party services.

License
GPL v3 or later, matching Moodle.

Author / Maintainer
Kamran Mir Institute of Geographical Information Systems (IGIS) National University of Sciences and Technology (NUST) Islamabad, Pakistan ✉ kmir.phd21igis@student.nust.edu.pk
