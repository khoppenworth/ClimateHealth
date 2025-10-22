<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Util/Settings.php';

$settingsStore = new Settings(__DIR__ . '/../config/system_settings.json');
$settings = $settingsStore->all();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user'] = $username;
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid credentials. Try admin / admin123.';
}

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($settings['app_title']); ?> • Login</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet"/>
  <link href="/assets/css/style.css" rel="stylesheet"/>
  <style>
    :root {
      --primary-color: <?= htmlspecialchars($settings['primary_color']); ?>;
      --accent-color: <?= htmlspecialchars($settings['accent_color']); ?>;
    }
  </style>
</head>
<body class="login-shell">
  <div class="login-card elevation">
    <div class="login-header">
      <img src="<?= htmlspecialchars($settings['logo_url']); ?>" alt="<?= htmlspecialchars($settings['app_title']); ?> logo" class="login-logo"/>
      <h1><?= htmlspecialchars($settings['app_title']); ?></h1>
      <p><?= htmlspecialchars($settings['tagline']); ?></p>
    </div>
    <?php if ($error): ?>
      <div class="card-panel red lighten-4 red-text text-darken-4 login-error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" class="login-form">
      <div class="input-field">
        <label for="username" class="active">Username</label>
        <input type="text" id="username" name="username" required autofocus/>
      </div>
      <div class="input-field">
        <label for="password" class="active">Password</label>
        <input type="password" id="password" name="password" required/>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>
    <p class="login-disclaimer">Use the demo credentials to explore the administrative console.</p>
  </div>
  <footer class="login-footer">
    <p>Apache-2.0 • Demo credentials: admin / admin123</p>
  </footer>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
