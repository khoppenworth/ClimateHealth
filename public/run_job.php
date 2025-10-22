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
if ($action === 'upgrade' || $action === 'downgrade') {
  $version = $_GET['version'] ?? '';
  $script = __DIR__ . '/../src/Jobs/InstallPackage.php';
  $cmd = 'php ' . escapeshellarg($script) . ' ' . escapeshellarg($action);
  if ($version !== '') {
    $cmd .= ' ' . escapeshellarg($version);
  }
  $exitCode = 0;
  passthru($cmd, $exitCode);
  if ($exitCode !== 0) {
    http_response_code(500);
  }
  exit;
}
http_response_code(400);
echo "Unknown action";
