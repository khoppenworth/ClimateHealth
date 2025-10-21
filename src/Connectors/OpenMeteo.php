<?php
require_once __DIR__ . '/../Util/Helpers.php';
class OpenMeteo {
  public static function hourly($lat, $lon, $past_days=2, $forecast_days=3) {
    $params = http_build_query([
      'latitude' => $lat,
      'longitude' => $lon,
      'hourly' => 'temperature_2m,precipitation',
      'past_days' => $past_days,
      'forecast_days' => $forecast_days
    ]);
    $url = "https://api.open-meteo.com/v1/forecast?$params";
    return Helpers::http_get_json($url);
  }
}
