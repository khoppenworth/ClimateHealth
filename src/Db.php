<?php
class Db {
  private $pdo;
  public function __construct($cfg) {
    $dsn = "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4";
    $this->pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  public function pdo() { return $this->pdo; }
}
