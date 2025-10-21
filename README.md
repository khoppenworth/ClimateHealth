# OpenClimate-DHIS (PHP/LAMP Edition)

A lightweight FOSS starter to align climate datasets with local systems like **DHIS2**.
Built for standard **LAMP** hosting (Linux/Apache/MySQL/PHP 8+), with a Material Design UI (MaterializeCSS).

## Features
- Connectors: **Open-Meteo** (no key) and **NASA POWER** (no key) with PHP cURL
- Demo data & MySQL schema (org units w/ centroids, climate values)
- ETL: fetch → transform (daily aggregates) → store
- DHIS2 push via Web API (`/api/dataValueSets`) with **dry-run** toggle
- Simple admin UI to run jobs and preview outgoing payloads

## Quick Start
1. Create a MySQL database and user.
2. Import schema and demo data:
   ```bash
   mysql -u <user> -p <db> < sql/schema_mysql.sql
   ```
3. Copy configuration and edit credentials:
   ```bash
   cp config/sample.env.php config/env.php
   ```
4. Deploy the `public/` folder under your Apache vhost DocumentRoot (or point vhost to it).
5. Ensure `tmp/` is writable by the web server user.
6. Visit the app (e.g., `https://yourserver/`) and click **Run Daily Ingest**.

## Cron (optional)
```
# Every day at 01:30
30 1 * * * php /var/www/openclimate-dhis-php/scripts/ingest.php >> /var/log/openclimate-dhis.log 2>&1
```

## Security Note
This starter has no auth by default. Protect `/public/` with your SSO or basic auth, or place it behind your DHIS2 admin network. Never expose job endpoints publicly in production.

## DHIS2 Mapping
Edit `config/mappings.php` to bind climate variables to DHIS2 dataElements/dataSet/orgUnit UIDs.

## License
Apache-2.0
