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

if ($fn === 'gis-report') {
  $metric = $_GET['metric'] ?? 'tmean_c';
  $validMetrics = ['tmean_c', 'rain_mm'];
  if (!in_array($metric, $validMetrics, true)) {
    $metric = 'tmean_c';
  }

  $sql = "SELECT ou.id, ou.name, ou.lat, ou.lon, cv.date_utc, cv.tmean_c, cv.rain_mm, cv.source
          FROM org_units ou
          LEFT JOIN (
            SELECT cv1.* FROM climate_values cv1
            JOIN (
              SELECT org_unit_id, MAX(date_utc) AS max_date
              FROM climate_values
              GROUP BY org_unit_id
            ) latest ON latest.org_unit_id = cv1.org_unit_id AND latest.max_date = cv1.date_utc
          ) cv ON cv.org_unit_id = ou.id
          ORDER BY ou.name";

  $rows = $db->query($sql)->fetchAll();

  $features = [];
  foreach ($rows as $row) {
    if ($row['lat'] === null || $row['lon'] === null) {
      continue;
    }
    $value = $row[$metric] !== null ? (float) $row[$metric] : null;
    $colour = $metric === 'tmean_c'
      ? ($value !== null ? getColourForTemperature($value) : '#6c757d')
      : ($value !== null ? getColourForRain($value) : '#6c757d');

    $features[] = [
      'type' => 'Feature',
      'geometry' => [
        'type' => 'Point',
        'coordinates' => [(float)$row['lon'], (float)$row['lat']]
      ],
      'properties' => [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'date_utc' => $row['date_utc'],
        'tmean_c' => $row['tmean_c'] !== null ? (float)$row['tmean_c'] : null,
        'rain_mm' => $row['rain_mm'] !== null ? (float)$row['rain_mm'] : null,
        'source' => $row['source'],
        'colour' => $colour
      ]
    ];
  }

  $format = strtolower($_GET['format'] ?? 'json');
  $payload = [
    'type' => 'FeatureCollection',
    'features' => $features,
    'meta' => [
      'metric' => $metric,
      'generated_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM)
    ]
  ];

  header('Content-Type: application/json');
  if ($format === 'geojson') {
    header('Content-Disposition: attachment; filename="climate-gis-report.geojson"');
  }
  echo json_encode($payload, JSON_PRETTY_PRINT);
  exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown fn']);

function getColourForTemperature(float $value): string
{
  if ($value <= 18) {
    return '#1d4ed8';
  }
  if ($value <= 24) {
    return '#10b981';
  }
  if ($value <= 30) {
    return '#f97316';
  }
  return '#dc2626';
}

function getColourForRain(float $value): string
{
  if ($value <= 1) {
    return '#38bdf8';
  }
  if ($value <= 5) {
    return '#0ea5e9';
  }
  if ($value <= 15) {
    return '#1d4ed8';
  }
  return '#1e3a8a';
}
