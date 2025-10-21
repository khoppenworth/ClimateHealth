<?php
class Dhis2Client {
  private $base;
  private $user;
  private $pass;
  private $publish;
  public function __construct($cfg) {
    $this->base = rtrim($cfg['dhis2_base_url'], '/');
    $this->user = $cfg['dhis2_username'];
    $this->pass = $cfg['dhis2_password'];
    $this->publish = (bool)$cfg['publish_dhis2'];
  }
  public function postDataValueSets($payload) {
    if (!$this->publish) {
      return ['dryRun'=>true, 'payload'=>$payload];
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $this->base . "/api/dataValueSets",
      CURLOPT_USERPWD => $this->user . ":" . $this->pass,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($payload),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 20,
      CURLOPT_TIMEOUT => 60,
    ]);
    $out = curl_exec($ch);
    if ($out === false) throw new Exception('cURL error: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['httpCode'=>$code, 'response'=>json_decode($out, true)];
  }
}
