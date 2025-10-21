<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Db.php';

$fn = $_GET['fn'] ?? 'preview';
$cfg = $ENV;
$db = (new Db($cfg))->pdo();

if ($fn === 'preview') {
  $y = (new DateTimeImmutable('yesterday', new DateTimeZone('UTC')))->format('Ymd');
  $sql = "SELECT cv.*, ou.dhis2_uid FROM climate_values cv
          JOIN org_units ou ON ou.id=cv.org_unit_id WHERE cv.date_utc=?";
  $stmt = $db->prepare($sql);
  $stmt->execute([$y]);
  $rows = $stmt->fetchAll();

  $payload = [
    "dataSet" => $cfg['dataset'],
    "period"  => $y,
    "dataValues" => []
  ];
  foreach ($rows as $r) {
    if ($r['tmean_c'] !== null) {
      $payload['dataValues'][] = [
        "orgUnit" => $r['dhis2_uid'],
        "dataElement" => "TMEAN_C",
        "categoryOptionCombo" => $cfg['default_coc'],
        "value" => round((float)$r['tmean_c'], 2)
      ];
    }
    if ($r['rain_mm'] !== null) {
      $payload['dataValues'][] = [
        "orgUnit" => $r['dhis2_uid'],
        "dataElement" => "RAIN_MM",
        "categoryOptionCombo" => $cfg['default_coc'],
        "value" => round((float)$r['rain_mm'], 1)
      ];
    }
  }
  header('Content-Type: application/json');
  echo json_encode($payload, JSON_PRETTY_PRINT);
  exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown fn']);
