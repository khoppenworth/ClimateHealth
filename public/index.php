<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Db.php';

$cfg = $ENV;
$db  = (new Db($cfg))->pdo();

$latest = $db->query("SELECT date_utc FROM climate_values ORDER BY date_utc DESC LIMIT 1")->fetchColumn();
$rows = $db->query("SELECT ou.name, cv.date_utc, cv.tmean_c, cv.rain_mm
                    FROM climate_values cv JOIN org_units ou ON ou.id=cv.org_unit_id
                    ORDER BY cv.date_utc DESC, ou.name ASC LIMIT 50")->fetchAll();

$stateFile = __DIR__ . '/../tmp/system_state.json';
$installState = [];
if (is_readable($stateFile)) {
  $raw = file_get_contents($stateFile);
  if ($raw !== false) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $installState = $decoded;
    }
  }
}
$currentVersion = $installState['version'] ?? '1.0.0';
$previousVersion = $installState['previous_version'] ?? null;
$lastAction = $installState['last_action'] ?? null;
$stateUpdated = $installState['updated_at'] ?? null;
$lastActionLabel = $lastAction ? strtoupper($lastAction) : 'NONE';

$backupDir = __DIR__ . '/../tmp/backups';
$latestBackup = null;
if (is_dir($backupDir)) {
  $backups = glob($backupDir . '/*.sql');
  if ($backups) {
    rsort($backups);
    $latestBackup = basename($backups[0]);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>OpenClimate‑DHIS (PHP/LAMP)</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet"/>
  <link href="/assets/css/style.css" rel="stylesheet"/>
</head>
<body class="blue-grey lighten-5">
  <nav class="blue-grey darken-3">
    <div class="nav-wrapper container">
      <a href="#" class="brand-logo">OpenClimate‑DHIS</a>
      <ul class="right hide-on-med-and-down">
        <li><a href="run_job.php?action=ingest">Run Daily Ingest</a></li>
        <li><a href="run_job.php?action=publish">Publish to DHIS2</a></li>
        <li><a href="api.php?fn=preview">Preview Payload</a></li>
      </ul>
    </div>
  </nav>

  <main class="container" style="margin-top:24px;">
    <div class="card z-depth-2">
      <div class="card-content">
        <span class="card-title">Status</span>
        <p>Latest data date: <b><?= htmlspecialchars($latest ?: 'none'); ?></b></p>
        <p>Dataset: <b><?= htmlspecialchars($cfg['dataset']); ?></b> • DHIS2: <b><?= htmlspecialchars($cfg['dhis2_base_url']); ?></b> • Mode: <b><?= $cfg['publish_dhis2'] ? 'LIVE' : 'DRY‑RUN'; ?></b></p>
      </div>
      <div class="card-action">
        <a class="btn waves-effect blue" href="run_job.php?action=ingest">Run Daily Ingest</a>
        <a class="btn waves-effect green" href="run_job.php?action=publish">Publish to DHIS2</a>
        <a class="btn waves-effect grey" href="api.php?fn=preview">Preview Payload</a>
      </div>
    </div>

    <div class="card">
      <div class="card-content">
        <span class="card-title">Upgrade / Downgrade</span>
        <p>Current version: <b><?= htmlspecialchars($currentVersion); ?></b>
          <?php if ($previousVersion !== null): ?>
            <span class="grey-text text-darken-1">(previous: <?= htmlspecialchars($previousVersion); ?>)</span>
          <?php endif; ?>
        </p>
        <p>Last action: <b><?= htmlspecialchars($lastActionLabel); ?></b>
          <?php if ($stateUpdated): ?>
            <span class="grey-text text-darken-1">on <?= htmlspecialchars($stateUpdated); ?> UTC</span>
          <?php endif; ?>
        </p>
        <p>Latest backup: <b><?= htmlspecialchars($latestBackup ?: 'none yet'); ?></b></p>
        <p class="grey-text text-darken-1" style="margin-top:12px;">A full database backup will be created automatically before the selected operation runs.</p>
        <div class="row" style="margin-top:18px;">
          <form class="col s12 m6" action="run_job.php" method="get" data-install-form data-install-action="upgrade">
            <input type="hidden" name="action" value="upgrade"/>
            <div class="input-field">
              <input id="upgrade_version" name="version" type="text" placeholder="e.g. 1.2.0"/>
              <label for="upgrade_version">Target Version (optional)</label>
              <span class="helper-text">Leave blank to apply the next incremental release.</span>
            </div>
            <button class="btn waves-effect blue" type="submit">Run Upgrade</button>
          </form>
          <form class="col s12 m6" action="run_job.php" method="get" data-install-form data-install-action="downgrade">
            <input type="hidden" name="action" value="downgrade"/>
            <div class="input-field">
              <input id="downgrade_version" name="version" type="text" placeholder="e.g. 1.1.0"/>
              <label for="downgrade_version">Target Version (optional)</label>
              <span class="helper-text">Leave blank to revert to the previous release.</span>
            </div>
            <button class="btn waves-effect orange darken-2" type="submit">Run Downgrade</button>
          </form>
        </div>
      </div>
      <div class="card-action">
        <span class="grey-text text-darken-2">Backups are stored in <code>tmp/backups</code> before each installation.</span>
      </div>
    </div>

    <div class="card">
      <div class="card-content">
        <span class="card-title">Recent Values (top 50)</span>
        <table class="striped responsive-table">
          <thead><tr><th>Org Unit</th><th>Date (UTC)</th><th>Tmean (°C)</th><th>Rain (mm)</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['name']); ?></td>
              <td><?= htmlspecialchars($r['date_utc']); ?></td>
              <td><?= htmlspecialchars($r['tmean_c']); ?></td>
              <td><?= htmlspecialchars($r['rain_mm']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <footer class="page-footer blue-grey darken-3">
    <div class="container">
      <div class="row">
        <div class="col s12">
          <p class="grey-text text-lighten-4">Apache‑2.0 • Built for LAMP • Demo only</p>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
