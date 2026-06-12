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
 * GeoIP resolver for local_golden.
 *
 * Bundles the official MaxMind\Db\Reader (Apache-2.0) so no Composer is required.
 * Pure PHP implementation – works on PHP 7.1+ and PHP 8.x.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_golden;

defined('MOODLE_INTERNAL') || die();

// Load the bundled MaxMind DB Reader once.
$libdir = __DIR__ . '/../lib/MaxMind/Db';
require_once($libdir . '/Reader.php');
require_once($libdir . '/Reader/Decoder.php');
require_once($libdir . '/Reader/InvalidDatabaseException.php');
require_once($libdir . '/Reader/Metadata.php');
require_once($libdir . '/Reader/Util.php');

/**
 * Pure-PHP IP → coordinate resolver backed by MaxMind GeoLite2.
 */
class geoip_resolver {

    /** @var string Absolute path to the .mmdb file. */
    private $dbpath;

    /** @var \MaxMind\Db\Reader|null */
    private $reader = null;

    /** @var string Last error message. */
    private $error = '';

    /** @var array In-request cache. */
    private static $cache = [];

    /**
     * Constructor.
     *
     * @param string $dbpath
     */
    public function __construct(string $dbpath) {
        $this->dbpath = $dbpath;
        $this->init_reader();
    }

    /**
     * Try to open the reader; capture any error for the caller.
     */
    private function init_reader() {
        if (!file_exists($this->dbpath)) {
            $this->error = 'File not found: ' . $this->dbpath;
            return;
        }
        if (!is_readable($this->dbpath)) {
            $this->error = 'File not readable (check permissions): ' . $this->dbpath;
            return;
        }
        try {
            $this->reader = new \MaxMind\Db\Reader($this->dbpath);
        } catch (\Throwable $e) {
            $this->error  = 'MaxMind DB error: ' . $e->getMessage();
            $this->reader = null;
        }
    }

    /**
     * @return bool True when the reader is initialised and ready.
     */
    public function is_ready(): bool {
        return $this->reader !== null;
    }

    /**
     * @return string Last error message (empty if ready).
     */
    public function get_error(): string {
        return $this->error;
    }

    /**
     * Resolve a single IP to coordinates.
     *
     * @param string $ip
     * @return array|null
     */
    public function lookup(string $ip) {
        if (empty($ip) || $ip === '0.0.0.0' || $ip === '::1' || $ip === '127.0.0.1') {
            return null;
        }
        if (isset(self::$cache[$ip])) {
            return self::$cache[$ip];
        }
        if (!$this->reader) {
            return null;
        }
        try {
            $record = $this->reader->get($ip);
        } catch (\Throwable $e) {
            return null;
        }
        if (!is_array($record)) {
            return null;
        }

        $loc     = isset($record['location']) ? $record['location'] : [];
        $country = isset($record['country'])  ? $record['country']  : [];
        $city    = isset($record['city'])     ? $record['city']     : [];

        $lat = isset($loc['latitude'])  ? (float)$loc['latitude']  : null;
        $lng = isset($loc['longitude']) ? (float)$loc['longitude'] : null;
        if ($lat === null || $lng === null) {
            return null;
        }

        $result = [
            'lat'          => $lat,
            'lng'          => $lng,
            'country'      => isset($country['names']['en']) ? $country['names']['en'] : 'Unknown',
            'country_code' => isset($country['iso_code']) ? $country['iso_code'] : 'XX',
            'city'         => isset($city['names']['en']) ? $city['names']['en'] : 'Unknown',
        ];
        self::$cache[$ip] = $result;
        return $result;
    }
}
