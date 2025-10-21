<?php
$ENV = require __DIR__ . '/env.php';
if (!$ENV) { die("Missing config/env.php. Copy from config/sample.env.php"); }
date_default_timezone_set('UTC');
