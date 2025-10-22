<?php

class BackupDatabase {
  public static function create(PDO $pdo, array $cfg) {
    $dbName = $cfg['db_name'] ?? 'database';
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $dbName);
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd_His');
    $baseDir = __DIR__ . '/../../tmp/backups';
    if (!is_dir($baseDir)) {
      if (!mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
        return [
          'success' => false,
          'message' => "Unable to create backup directory at {$baseDir}"
        ];
      }
    }
    $filePath = sprintf('%s/%s_%s.sql', $baseDir, $safeName, $timestamp);
    $header = sprintf("-- Backup for %s created at %s UTC\n\n", $dbName, gmdate('c'));
    try {
      $fh = fopen($filePath, 'w');
      if (!$fh) {
        return ['success' => false, 'message' => "Unable to write to {$filePath}"];
      }
      fwrite($fh, $header);
      fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");
      $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
      foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        if (!$createStmt || !isset($createStmt['Create Table'])) {
          continue;
        }
        fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($fh, $createStmt['Create Table'] . ";\n\n");
        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        $rowCount = 0;
        $columnsSql = '';
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          if ($rowCount === 0) {
            $columns = array_map(function ($col) {
              return "`" . str_replace("`", "``", $col) . "`";
            }, array_keys($row));
            $columnsSql = '(' . implode(', ', $columns) . ')';
            fwrite($fh, "INSERT INTO `{$table}` {$columnsSql} VALUES\n");
          } else {
            fwrite($fh, ",\n");
          }
          $values = array_map(function ($value) use ($pdo) {
            if ($value === null) return 'NULL';
            return $pdo->quote($value);
          }, array_values($row));
          fwrite($fh, '(' . implode(', ', $values) . ')');
          $rowCount++;
        }
        if ($rowCount > 0) {
          fwrite($fh, ";\n\n");
        } else {
          fwrite($fh, "-- Table `{$table}` had no rows at backup time.\n\n");
        }
      }
      fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
      fclose($fh);
      return [
        'success' => true,
        'path' => $filePath,
        'message' => 'Backup completed'
      ];
    } catch (Throwable $e) {
      if (isset($fh) && is_resource($fh)) {
        fclose($fh);
      }
      if (file_exists($filePath)) {
        unlink($filePath);
      }
      return [
        'success' => false,
        'message' => 'Backup failed: ' . $e->getMessage()
      ];
    }
  }
}
