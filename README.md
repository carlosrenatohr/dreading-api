# DReading API

A small Laravel REST API that serves the Catholic **daily liturgical readings** ("evangelio y lecturas del día") stored in MongoDB. The data is produced by the companion scraper [`dreading-scrape`](../dreading-scrape); this API exposes it as read-only JSON endpoints.

> **Status — side project, being revived.** The API runs end-to-end on the local Docker stack below: it builds, connects to a local MongoDB, and serves seeded readings over HTTP. It is intentionally read-only today — see [RECOMMENDATIONS.md](./RECOMMENDATIONS.md) for the backlog (real CRUD, auth, schema, etc.).

## Architecture

- **Laravel 9** (PHP 8.2) with the **repository pattern**: `ReadingController` → `ReadingInterface` → `ReadingRepository` (bound in `RepositoryServiceProvider`).
- **MongoDB** is the primary datastore, accessed via `jenssegers/mongodb` (`App\Models\Reading` → collection `readings`). Locally this is a `mongo` container; in production it is MongoDB Atlas.
- **MariaDB** ships in the stack only for Laravel's own system tables (users, Sanctum tokens); the reading endpoints do not use it.
- Served by **nginx + php-fpm**; `composer` and `artisan` run as one-off containers.

## Stack

- Laravel `^9.19`, PHP `8.2`, `jenssegers/mongodb ^3.9` (pinned to `ext-mongodb 1.21`, the last 1.x the 3.9 line supports), `laravel/sanctum`.
- Docker + Docker Compose (nginx, php-fpm, mariadb, mongo).

## Quickstart (local, Docker — no Atlas needed)

```bash
cp .env.demo .env                # docker-compose variables (MariaDB creds, etc.)
cp src/.env.example src/.env     # Laravel app env (points DBM_URI at the local mongo)

docker compose build                              # builds php-fpm image with ext-mongodb
docker compose up -d nginx                        # starts nginx, php, mariadb, mongo
docker compose run --rm composer install
docker compose run --rm artisan key:generate --force
docker compose run --rm artisan db:seed --class=ReadingSeeder --force   # one sample reading
```

Then hit the API (base URL `http://localhost:89/api`):

```bash
curl http://localhost:89/api/v1/readings/last
```

Tear down (drops the local mongo volume):

```bash
docker compose down -v
```

## Endpoints

Base path `/api`. All reading endpoints are public `GET` and read-only.

| Method | Path | Returns |
| --- | --- | --- |
| GET | `/api/v1/readings` | Most recent reading (aliased as `readings.index`) |
| GET | `/api/v1/readings/last` | Most recent reading |
| GET | `/api/v1/readings/today` | Readings whose `date_raw` matches today |
| GET | `/api/v1/readings/date/{date}` | Readings for a given date |
| GET | `/api/v1/readings/last_day` | Readings from the last day |
| GET | `/api/v1/readings/last_week` | Readings from the last week |
| GET | `/api/v1/readings/last_month` | Readings from the last month |
| ANY | `/api/v2/readings` | `301` redirect to `readings.index` |
| GET | `/api/user` | Authenticated user (`auth:sanctum`) |

The list endpoints (`today`, `date/{date}`, `last_day`, `last_week`, `last_month`) are **paginated**: they return a Laravel paginator envelope (`{ "data": [...], "per_page", "total", ... }`) and accept `?per_page=` (default 15, max 100). `last` / `readings` return a single reading object.

A reading document looks like:

```json
{
  "title": "Lecturas de hoy ...",
  "date_title": "13/07/2026",
  "date_raw": "2026-07-13 00:00:00",
  "lecturas": [
    { "title": "Primera lectura", "content": "...", "first_line": "...", "last_line": "..." },
    { "title": "Salmo", "content": "...", "first_line": "...", "psalm": "..." },
    { "title": "Evangelio de hoy", "content": "...", "first_line": "...", "last_line": "..." }
  ]
}
```

## Configuration

`src/.env` (from `src/.env.example`) drives the MongoDB connection:

| Variable | Purpose | Local default | Cloud (Atlas) |
| --- | --- | --- | --- |
| `DBM_CONNECTION` | Connection driver | `mongodb` | `mongodb` |
| `DBM_URI` | Mongo DSN (used verbatim when set) | `mongodb://mongo:27017` | `mongodb+srv://user:pass@cluster/...` |
| `DBM_DATABASE` | Database name | `dailyreading` | `dailyreading` |

Root `.env` (from `.env.demo`) drives docker-compose: MariaDB credentials plus `PHPUID`/`PHPGID`, the host UID/GID the PHP containers run as (defaults `1000`/`1001`). If your `id -u`/`id -g` differ, set them to match so files the app writes into `./src` stay owned by you. Host ports: nginx `89`, MariaDB `3316`, mongo `27018`.

### Production (MongoDB Atlas)

In production the readings live in a **MongoDB Atlas** cluster (populated by `dreading-scrape`). Point the API at it by setting `DBM_URI` to the Atlas connection string and `DBM_DATABASE` to the cluster's database, supplied via environment/secrets rather than committed:

```
DBM_URI="mongodb+srv://<user>:<pass>@<cluster>/?retryWrites=true&w=majority"
DBM_DATABASE=dailyreading
```

The local `mongo` compose service and MariaDB are dev-only; MariaDB backs Laravel's system tables and is not needed to serve readings.

## Notes

- The PHP containers (`php`, `artisan`, `composer`) run as the host UID/GID (`PHPUID`/`PHPGID`) instead of `www-data`, so php-fpm can write `storage/logs` and `bootstrap/cache` on the host-owned `./src` mount with no `chmod` — and files the app writes stay owned by you.
- `ReadingSeeder` is a local convenience only — production data comes from `dreading-scrape` writing to the same `readings` collection.
- **Rate limiting & auth:** all `/api/*` routes are rate-limited to 60 requests/minute per IP (Laravel's default `throttle:api`, configured in `RouteServiceProvider`). The reading data is public liturgical text, so the reading endpoints are intentionally left unauthenticated; only `/api/user` requires a Sanctum token.

## More

- [VISION.md](./VISION.md) — where this is headed: daily AI reflections & art, an app for every age, community analytics.
- [IDEAS.md](./IDEAS.md) — the wider brainstorm backlog (historical backfill, second source, engagement, analytics, growth) for analysis now or later.
- `./scripts/e2e.sh` — end-to-end check that runs the real scraper into this stack's MongoDB and asserts the endpoints serve the live readings (including a Sunday second reading).
