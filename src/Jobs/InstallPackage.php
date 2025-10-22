<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Db.php';
require_once __DIR__ . '/BackupDatabase.php';

$action = $argv[1] ?? '';
$targetVersion = $argv[2] ?? '';

if (!in_array($action, ['upgrade', 'downgrade'], true)) {
  echo "Usage: php InstallPackage.php <upgrade|downgrade> [targetVersion]\n";
  exit(1);
}

$cfg = $ENV;
$pdo = (new Db($cfg))->pdo();

$result = BackupDatabase::create($pdo, $cfg);
if (!$result['success']) {
  fwrite(STDERR, "Backup failed: {$result['message']}\n");
  exit(2);
}

echo "[1/2] Backup created at {$result['path']}\n";

$stateFile = __DIR__ . '/../../tmp/system_state.json';
$stateDir = dirname($stateFile);
if (!is_dir($stateDir) && !mkdir($stateDir, 0775, true) && !is_dir($stateDir)) {
  fwrite(STDERR, "Unable to prepare state directory at {$stateDir}\n");
  exit(3);
}

$state = [];
if (is_readable($stateFile)) {
  $rawState = file_get_contents($stateFile);
  if ($rawState !== false) {
    $decoded = json_decode($rawState, true);
    if (is_array($decoded)) {
      $state = $decoded;
    }
  }
}

$currentVersion = $state['version'] ?? '1.0.0';

if ($targetVersion === '') {
  $targetVersion = calculateTargetVersion($currentVersion, $action);
}

$state['previous_version'] = $currentVersion;
$state['version'] = $targetVersion;
$state['last_action'] = $action;
$state['updated_at'] = gmdate('c');

$stateResult = file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
if ($stateResult === false) {
  fwrite(STDERR, "Unable to write state file at {$stateFile}\n");
  exit(4);
}

$logFile = __DIR__ . '/../../tmp/install.log';
$logLine = sprintf("%s\t%s\t%s\tbackup=%s\n", gmdate('c'), strtoupper($action), $targetVersion, basename($result['path']));
$logResult = file_put_contents($logFile, $logLine, FILE_APPEND);

echo "[2/2] " . ucfirst($action) . " completed to version {$targetVersion}.\n";

echo "Previous version was {$currentVersion}. State stored at {$stateFile}.\n";
if ($logResult === false) {
  fwrite(STDERR, "Warning: unable to write log file at {$logFile}\n");
} else {
  echo "Log entry appended to {$logFile}.\n";
}

function calculateTargetVersion(string $currentVersion, string $action): string {
  if (!preg_match('/^(\\d+)\\.(\\d+)\\.(\\d+)$/', $currentVersion, $matches)) {
    return $action === 'upgrade' ? '1.0.0' : '0.0.0';
  }
  [$full, $major, $minor, $patch] = $matches;
  $major = (int)$major;
  $minor = (int)$minor;
  $patch = (int)$patch;

  if ($action === 'upgrade') {
    $patch++;
  } else {
    if ($patch > 0) {
      $patch--;
    } elseif ($minor > 0) {
      $minor--;
      $patch = 9;
    } elseif ($major > 0) {
      $major--;
      $minor = 9;
      $patch = 9;
    }
  }

  return sprintf('%d.%d.%d', $major, $minor, $patch);
}
