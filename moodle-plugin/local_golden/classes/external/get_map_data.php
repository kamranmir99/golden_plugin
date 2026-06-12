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
 * External Service: return geolocated user/grade data for the GOLDEN map.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_golden\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/enrollib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context_system;

/**
 * Webservice: local_golden_get_map_data.
 */
class get_map_data extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT,   'Course id (0 for all courses)', VALUE_DEFAULT, 0),
            'mingrade' => new external_value(PARAM_FLOAT, 'Minimum grade filter (0-100)',  VALUE_DEFAULT, 0),
            'maxgrade' => new external_value(PARAM_FLOAT, 'Maximum grade filter (0-100)',  VALUE_DEFAULT, 100),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int   $courseid
     * @param float $mingrade
     * @param float $maxgrade
     * @return array
     */
    public static function execute($courseid, $mingrade, $maxgrade) {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'mingrade' => $mingrade,
            'maxgrade' => $maxgrade,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/golden:view', $context);

        $warnings = [];
        $points   = [];
        $stats    = [
            'total_students'     => 0,
            'countries_covered'  => 0,
            'avg_grade'          => 0.0,
            'top_countries'      => [],
            'grade_distribution' => self::empty_distribution(),
        ];

        $maxusers = (int)get_config('local_golden', 'max_users');
        if ($maxusers <= 0) {
            $maxusers = 5000;
        }
        $dbpath = get_config('local_golden', 'geoip_path');
        if (empty($dbpath)) {
            $dbpath = $CFG->dirroot . '/local/golden/data/GeoLite2-City.mmdb';
        }

        $resolver = new \local_golden\geoip_resolver($dbpath);
        if (!$resolver->is_ready()) {
            $msg = $resolver->get_error();
            if (empty($msg)) {
                $msg = get_string('geoip_missing', 'local_golden', $dbpath);
            }
            $warnings[] = [
                'item'        => 'geoip',
                'itemid'      => 0,
                'warningcode' => 'geoip_missing',
                'message'     => $msg,
            ];
            return ['points' => $points, 'stats' => $stats, 'warnings' => $warnings];
        }

        $users = \local_golden\data_service::list_users($params['courseid'] ?: null, $maxusers);

        $countrycounts = [];
        $grades        = [];
        $gradedistrib  = array_fill(0, 10, 0);

        foreach ($users as $u) {
            $geo = $resolver->lookup($u['lastip']);
            if (!$geo) {
                continue;
            }

            if ($params['courseid']) {
                $grade = \local_golden\data_service::course_grade((int)$u['id'], (int)$params['courseid']);
            } else {
                $grade = \local_golden\data_service::overall_grade((int)$u['id']);
            }
            if ($grade === null) {
                $grade = 0.0;
            }
            if ($grade < $params['mingrade'] || $grade > $params['maxgrade']) {
                continue;
            }

            $points[] = [
                'id'          => (int)$u['id'],
                'firstname'   => (string)$u['firstname'],
                'lastname'    => (string)$u['lastname'],
                'email'       => (string)$u['email'],
                'ip'          => (string)$u['lastip'],
                'country'     => (string)$geo['country'],
                'countrycode' => (string)$geo['country_code'],
                'city'        => (string)$geo['city'],
                'lat'         => (float)$geo['lat'],
                'lng'         => (float)$geo['lng'],
                'grade'       => (float)$grade,
            ];
            $grades[] = (float)$grade;
            $countrycounts[$geo['country']] = (isset($countrycounts[$geo['country']]) ? $countrycounts[$geo['country']] : 0) + 1;
            $bucket = min(9, (int)floor($grade / 10));
            $gradedistrib[$bucket]++;
        }

        arsort($countrycounts);
        $top = [];
        $i = 0;
        foreach ($countrycounts as $country => $count) {
            $top[] = ['country' => (string)$country, 'students' => (int)$count];
            if (++$i >= 10) {
                break;
            }
        }

        $distribution = [];
        for ($i = 0; $i < 10; $i++) {
            $distribution[] = [
                'range' => ($i * 10) . '-' . ($i * 10 + 9),
                'count' => (int)$gradedistrib[$i],
            ];
        }

        $stats = [
            'total_students'     => count($points),
            'countries_covered'  => count($countrycounts),
            'avg_grade'          => $grades ? round(array_sum($grades) / count($grades), 2) : 0.0,
            'top_countries'      => $top,
            'grade_distribution' => $distribution,
        ];

        return ['points' => $points, 'stats' => $stats, 'warnings' => $warnings];
    }

    /**
     * Returns definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'points' => new external_multiple_structure(
                new external_single_structure([
                    'id'          => new external_value(PARAM_INT,   'User id'),
                    'firstname'   => new external_value(PARAM_TEXT,  'First name'),
                    'lastname'    => new external_value(PARAM_TEXT,  'Last name'),
                    'email'       => new external_value(PARAM_RAW,   'Email'),
                    'ip'          => new external_value(PARAM_RAW,   'Last IP address'),
                    'country'     => new external_value(PARAM_TEXT,  'Country name'),
                    'countrycode' => new external_value(PARAM_TEXT,  'ISO 3166-1 alpha-2 country code'),
                    'city'        => new external_value(PARAM_TEXT,  'City name'),
                    'lat'         => new external_value(PARAM_FLOAT, 'Latitude'),
                    'lng'         => new external_value(PARAM_FLOAT, 'Longitude'),
                    'grade'       => new external_value(PARAM_FLOAT, 'Grade (0-100)'),
                ])
            ),
            'stats' => new external_single_structure([
                'total_students'    => new external_value(PARAM_INT,   'Total geolocated students'),
                'countries_covered' => new external_value(PARAM_INT,   'Distinct countries covered'),
                'avg_grade'         => new external_value(PARAM_FLOAT, 'Mean grade'),
                'top_countries'     => new external_multiple_structure(
                    new external_single_structure([
                        'country'  => new external_value(PARAM_TEXT, 'Country'),
                        'students' => new external_value(PARAM_INT,  'Students in country'),
                    ])
                ),
                'grade_distribution' => new external_multiple_structure(
                    new external_single_structure([
                        'range' => new external_value(PARAM_TEXT, 'Range label'),
                        'count' => new external_value(PARAM_INT,  'Count in bucket'),
                    ])
                ),
            ]),
            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Return an empty 10-bucket distribution skeleton.
     *
     * @return array
     */
    private static function empty_distribution(): array {
        $out = [];
        for ($i = 0; $i < 10; $i++) {
            $out[] = ['range' => ($i * 10) . '-' . ($i * 10 + 9), 'count' => 0];
        }
        return $out;
    }
}
