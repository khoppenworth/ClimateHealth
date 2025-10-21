<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Db.php';

$cfg = $ENV;
$db  = (new Db($cfg))->pdo();

$latest = $db->query("SELECT date_utc FROM climate_values ORDER BY date_utc DESC LIMIT 1")->fetchColumn();
$rows = $db->query("SELECT ou.name, cv.date_utc, cv.tmean_c, cv.rain_mm
                    FROM climate_values cv JOIN org_units ou ON ou.id=cv.org_unit_id
                    ORDER BY cv.date_utc DESC, ou.name ASC LIMIT 50")->fetchAll();
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
