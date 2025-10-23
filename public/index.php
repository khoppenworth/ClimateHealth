<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Db.php';
require_once __DIR__ . '/../src/Util/Settings.php';

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$settingsStore = new Settings(__DIR__ . '/../config/system_settings.json');
$settings = $settingsStore->all();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token mismatch. Please try again.';
    } else {
        $appTitle = trim($_POST['app_title'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $logoUrl = trim($_POST['logo_url'] ?? '');
        $primaryColor = strtoupper(trim($_POST['primary_color'] ?? ''));
        $accentColor = strtoupper(trim($_POST['accent_color'] ?? ''));
        $dataReference = trim($_POST['data_reference'] ?? '');
        $aboutText = trim($_POST['about_text'] ?? '');
        $defaultMetric = $_POST['default_metric'] ?? $settings['default_metric'];

        $colorPattern = '/^#[0-9A-F]{6}$/';
        if ($primaryColor === '' || !preg_match($colorPattern, $primaryColor)) {
            $errors[] = 'Primary color must be a 6-digit hex value (e.g. #0B3954).';
        }
        if ($accentColor === '' || !preg_match($colorPattern, $accentColor)) {
            $errors[] = 'Accent color must be a 6-digit hex value (e.g. #FF7F11).';
        }

        if ($logoUrl === '') {
            $errors[] = 'Logo URL or path cannot be empty.';
        } else {
            $isRelative = str_starts_with($logoUrl, '/');
            $isHttp = filter_var($logoUrl, FILTER_VALIDATE_URL) !== false;
            if (!$isRelative && !$isHttp) {
                $errors[] = 'Logo must be a valid URL or start with /. e.g. /assets/img/logo.svg';
            }
        }

        $allowedMetrics = ['tmean_c', 'rain_mm'];
        if (!in_array($defaultMetric, $allowedMetrics, true)) {
            $errors[] = 'Default GIS metric is not supported.';
        }

        if (!$errors) {
            try {
                $settingsStore->save([
                    'app_title' => $appTitle ?: $settings['app_title'],
                    'tagline' => $tagline ?: $settings['tagline'],
                    'logo_url' => $logoUrl,
                    'primary_color' => $primaryColor,
                    'accent_color' => $accentColor,
                    'data_reference' => $dataReference ?: $settings['data_reference'],
                    'about_text' => $aboutText ?: $settings['about_text'],
                    'default_metric' => $defaultMetric,
                ]);
                $_SESSION['flash'] = 'Settings were updated successfully.';
                header('Location: index.php');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Unable to save settings: ' . $e->getMessage();
            }
        }
    }
    $settings = $settingsStore->all();
}

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
  <title><?= htmlspecialchars($settings['app_title']); ?> &bull; Climate Intelligence</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet"/>
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" integrity="sha384-mwDYC8p7ulT8rC+XVUKapY3bgZCD0UYh5wZ2L6jciJ6TP2PzefDs2fzGEylh4G6m" crossorigin=""/>
  <link href="/assets/css/style.css" rel="stylesheet"/>
  <style>
    :root {
      --primary-color: <?= htmlspecialchars($settings['primary_color']); ?>;
      --accent-color: <?= htmlspecialchars($settings['accent_color']); ?>;
    }
  </style>
</head>
<body class="app-shell">
  <nav class="app-navbar">
    <div class="nav-wrapper container">
      <a href="index.php" class="brand-logo">
        <img src="<?= htmlspecialchars($settings['logo_url']); ?>" alt="<?= htmlspecialchars($settings['app_title']); ?> logo" class="brand-logo__img"/>
        <span class="brand-logo__text"><?= htmlspecialchars($settings['app_title']); ?></span>
      </a>
      <ul class="right">
        <li><a href="run_job.php?action=ingest" class="nav-link">Run Daily Ingest</a></li>
        <li><a href="run_job.php?action=publish" class="nav-link">Publish to DHIS2</a></li>
        <li><a href="api.php?fn=preview" class="nav-link">Preview Payload</a></li>
        <li><a href="?logout=1" class="nav-link">Logout</a></li>
      </ul>
    </div>
  </nav>

  <header class="hero">
    <div class="container">
      <p class="hero__tagline"><?= htmlspecialchars($settings['tagline']); ?></p>
      <h1 class="hero__title">Climate &amp; Health Operations Dashboard</h1>
      <p class="hero__subtitle"><?= htmlspecialchars($settings['about_text']); ?></p>
    </div>
  </header>

  <main class="container app-main">
    <?php if ($flash): ?>
      <div class="card-panel green lighten-4 green-text text-darken-4"><?= htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="card-panel red lighten-4 red-text text-darken-4">
        <ul class="error-list">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <section class="grid">
      <article class="card elevation">
        <div class="card-content">
          <div class="card-heading">
            <h2>Status Overview</h2>
            <span class="status-pill">Mode: <?= $cfg['publish_dhis2'] ? 'LIVE' : 'DRY-RUN'; ?></span>
          </div>
          <div class="status-grid">
            <div>
              <p class="status-label">Latest Data Date</p>
              <p class="status-value"><?= htmlspecialchars($latest ?: 'No data'); ?></p>
            </div>
            <div>
              <p class="status-label">Dataset UID</p>
              <p class="status-value"><?= htmlspecialchars($cfg['dataset']); ?></p>
            </div>
            <div>
              <p class="status-label">DHIS2 Endpoint</p>
              <p class="status-value truncate"><?= htmlspecialchars($cfg['dhis2_base_url']); ?></p>
            </div>
          </div>
        </div>
        <div class="card-action card-action--stacked">
          <a class="btn btn-primary" href="run_job.php?action=ingest">Run Daily Ingest</a>
          <a class="btn btn-secondary" href="run_job.php?action=publish">Publish to DHIS2</a>
          <a class="btn btn-tertiary" href="api.php?fn=preview">Preview Payload</a>
        </div>
      </article>

      <article class="card elevation">
        <div class="card-content">
          <div class="card-heading">
            <h2>System Maintenance</h2>
            <?php if ($stateUpdated): ?>
              <span class="status-pill status-pill--muted">Updated <?= htmlspecialchars($stateUpdated); ?></span>
            <?php endif; ?>
          </div>
          <p class="section-lead">Manage package versions and keep track of your last install activity.</p>
          <div class="status-grid status-grid--compact">
            <div>
              <p class="status-label">Current version</p>
              <p class="status-value"><?= htmlspecialchars($currentVersion ?: 'Unknown'); ?></p>
            </div>
            <?php if ($previousVersion): ?>
              <div>
                <p class="status-label">Previous version</p>
                <p class="status-value"><?= htmlspecialchars($previousVersion); ?></p>
              </div>
            <?php endif; ?>
            <div>
              <p class="status-label">Last action</p>
              <p class="status-value"><?= htmlspecialchars($lastActionLabel); ?></p>
            </div>
            <div>
              <p class="status-label">Latest backup</p>
              <p class="status-value"><?= htmlspecialchars($latestBackup ?: 'None recorded'); ?></p>
            </div>
          </div>
        </div>
        <div class="card-action card-action--stacked">
          <a class="btn btn-primary" href="run_job.php?action=upgrade">Upgrade package</a>
          <a class="btn btn-tertiary" href="run_job.php?action=downgrade">Downgrade package</a>
        </div>
      </article>

      <article class="card elevation">
        <div class="card-content">
          <h2>Recent Climate Values</h2>
          <p class="section-lead">A quick view of the most recent temperature and precipitation metrics prepared for DHIS2.</p>
          <div class="table-wrapper">
            <table class="striped responsive-table highlight-table">
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
      </article>

      <article class="card elevation">
        <div class="card-content">
          <div class="card-heading">
            <h2>GIS Climate Report</h2>
            <div class="card-heading__actions">
              <a class="btn-flat" href="api.php?fn=gis-report&format=geojson" target="_blank" rel="noopener">Download GeoJSON</a>
            </div>
          </div>
          <p class="section-lead">Spatial distribution of latest climate indicators across monitored health zones.</p>
          <div id="climateMap" data-default-metric="<?= htmlspecialchars($settings['default_metric']); ?>"></div>
          <div id="mapSummary" class="map-summary"></div>
        </div>
      </article>

      <article class="card elevation">
        <div class="card-content">
          <h2>Climate Data Sources</h2>
          <p class="section-lead"><?= htmlspecialchars($settings['data_reference']); ?></p>
          <ul class="data-source-list">
            <li><strong>NASA POWER:</strong> Global solar and meteorological data optimized for sustainable development planning. <a href="https://power.larc.nasa.gov/" target="_blank" rel="noopener">View dataset</a></li>
            <li><strong>Copernicus Climate Data Store:</strong> European Centre for Medium-Range Weather Forecasts climate reanalysis and forecasts. <a href="https://cds.climate.copernicus.eu/" target="_blank" rel="noopener">Explore CDS</a></li>
            <li><strong>NOAA GHCN:</strong> High-quality ground station observations underpinning rainfall and temperature extremes. <a href="https://www.ncei.noaa.gov/products/land-based-station/global-historical-climatology-network" target="_blank" rel="noopener">Read more</a></li>
            <li><strong>WMO Guidelines:</strong> Harmonisation guidance for climate services in health sector decision support. <a href="https://public.wmo.int/en" target="_blank" rel="noopener">WMO resources</a></li>
          </ul>
        </div>
      </article>

      <article class="card elevation">
        <div class="card-content">
          <h2>System Settings</h2>
          <p class="section-lead">Adjust look-and-feel details to align the console with your organisation.</p>
          <form method="post" class="settings-form">
            <input type="hidden" name="action" value="save_settings"/>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>"/>
            <div class="row">
              <div class="input-field col s12 m6">
                <label for="app_title" class="active">Application title</label>
                <input type="text" id="app_title" name="app_title" value="<?= htmlspecialchars($settings['app_title']); ?>"/>
              </div>
              <div class="input-field col s12 m6">
                <label for="tagline" class="active">Tagline</label>
                <input type="text" id="tagline" name="tagline" value="<?= htmlspecialchars($settings['tagline']); ?>"/>
              </div>
            </div>
            <div class="row">
              <div class="input-field col s12 m6">
                <label for="logo_url" class="active">Logo URL or path</label>
                <input type="text" id="logo_url" name="logo_url" value="<?= htmlspecialchars($settings['logo_url']); ?>"/>
              </div>
              <div class="input-field col s6 m3">
                <label for="primary_color" class="active">Primary colour</label>
                <input type="text" id="primary_color" name="primary_color" value="<?= htmlspecialchars($settings['primary_color']); ?>" class="color-input"/>
              </div>
              <div class="input-field col s6 m3">
                <label for="accent_color" class="active">Accent colour</label>
                <input type="text" id="accent_color" name="accent_color" value="<?= htmlspecialchars($settings['accent_color']); ?>" class="color-input"/>
              </div>
            </div>
            <div class="row">
              <div class="input-field col s12 m4">
                <label for="default_metric" class="active">Default GIS metric</label>
                <select id="default_metric" name="default_metric" class="browser-default">
                  <option value="tmean_c" <?= $settings['default_metric'] === 'tmean_c' ? 'selected' : ''; ?>>Mean temperature (°C)</option>
                  <option value="rain_mm" <?= $settings['default_metric'] === 'rain_mm' ? 'selected' : ''; ?>>Rainfall (mm)</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="input-field col s12">
                <label for="data_reference" class="active">Climate data reference text</label>
                <textarea id="data_reference" name="data_reference" class="materialize-textarea"><?= htmlspecialchars($settings['data_reference']); ?></textarea>
              </div>
            </div>
            <div class="row">
              <div class="input-field col s12">
                <label for="about_text" class="active">Hero description</label>
                <textarea id="about_text" name="about_text" class="materialize-textarea"><?= htmlspecialchars($settings['about_text']); ?></textarea>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Save settings</button>
            </div>
          </form>
        </div>
      </article>
    </section>
  </main>

  <footer class="app-footer">
    <div class="container">
      <p>Apache-2.0 • Built for LAMP • Demo only</p>
      <p class="footer-note">Questions? Email the climate services team or review the <a href="https://github.com/" target="_blank" rel="noopener">project documentation</a>.</p>
    </div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha384-VHZ7v6czS4QhIwTZPIYOvTo95OfzmiEJeZVrDCTnhgypKekJy5o+1OtSWT8gKa5z" crossorigin=""></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
