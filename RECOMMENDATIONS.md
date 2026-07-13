# Recommendations — dreading-api

Prioritized backlog for reviving this API. References use `file:line` from the current tree.

## Already addressed in this pass
- Dockerized to run locally end-to-end: added a `mongo` service + volume to `docker-compose.yml` so the API serves data with no cloud credentials.
- `php.dockerfile`: dropped the invalid `zlib` PECL package, pinned `php:8.2-fpm`, and pinned `ext-mongodb 1.21.0` (the last 1.x the `jenssegers/mongodb 3.9` / `mongodb-lib 1.15` line accepts — the 2.x extension is incompatible).
- Composer now runs on the same PHP 8.2 image (with `ext-mongodb`) via a bundled `composer` binary; removed the dead `composer.dockerfile` that pulled `composer:latest` (PHP 8.5, no mongo ext).
- `.env.demo`: set non-empty MariaDB passwords so the `mariadb:10.5` container initializes.
- `src/.env.example`: `DBM_URI`/`DBM_DATABASE` default to the local `mongo` service.
- `App\Models\Reading`: added `$fillable`; new `ReadingSeeder` inserts a sample reading for local runs.
- Tidied `ReadingRepository` (stray `;;`) and the stale route comment in `routes/api.php`.

## Already addressed in this pass
- **`lastReading()` no longer loads the whole collection** (was P1): now `Reading::orderByDesc('date_raw')->first()`.
- **Dropped the misleading empty relational readings migration** (partial P0.2): `2023_06_04_..._create_readings_table.php` removed; the readings live in MongoDB. Real CRUD is still open (below).
- **`{date}` input is validated** (partial P1.5): `from_date()` returns a `422` JSON error for anything that isn't `Y-m-d`.
- **List endpoints are paginated** (P1.4): `today`/`date`/`last_day`/`last_week`/`last_month` return a Laravel paginator envelope honoring `?per_page` (default 15, max 100); `lastReading` stays a single object.
- **Fixed the v2 redirect**: `/api/v2/readings` returned 500 (a closure was passed to `Route::redirect`); it now 301s to `/api/v1/readings`.
- **Rate limit covered + security posture documented** (P2): a feature test asserts the 60 req/min `throttle:api`; the intentional no-auth-for-public-data decision is in the README.

## P0 — needed for real (production) use
1. **Wire production data.** In production the readings live in **MongoDB Atlas**; set `DBM_URI` (or `DBM_HOST/USERNAME/PASSWORD`) via environment/secrets. Alternatively point the API at the same Mongo the scraper writes to.
2. **Implement (or drop) CRUD.** Despite the `feat(api): Add reading crud manager` commit, only read endpoints exist. Either implement create/update/delete or rename accordingly. (The vestigial relational migration was removed — see above.)

## P1 — correctness & performance
3. **Fragile date filtering.** `date_raw` is matched with SQL-style `LIKE "%...%"` and compared `>=` against Carbon datetimes on a schemaless string field (`src/app/Repositories/ReadingRepository.php`). Store the date as a real BSON date and query with range operators, or normalize `date_raw` and document its exact format.
4. **Standardize error responses.** Beyond the `{date}` 422, adopt a consistent error envelope across the endpoints (e.g. for empty results or not-found).

## P2 — security & hardening
6. **Auth (optional).** All `/api/*` routes are already rate-limited to 60 req/min per IP via Laravel's default `throttle:api` (covered by a feature test). The reading data is public liturgical text, so the reading endpoints are intentionally unauthenticated; revisit only if a private/write surface is added.
7. **Storage permissions.** php-fpm runs as `www-data` against a host-owned `./src` mount, so `storage/logs` isn't writable (worked around with `chmod` in the README). Cleaner fix: build the image to run as the host UID/GID (the commented `PHPUSER`/`NGINXUSER` logic in `php.dockerfile` / `nginx.dockerfile`), or add an entrypoint that fixes ownership.
8. **Mongo-vs-MariaDB split-brain.** Default connection is `mongodb`, yet the stack ships MariaDB and a relational readings migration. Document the two-store setup clearly or remove the unused relational path.

## P3 — tests, CI, docs
9. **Tests.** Only the default `tests/Feature/ExampleTest.php` / `Unit/ExampleTest.php` exist. Add feature tests that seed a reading and assert each endpoint's JSON. Note `phpunit.xml` has the SQLite in-memory lines commented out.
10. **CI.** No workflow. Add build + test (and optionally a `docker compose build` smoke check).
11. **API docs.** Generate OpenAPI/Swagger for the reading endpoints.
12. **Dead Docker plumbing.** The `*USER`/`*GROUP` build args and user-creation blocks are entirely commented out across the dockerfiles and `docker-compose.yml`; either wire them (see P2.7) or remove them. `src/README.md` is still the stock Laravel readme.
