<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Db.php';
require_once __DIR__ . '/../Dhis2Client.php';

$cfg = $ENV;
$db = (new Db($cfg))->pdo();
$dhis = new Dhis2Client($cfg);
$mappings = require __DIR__ . '/../../config/mappings.php';

$y = (new DateTimeImmutable('yesterday', new DateTimeZone('UTC')))->format('Ymd');
$period = $y;

$sql = "SELECT cv.*, ou.dhis2_uid
        FROM climate_values cv
        JOIN org_units ou ON ou.id = cv.org_unit_id
        WHERE cv.date_utc = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$y]);
$rows = $stmt->fetchAll();

$payload = [
  "dataSet" => $cfg['dataset'],
  "period"  => $period,
  "dataValues" => []
];

foreach ($rows as $r) {
  if ($r['tmean_c'] !== null) {
    $payload['dataValues'][] = [
      "orgUnit" => $r['dhis2_uid'],
      "dataElement" => $mappings['TMEAN_C']['dataElement'],
      "categoryOptionCombo" => $cfg['default_coc'],
      "value" => round((float)$r['tmean_c'], 2)
    ];
  }
  if ($r['rain_mm'] !== null) {
    $payload['dataValues'][] = [
      "orgUnit" => $r['dhis2_uid'],
      "dataElement" => $mappings['RAIN_MM']['dataElement'],
      "categoryOptionCombo" => $cfg['default_coc'],
      "value" => round((float)$r['rain_mm'], 1)
    ];
  }
}

$res = $dhis->postDataValueSets($payload);
echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
