<?php
$action = $_GET['action'] ?? '';
if ($action === 'ingest') {
  passthru('php ' . escapeshellarg(__DIR__ . '/../src/Jobs/DailyIngest.php'));
  exit;
}
if ($action === 'publish') {
  passthru('php ' . escapeshellarg(__DIR__ . '/../src/Jobs/PublishToDhis2.php'));
  exit;
}
http_response_code(400);
echo "Unknown action";
