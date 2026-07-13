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

## P0 — needed for real (production) use
1. **Wire production data.** In production the readings live in **MongoDB Atlas**; set `DBM_URI` (or `DBM_HOST/USERNAME/PASSWORD`) via environment/secrets. Alternatively point the API at the same Mongo the scraper writes to.
2. **"CRUD" is read-only.** Despite the `feat(api): Add reading crud manager` commit, only read endpoints exist. Either implement create/update/delete or rename accordingly. The `2023_06_04_..._create_readings_table.php` migration builds an **empty relational table** (`id` + `timestamps`) that neither matches the Mongo document shape nor the store the model uses — drop it or replace it with real intent.

## P1 — correctness & performance
3. **`lastReading()` loads the whole collection.** `Reading::all()->last()` (`src/app/Repositories/ReadingRepository.php:24-27`) pulls every document into memory. Use `Reading::orderByDesc('date_raw')->first()`.
4. **Fragile date filtering.** `date_raw` is matched with SQL-style `LIKE "%...%"` and compared `>=` against Carbon datetimes on a schemaless string field (`src/app/Repositories/ReadingRepository.php`). Store the date as a real BSON date and query with range operators, or normalize `date_raw` and document its exact format.
5. **No validation / pagination / error handling** in `ReadingController`. Add input validation for `{date}`, paginate list endpoints, and return proper error responses.

## P2 — security & hardening
6. **Endpoints are public.** All `/api/v1/readings*` routes have no auth or rate limiting (`src/routes/api.php`). Add throttling at minimum; add auth if the data warrants it.
7. **Storage permissions.** php-fpm runs as `www-data` against a host-owned `./src` mount, so `storage/logs` isn't writable (worked around with `chmod` in the README). Cleaner fix: build the image to run as the host UID/GID (the commented `PHPUSER`/`NGINXUSER` logic in `php.dockerfile` / `nginx.dockerfile`), or add an entrypoint that fixes ownership.
8. **Mongo-vs-MariaDB split-brain.** Default connection is `mongodb`, yet the stack ships MariaDB and a relational readings migration. Document the two-store setup clearly or remove the unused relational path.

## P3 — tests, CI, docs
9. **Tests.** Only the default `tests/Feature/ExampleTest.php` / `Unit/ExampleTest.php` exist. Add feature tests that seed a reading and assert each endpoint's JSON. Note `phpunit.xml` has the SQLite in-memory lines commented out.
10. **CI.** No workflow. Add build + test (and optionally a `docker compose build` smoke check).
11. **API docs.** Generate OpenAPI/Swagger for the reading endpoints.
12. **Dead Docker plumbing.** The `*USER`/`*GROUP` build args and user-creation blocks are entirely commented out across the dockerfiles and `docker-compose.yml`; either wire them (see P2.7) or remove them. `src/README.md` is still the stock Laravel readme.
