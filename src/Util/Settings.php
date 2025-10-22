<?php
class Settings
{
    private string $path;
    private array $defaults = [
        'app_title' => 'OpenClimate-DHIS',
        'tagline' => 'Climate & Health Intelligence Console',
        'logo_url' => '/assets/img/openclimate-logo.svg',
        'primary_color' => '#0b3954',
        'accent_color' => '#ff7f11',
        'data_reference' => 'Climate indicators derived from NASA POWER, Copernicus Climate Data Store, and NOAA Global Historical Climatology Network datasets.',
        'about_text' => 'This console harmonizes trusted climate datasets with health system workflows to inform resilient decision making.',
        'default_metric' => 'tmean_c'
    ];

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function all(): array
    {
        if (!file_exists($this->path)) {
            return $this->defaults;
        }

        $raw = file_get_contents($this->path);
        if ($raw === false) {
            return $this->defaults;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $this->defaults;
        }

        return array_merge($this->defaults, $data);
    }

    public function save(array $data): void
    {
        $payload = array_merge($this->defaults, $data);
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Unable to encode settings.');
        }
        if (file_put_contents($this->path, $json) === false) {
            throw new RuntimeException('Unable to persist settings.');
        }
    }
}
