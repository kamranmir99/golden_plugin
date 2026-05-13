<?php
// AJAX endpoint for local_golden – returns JSON payload for the Leaflet map.

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/geoip_resolver.php');
require_once(__DIR__ . '/classes/data_service.php');

require_login();
require_capability('local/golden:view', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$mingrade = optional_param('mingrade', 0, PARAM_FLOAT);
$maxgrade = optional_param('maxgrade', 100, PARAM_FLOAT);

header('Content-Type: application/json; charset=utf-8');

$maxusers = (int)get_config('local_golden', 'max_users') ?: 5000;
$dbpath   = get_config('local_golden', 'geoip_path') ?: ($CFG->dirroot . '/local/golden/data/GeoLite2-City.mmdb');

$resolver = new \local_golden\geoip_resolver($dbpath);
if (!$resolver->is_ready()) {
    $errmsg = $resolver->get_error() ?: get_string('geoip_missing', 'local_golden', $dbpath);
    echo json_encode([
        'error'   => 'geoip_missing',
        'message' => $errmsg,
    ]);
    exit;
}

$users = \local_golden\data_service::list_users($courseid ?: null, $maxusers);

$points          = [];
$countrycounts   = [];
$grades          = [];
$gradedistrib    = array_fill(0, 10, 0);

foreach ($users as $u) {
    $geo = $resolver->lookup($u['lastip']);
    if (!$geo) {
        continue;
    }
    if ($courseid) {
        $grade = \local_golden\data_service::course_grade((int)$u['id'], $courseid);
    } else {
        $grade = \local_golden\data_service::overall_grade((int)$u['id']);
    }
    if ($grade === null) {
        $grade = 0;
    }
    if ($grade < $mingrade || $grade > $maxgrade) {
        continue;
    }

    $points[] = [
        'id'            => $u['id'],
        'first_name'    => $u['firstname'],
        'last_name'     => $u['lastname'],
        'email'         => $u['email'],
        'ip'            => $u['lastip'],
        'country'       => $geo['country'],
        'country_code'  => $geo['country_code'],
        'city'          => $geo['city'],
        'lat'           => $geo['lat'],
        'lng'           => $geo['lng'],
        'grade'         => $grade,
    ];
    $grades[] = $grade;
    $countrycounts[$geo['country']] = ($countrycounts[$geo['country']] ?? 0) + 1;
    $bucket = min(9, (int)floor($grade / 10));
    $gradedistrib[$bucket]++;
}

arsort($countrycounts);
$topcountries = [];
foreach (array_slice($countrycounts, 0, 10, true) as $country => $count) {
    $topcountries[] = ['country' => $country, 'students' => $count];
}

$distribution = [];
for ($i = 0; $i < 10; $i++) {
    $distribution[] = ['range' => ($i * 10) . '-' . ($i * 10 + 9), 'count' => $gradedistrib[$i]];
}

echo json_encode([
    'points'             => $points,
    'stats'              => [
        'total_students'    => count($points),
        'countries_covered' => count($countrycounts),
        'avg_grade'         => $grades ? round(array_sum($grades) / count($grades), 2) : 0,
        'top_countries'     => $topcountries,
        'grade_distribution'=> $distribution,
    ],
]);
