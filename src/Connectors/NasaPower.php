<?php
require_once __DIR__ . '/../Util/Helpers.php';
class NasaPower {
  public static function daily_point($lat, $lon, $startYmd, $endYmd) {
    $params = http_build_query([
      'parameters' => 'T2M,PRECTOT',
      'community' => 'AG',
      'latitude' => $lat,
      'longitude' => $lon,
      'start' => $startYmd,
      'end' => $endYmd,
      'format' => 'JSON'
    ]);
    $url = "https://power.larc.nasa.gov/api/temporal/daily/point?$params";
    return Helpers::http_get_json($url);
  }
}
