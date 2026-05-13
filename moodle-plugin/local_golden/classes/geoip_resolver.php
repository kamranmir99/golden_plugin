<?php
// GeoIP resolver for local_golden.
//
// Bundles the official MaxMind\Db\Reader (Apache-2.0) so no Composer is required.
// Pure PHP implementation – works on PHP 7.1+ and PHP 8.x.

namespace local_golden;

defined('MOODLE_INTERNAL') || die();

// Load the bundled MaxMind DB Reader once.
$libdir = __DIR__ . '/../lib/MaxMind/Db';
require_once($libdir . '/Reader.php');
require_once($libdir . '/Reader/Decoder.php');
require_once($libdir . '/Reader/InvalidDatabaseException.php');
require_once($libdir . '/Reader/Metadata.php');
require_once($libdir . '/Reader/Util.php');

class geoip_resolver {

    /** @var string Absolute path to the .mmdb file. */
    private $dbpath;

    /** @var \MaxMind\Db\Reader|null */
    private $reader = null;

    /** @var string Last error message. */
    private $error = '';

    /** @var array In-request cache. */
    private static $cache = [];

    public function __construct(string $dbpath) {
        $this->dbpath = $dbpath;
        $this->init_reader();
    }

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

    public function is_ready(): bool {
        return $this->reader !== null;
    }

    public function get_error(): string {
        return $this->error;
    }

    /**
     * Resolve a single IP to coordinates.
     *
     * @param string $ip
     * @return array|null [lat, lng, country, country_code, country_iso3, city]
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

        $loc      = isset($record['location']) ? $record['location'] : [];
        $country  = isset($record['country']) ? $record['country'] : [];
        $city     = isset($record['city']) ? $record['city'] : [];

        $lat = isset($loc['latitude'])  ? (float)$loc['latitude']  : null;
        $lng = isset($loc['longitude']) ? (float)$loc['longitude'] : null;
        if ($lat === null || $lng === null) {
            return null;
        }

        $cname  = isset($country['names']['en']) ? $country['names']['en'] : 'Unknown';
        $iso2   = isset($country['iso_code'])    ? $country['iso_code']    : 'XX';
        $cityn  = isset($city['names']['en'])    ? $city['names']['en']    : 'Unknown';

        $result = [
            'lat'          => $lat,
            'lng'          => $lng,
            'country'      => $cname,
            'country_code' => $iso2,
            'city'         => $cityn,
        ];
        self::$cache[$ip] = $result;
        return $result;
    }
}
