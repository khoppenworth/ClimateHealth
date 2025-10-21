<?php
class Helpers {
  public static function http_get_json($url, $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_CONNECTTIMEOUT => 20,
      CURLOPT_TIMEOUT => 60
    ]);
    $out = curl_exec($ch);
    if ($out === false) throw new Exception('cURL error: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 400) throw new Exception("HTTP $code for $url");
    $json = json_decode($out, true);
    if ($json === null) throw new Exception("Failed to decode JSON from $url");
    return $json;
  }
}
