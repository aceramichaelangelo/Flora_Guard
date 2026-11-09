<?php
declare(strict_types=1);

require_once __DIR__ . '/data_repository.php';

/**
 * Barangays of Talaingod, Davao del Norte, with bounding boxes [w, s, e, n]
 * from Mapbox geocoding (used to frame the map when a barangay has no tags yet).
 */
const TG_TALAINGOD_BARANGAYS = [
    'Dagohoy' => [125.415465, 7.645364, 125.64177, 7.877525],
    'Palma Gil' => [125.424375, 7.622051, 125.642129, 7.734495],
    'Santo Niño' => [125.300924, 7.565047, 125.642129, 7.667076],
];

const TG_BARANGAY_GEOCODE_BATCH = 25;

function tg_barangay_cache_file(): string
{
    return dirname(__DIR__) . '/cache/barangay_geocode.json';
}

/** @return array<string, array{barangay:string, municipality:string}> */
function tg_barangay_cache_load(): array
{
    $raw = @file_get_contents(tg_barangay_cache_file());
    $data = $raw !== false ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}

function tg_barangay_cache_save(array $cache): void
{
    $file = tg_barangay_cache_file();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($file, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

/** ~11 m grid, so repeat scans of the same tree share one lookup. */
function tg_barangay_cache_key(float $lat, float $lng): string
{
    return sprintf('%.4f,%.4f', $lat, $lng);
}

function tg_barangay_mapbox_token(): string
{
    $e = getenv('MAPBOX_ACCESS_TOKEN');
    if (is_string($e) && $e !== '') {
        return $e;
    }
    return defined('MAPBOX_ACCESS_TOKEN') ? (string)MAPBOX_ACCESS_TOKEN : '';
}

/** The public token is URL-restricted, so send the same origin the browser map uses. */
function tg_barangay_referer(): string
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return 'http://localhost/';
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . $host . '/';
}

/**
 * @param array<string, array{0:float,1:float}> $points cache key => [lat, lng]
 * @return array<string, array{barangay:string, municipality:string}> only successful lookups
 */
function tg_barangay_reverse_geocode(array $points): array
{
    $token = tg_barangay_mapbox_token();
    if ($points === [] || strncmp($token, 'pk.', 3) !== 0 || !function_exists('curl_multi_init')) {
        return [];
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($points as $key => [$lat, $lng]) {
        $url = sprintf(
            'https://api.mapbox.com/geocoding/v5/mapbox.places/%F,%F.json?types=locality,place&access_token=%s',
            $lng,
            $lat,
            rawurlencode($token)
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => ['Referer: ' . tg_barangay_referer()],
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $status === CURLM_OK);

    $out = [];
    foreach ($handles as $key => $ch) {
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $body = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        if ($code !== 200 || !is_string($body)) {
            continue;
        }
        $json = json_decode($body, true);
        if (!is_array($json) || !isset($json['features']) || !is_array($json['features'])) {
            continue;
        }

        $barangay = '';
        $municipality = '';
        foreach ($json['features'] as $f) {
            $types = (array)($f['place_type'] ?? []);
            if (in_array('locality', $types, true)) {
                $barangay = (string)($f['text'] ?? '');
                // Cities like Davao are modelled as regions, so fall back up the context chain.
                foreach (['place.', 'region.'] as $prefix) {
                    foreach ((array)($f['context'] ?? []) as $ctx) {
                        if ($municipality === '' && str_starts_with((string)($ctx['id'] ?? ''), $prefix)) {
                            $municipality = (string)($ctx['text'] ?? '');
                        }
                    }
                }
            } elseif (in_array('place', $types, true) && $municipality === '') {
                $municipality = (string)($f['text'] ?? '');
            }
        }
        $out[$key] = ['barangay' => $barangay, 'municipality' => $municipality];
    }
    curl_multi_close($mh);

    return $out;
}

/**
 * Fill `barangay` / `municipality` on records that don't carry them, from their coordinates.
 *
 * @param list<array<string,mixed>> $records
 * @return list<array<string,mixed>>
 */
function tg_attach_barangays(array $records): array
{
    $cache = tg_barangay_cache_load();
    $missing = [];
    foreach ($records as $r) {
        if (trim((string)($r['barangay'] ?? '')) !== '') {
            continue;
        }
        $ll = tg_record_lat_lng($r);
        if ($ll === null) {
            continue;
        }
        $key = tg_barangay_cache_key($ll[0], $ll[1]);
        if (!isset($cache[$key]) && count($missing) < TG_BARANGAY_GEOCODE_BATCH) {
            $missing[$key] = $ll;
        }
    }

    if ($missing !== []) {
        $found = tg_barangay_reverse_geocode($missing);
        if ($found !== []) {
            $cache = $found + $cache;
            tg_barangay_cache_save($cache);
        }
    }

    foreach ($records as $i => $r) {
        if (trim((string)($r['barangay'] ?? '')) !== '') {
            continue;
        }
        $ll = tg_record_lat_lng($r);
        $hit = $ll !== null ? ($cache[tg_barangay_cache_key($ll[0], $ll[1])] ?? null) : null;
        if ($hit === null || $hit['barangay'] === '') {
            continue;
        }
        $records[$i]['barangay'] = $hit['barangay'];
        if (trim((string)($r['municipality'] ?? '')) === '' && $hit['municipality'] !== '') {
            $records[$i]['municipality'] = $hit['municipality'];
        }
    }

    return $records;
}
