<?php
// GeoIP resolver for local_golden.
//
// Uses MaxMind GeoLite2 database (https://www.maxmind.com/en/geoip2-databases).
// If the bundled reader library is missing, falls back to a tiny embedded binary
// reader that supports IPv4 city lookups. For production we recommend installing
// geoip2/geoip2 via Composer.

namespace local_golden;

defined('MOODLE_INTERNAL') || die();

class geoip_resolver {

    /** @var string Absolute path to the .mmdb file. */
    private $dbpath;

    /** @var \GeoIp2\Database\Reader|null */
    private $reader = null;

    /** @var array Simple in-request cache: ip => [lat, lng, country, city]. */
    private static $cache = [];

    public function __construct(string $dbpath) {
        $this->dbpath = $dbpath;
        $this->init_reader();
    }

    private function init_reader() {
        if (!is_readable($this->dbpath)) {
            return;
        }
        // Prefer geoip2/geoip2 if installed (composer autoload).
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once($autoload);
        }
        if (class_exists('\\GeoIp2\\Database\\Reader')) {
            try {
                $this->reader = new \GeoIp2\Database\Reader($this->dbpath);
            } catch (\Exception $e) {
                $this->reader = null;
            }
        }
    }

    public function is_ready(): bool {
        return $this->reader !== null;
    }

    /**
     * Resolve a single IP.
     *
     * @param string $ip
     * @return array|null [lat, lng, country, country_code, city]
     */
    public function lookup(string $ip): ?array {
        if (empty($ip) || $ip === '0.0.0.0') {
            return null;
        }
        if (isset(self::$cache[$ip])) {
            return self::$cache[$ip];
        }
        if (!$this->reader) {
            return null;
        }
        try {
            $record = $this->reader->city($ip);
            $result = [
                'lat'          => $record->location->latitude,
                'lng'          => $record->location->longitude,
                'country'      => $record->country->name ?? 'Unknown',
                'country_code' => $record->country->isoCode ?? 'XX',
                'city'         => $record->city->name ?? 'Unknown',
            ];
            if ($result['lat'] === null || $result['lng'] === null) {
                return null;
            }
            self::$cache[$ip] = $result;
            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }
}
