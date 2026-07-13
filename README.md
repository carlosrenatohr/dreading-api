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

## Notes

- The PHP containers (`php`, `artisan`, `composer`) run as the host UID/GID (`PHPUID`/`PHPGID`) instead of `www-data`, so php-fpm can write `storage/logs` and `bootstrap/cache` on the host-owned `./src` mount with no `chmod` — and files the app writes stay owned by you.
- `ReadingSeeder` is a local convenience only — production data comes from `dreading-scrape` writing to the same `readings` collection.
