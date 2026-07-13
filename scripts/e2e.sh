#!/usr/bin/env bash
#
# End-to-end check: the real scraper -> the API's MongoDB -> the API endpoints.
#
# Proves the full pipeline serves live scraped content (not just the seeder):
# builds the dreading-scrape image, runs it into THIS stack's mongo, then asserts
# the reading endpoints return that content — including a Sunday/feast second
# reading. Cross-repo: expects dreading-scrape as a sibling of this repo.
#
# Usage:  ./scripts/e2e.sh        (run from the dreading-api repo root)
# Exits non-zero on the first failed assertion.

set -euo pipefail

API_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SCRAPE_DIR="$(cd "$API_DIR/../dreading-scrape" && pwd)"
BASE="http://localhost:89/api/v1/readings"
NET="dreading-api_default"
cd "$API_DIR"

pass() { echo "  PASS: $1"; }
fail() { echo "  FAIL: $1"; exit 1; }

echo "==> Build scraper image + bring up the API stack"
docker build -q -t dreading-scrape-scraper "$SCRAPE_DIR" >/dev/null
[ -f .env ] || cp .env.demo .env
[ -f src/.env ] || cp src/.env.example src/.env
docker compose up -d nginx >/dev/null 2>&1
sleep 8
[ -d src/vendor ] || docker compose run --rm composer install >/dev/null 2>&1
grep -q '^APP_KEY=base64' src/.env || docker compose run --rm artisan key:generate --force >/dev/null 2>&1

echo "==> Reset readings, then run the REAL scraper into the API's mongo"
docker compose exec -T mongo mongosh --quiet dailyreading --eval 'db.readings.drop()' >/dev/null 2>&1 || true
docker rm -f e2e-redis >/dev/null 2>&1 || true
docker run -d --name e2e-redis --network "$NET" redis:7 >/dev/null
docker run --rm --network "$NET" \
  -e DB_URI=mongodb://mongo:27017 -e DB_NAME=dailyreading \
  -e UPSTACK_ENDPOINT=e2e-redis -e UPSTACK_PORT=6379 -e UPSTACK_SSL=false \
  dreading-scrape-scraper >/dev/null 2>&1
docker rm -f e2e-redis >/dev/null 2>&1

COUNT=$(docker compose exec -T mongo mongosh --quiet dailyreading --eval 'print(db.readings.countDocuments())' | tr -d '[:space:]')
[ "$COUNT" -ge 1 ] && pass "scraper stored $COUNT readings" || fail "no readings stored"

echo "==> Assert the API serves the scraped content"

# /last returns a single reading with content.
curl -sf "$BASE/last" | python3 -c "import sys,json;d=json.load(sys.stdin);assert d.get('title') and d.get('lecturas'),'no reading';print('  last:',d['date_raw'],'-',d['title'][:40])" || fail "/last"
pass "/last returns a reading"

# A stored date returns 200 with a paginated data envelope.
DATE=$(docker compose exec -T mongo mongosh --quiet dailyreading --eval 'print(db.readings.find().sort({date_raw:-1}).limit(1).next().date_raw.slice(0,10))' | tr -d '[:space:]')
curl -sf "$BASE/date/$DATE" | python3 -c "import sys,json;d=json.load(sys.stdin);assert d['data'][0]['lecturas'],'empty';print('  date/'+'$DATE'+':',len(d['data']),'reading(s)')" || fail "/date/$DATE"
pass "/date/{date} returns the reading (paginated)"

# A Sunday/feast (>=4 sections) carries a Segunda Lectura, served intact.
SUN=$(docker compose exec -T mongo mongosh --quiet dailyreading --eval 'var r=db.readings.findOne({"lecturas.3":{$exists:true}});print(r?r.date_raw.slice(0,10):"")' | tr -d '[:space:]')
if [ -n "$SUN" ]; then
  curl -sf "$BASE/date/$SUN" | python3 -c "import sys,json;d=json.load(sys.stdin);t=[l['title'] for l in d['data'][0]['lecturas']];assert 'Segunda Lectura' in t,t;print('  date/'+'$SUN'+':',t)" || fail "second-reading day $SUN"
  pass "Sunday/feast second reading served ($SUN)"
else
  echo "  SKIP: no 4-section day in the fetched window"
fi

# Invalid date is rejected with 422.
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/date/not-a-date")" = "422" ] && pass "invalid date -> 422" || fail "invalid date not 422"

echo "==> E2E PASSED"
