# Clean Air Finder

A Laravel web app that ranks Sofia, Plovdiv, Varna, Burgas, and Ruse by average hourly PM2.5 for a user-selected period.

## Start the complete app

With Docker and the Docker Compose plugin installed, run:

```sh
docker compose up --build
```

Open [http://localhost:8000](http://localhost:8000). Compose starts MySQL (database `airqapp`, user `root`, password `root`), runs the migrations, attempts the initial six-month collection, starts the web server, and runs the Laravel scheduler. If Open-Meteo is unavailable during startup, the web app still starts and serves any previously collected data; the failed attempt is logged and the daily schedule retries it. The MySQL data is persisted in a named volume.

The Compose credentials and generated application key are for local development only. Do not expose this setup publicly; configure unique credentials and a secret `APP_KEY` for a deployment. Set `APP_KEY` in the host environment to retain the same key across app-container recreations.

## Data source and city mapping

The collector uses the public [Open-Meteo Air Quality API](https://open-meteo.com/en/docs/air-quality-api), requesting hourly `pm2_5` in `Europe/Sofia` for:

| City | Latitude | Longitude |
| --- | ---: | ---: |
| Sofia | 42.6977 | 23.3219 |
| Plovdiv | 42.1354 | 24.7453 |
| Varna | 43.2141 | 27.9147 |
| Burgas | 42.5048 | 27.4626 |
| Ruse | 43.8356 | 25.9657 |

One API grid point at each city coordinate is used as a consistent proxy for that city. This is deliberately simple and comparable, but it cannot describe within-city variation or claim to be a street-level station reading. Open-Meteo's air-quality values are modelled, based on CAMS data, rather than direct local sensor measurements; users should interpret the ranking accordingly. The UI attributes Open-Meteo. The free API is intended for non-commercial use; a commercial relocation service should arrange the appropriate Open-Meteo plan and review the current [terms](https://open-meteo.com/en/terms) before production use.

Each collection downloads the rolling six-month window in one multi-location request, validates every location and hourly array before writing, and upserts observations transactionally. Timestamps are requested as Unix time and stored in UTC so the repeated local hour at daylight-saving transitions remains distinct; date-range queries convert Sofia-local day boundaries to UTC. A malformed or failed response leaves existing measurements intact. Null PM2.5 values and future hours are skipped. Successful collection prunes records older than six months. The scheduler runs the collector daily at 03:15 Sofia time.

## Query, health, and metrics

- `GET /` — date-picker UI.
- `GET /api/air-quality?start_date=2026-10-01&end_date=2026-10-07` — JSON ranking and cleanest city. Invalid dates return a useful `422` response.
- `GET /up` — Laravel process health check.
- `GET /health/data` — data-collection health; returns `503` until a successful sync has occurred, if the latest sync failed, or if the last success is older than 26 hours.
- `GET /metrics` — JSON counts by city and the latest collection status.

The period must be within the latest six months, cannot be in the future, and must have its start on or before its end. Ranking averages and reading counts are calculated in the database, not in PHP. Measurements have a unique `(city, recorded_at)` key for idempotent upserts and a `(recorded_at, city, pm2_5)` covering index for bounded date filtering and grouped aggregation. The collector makes no third-party request from a user-facing page or API request.

### Query performance

At hourly resolution, five locations contribute at most roughly 22,000 rows per six-month window. A date-range request uses one indexed database scan and `AVG(pm2_5)` / `COUNT(*) GROUP BY city`, returning at most five result rows; it does not load individual observations into application memory. To measure on the MySQL instance, run `EXPLAIN ANALYZE` on:

```sql
SELECT city, AVG(pm2_5), COUNT(*)
FROM air_quality_measurements
WHERE recorded_at BETWEEN '2026-04-20 21:00:00' AND '2026-10-21 20:59:59'
GROUP BY city;
```

Then time repeated requests to `/api/air-quality` over that same six-month range after the initial request has warmed the database buffer pool. The migration includes the date-first covering index used by this query.

## Run locally without Docker

The native PHP development setup uses SQLite, so it does not require Docker or a running MySQL service. PHP must have the `pdo_sqlite` and `curl` extensions enabled. Copy `.env.example` to `.env` if you do not already have one, then initialize the key and database and collect the initial data:

```sh
if (!(Test-Path .env)) { Copy-Item .env.example .env }
composer install
php artisan key:generate
php artisan migrate
php artisan air-quality:collect
php artisan serve
```

Do not overwrite an existing `.env`; set `DB_CONNECTION=sqlite` in it for the native setup, and run `php artisan key:generate` only if `APP_KEY` is empty. Open [http://localhost:8000](http://localhost:8000). The SQLite database is stored in `database/database.sqlite`.

The app serves saved measurements even if the initial collection cannot reach Open-Meteo. Collection failures are logged; retry later with `php artisan air-quality:collect`. To keep the local database refreshed automatically, run the scheduler in a second terminal:

```sh
php artisan schedule:work
```

### Fix PHP cURL certificate errors on Windows

If collection fails with `cURL error 60`, download the current CA bundle from [curl.se](https://curl.se/ca/cacert.pem) and configure both `curl.cainfo` and `openssl.cafile` in the PHP configuration file shown by `php --ini`:

```ini
curl.cainfo = "C:\path\to\cacert.pem"
openssl.cafile = "C:\path\to\cacert.pem"
```

Restart PowerShell, confirm the settings with `php -r "echo ini_get('curl.cainfo'), PHP_EOL, ini_get('openssl.cafile'), PHP_EOL;"`, then retry `php artisan air-quality:collect`. Keep TLS certificate verification enabled; never work around this error by disabling SSL verification.

To use native MySQL instead of SQLite, start a local MySQL server, configure `DB_CONNECTION=mysql` and its connection settings in `.env`, create the database, then run `php artisan migrate`. Docker Compose continues to use MySQL as described above. The collection command can be rerun safely to backfill/update the rolling window.

## Tests

```sh
php artisan test
```

The tests use an in-memory SQLite database and fake the Open-Meteo HTTP response; they do not require a network connection or modify MySQL data.
