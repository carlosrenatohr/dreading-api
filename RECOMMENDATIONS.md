# Recommendations — dreading-api

Prioritized backlog for reviving this API. References use `file:line` from the current tree.

## Already addressed
- Dockerized to run locally end-to-end: added a `mongo` service + volume to `docker-compose.yml` so the API serves data with no cloud credentials.
- `php.dockerfile`: dropped the invalid `zlib` PECL package, pinned `php:8.2-fpm`, and pinned `ext-mongodb 1.21.0` (the last 1.x the `jenssegers/mongodb 3.9` / `mongodb-lib 1.15` line accepts — the 2.x extension is incompatible).
- Composer runs on the same PHP 8.2 image (with `ext-mongodb`) via a bundled `composer` binary; removed the dead `composer.dockerfile`.
- `.env.demo`: non-empty MariaDB passwords; `src/.env.example`: `DBM_URI`/`DBM_DATABASE` default to the local `mongo` service.
- `App\Models\Reading` `$fillable`; `ReadingSeeder` for local sample data.
- **`lastReading()`** now uses `orderByDesc('date_raw')->first()` instead of loading the whole collection.
- **Removed the misleading empty relational readings migration** (readings live in MongoDB).
- **`{date}` validation**: `from_date()` returns a `422` JSON error for anything that isn't `Y-m-d`.
- **List endpoints paginated**: `today`/`date`/`last_day`/`last_week`/`last_month` return a Laravel paginator envelope honoring `?per_page` (default 15, max 100); `lastReading` stays a single object.
- **Fixed the v2 redirect**: `/api/v2/readings` (a closure passed to `Route::redirect`) returned 500; now 301s to `/api/v1/readings`.
- **Rate limit + auth posture**: a feature test asserts the 60 req/min `throttle:api`; the intentional no-auth-for-public-data decision is documented in the README.
- **Storage without chmod**: the PHP containers run as the host UID/GID (`PHPUID`/`PHPGID`), so `storage/logs` is writable on the bind mount with no `chmod`; dropped the dead `*USER`/`*GROUP` compose/dockerfile plumbing.
- **Two-store setup documented**: the README explains MongoDB (readings) vs MariaDB (Laravel system tables only), and how to point `DBM_URI` at MongoDB Atlas in production.
- **Feature tests**: PHPUnit runs against an isolated Mongo test DB (`dailyreading_test`); `ReadingEndpointsTest` covers `/last`, `/date` (valid + 422), `/today`, pagination, the v2 redirect and the rate limit.

## P0 — needed for real (production) use
1. **Provide Atlas credentials.** The README documents the `DBM_URI`/`DBM_DATABASE` prod config; the remaining step is supplying the real Atlas secrets to the deployment environment.
2. **Implement (or drop) CRUD.** Despite the `feat(api): Add reading crud manager` commit, only read endpoints exist. Either implement create/update/delete or rename accordingly. (Deliberately left read-only for now.)

## P1 — correctness & performance
3. **Fragile date filtering.** `date_raw` is matched with SQL-style `LIKE "%...%"` and compared `>=` against Carbon datetimes on a schemaless string field (`src/app/Repositories/ReadingRepository.php`). Store the date as a real BSON date and query with range operators, or normalize `date_raw` and document its exact format.
4. **Standardize error responses.** Beyond the `{date}` 422, adopt a consistent error envelope across the endpoints (e.g. for empty results or not-found).

## P2 — security & hardening
5. **Auth (optional).** Endpoints are rate-limited (60 req/min per IP) and serve public liturgical text, so they are intentionally unauthenticated. Revisit only if a private or write surface is added.

## P3 — tests, CI, docs
6. **Test CI.** Add a GitHub Actions workflow that runs PHPUnit. It needs a `mongo` service container and PHP 8.2 with **`ext-mongodb` pinned to 1.x** (the runner's default is 2.x, incompatible with the `jenssegers/mongodb 3.9` line — the same skew handled in `php.dockerfile`); best authored and iterated against a real CI run.
7. **API docs.** Generate OpenAPI/Swagger for the reading endpoints.
8. **Replace the stock `src/README.md`** (still the default Laravel framework readme).
