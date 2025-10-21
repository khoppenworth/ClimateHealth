<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Db.php';
require_once __DIR__ . '/../Connectors/NasaPower.php';

$cfg = $ENV;
$db = (new Db($cfg))->pdo();

$orgs = $db->query("SELECT id, name, dhis2_uid, lat, lon FROM org_units")->fetchAll();

$today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$start = $today->sub(new DateInterval('P7D'))->format('Ymd');
$end   = $today->sub(new DateInterval('P1D'))->format('Ymd');

foreach ($orgs as $ou) {
  $lat = $ou['lat']; $lon = $ou['lon'];
  try {
    $power = NasaPower::daily_point($lat, $lon, $start, $end);
    if (!isset($power['properties']['parameter']['T2M'])) continue;
    $params = $power['properties']['parameter'];
    $dates = array_keys($params['T2M']);
    foreach ($dates as $d) {
      $tmean = $params['T2M'][$d];       // °C
      $rain  = $params['PRECTOT'][$d] ?? null;   // mm/day
      $stmt = $db->prepare("REPLACE INTO climate_values
        (org_unit_id, date_utc, tmean_c, rain_mm, source) VALUES (?,?,?,?,?)");
      $stmt->execute([$ou['id'], $d, $tmean, $rain, 'NASA_POWER']);
    }
  } catch (Exception $e) {
    error_log("POWER failed for {$ou['name']}: " . $e->getMessage());
  }
}
echo "DailyIngest finished
";
