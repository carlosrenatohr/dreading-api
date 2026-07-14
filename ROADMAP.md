# Roadmap — future features & analytics research

Everything beyond the **live** stack (scraper → API Worker + D1 + Workers AI → PWA + Telegram bot + landing). Kept here so nothing is lost and so the next build is always one pick away. See the project [README](../README.md) for what's already live, [VISION.md](./VISION.md) for direction, [IDEAS.md](./IDEAS.md) for the raw backlog, [CLIENTS.md](./CLIENTS.md) for the client map.

Legend: 🟢 quick / instant · 🟡 a session · 🔴 a project. **P1 = do next.**

---

## 1. Analytics — visit-level insight for research (privacy-first, Cloudflare-native, ~€0)

**Why:** understand *who reads, when, what resonates, and whether they come back* — to guide the product **and** to show real reach to parishes / dioceses / sponsors. Aggregate + anonymous only; no PII, no cookies, no selling data. This is the research substrate the rest of the roadmap steers by.

### 1.1 What to measure

**Visits (traffic & reach)** — the layer you specifically want richer:
- page views, **unique visitors**, sessions, new vs returning
- **referrers / sources** (direct, Telegram, WhatsApp, social, parish sites) — which channels actually drive reads
- **geography** (country / region) — where the community is
- device / browser / OS, viewport (mobile vs desktop share)
- entry & exit pages, time-on-page, scroll depth, bounce
- **Core Web Vitals** (LCP/INP/CLS) — real-world performance

**Engagement (product depth)** — custom events from the PWA:
- `read` (a reading opened) + which `date` / liturgical day → **most-read / most-resonant readings**
- `install` (PWA installed), `open` (app launch), `share_click`, `listen_play` (TTS), `kids_toggle`, `prayer_open`, `amen` (streak awarded), `date_nav` (browsing the archive)
- funnel: **landing → install → open → read → amén** (where people drop)

**Retention & habit** — the real health metric for a *daily* habit:
- **DAU / WAU / MAU** and DAU/MAU stickiness ratio
- **retention cohorts** (D1 / D7 / D30 by install week)
- **streak distribution** (how many reach 3, 7, 30, 100 days) — the core loop's KPI
- most-read weekdays / times of day; drop-off after big feasts

### 1.2 Stack (all Cloudflare free tier)

| Layer | Tool | What it gives | Effort |
| --- | --- | --- | --- |
| **Web Analytics** | Cloudflare Web Analytics | Visits, referrers, geo, device, Web Vitals — from a beacon. **No cookies, no code beyond the snippet.** | 🟢 1-click toggle on the Pages project (auto-injects the beacon) |
| **Custom events** | Workers Analytics Engine (`ANALYTICS` binding, already live as `dreading_events`) | Time-series of *our* events: `writeDataPoint({ blobs:[event, date, country], doubles:[1], indexes:[event] })`. Already wired on read endpoints. | 🟡 extend to more events |
| **PWA → events** | PWA `POST`s lightweight events to a Worker route → Analytics Engine | `share_click`, `install`, `amen`, `streak_milestone`, `listen_play`, `kids_toggle`, `read` | 🟡 |
| **Query** | Analytics Engine SQL API / GraphQL Analytics API | `SELECT blob1 AS event, sum(_sample_interval) FROM dreading_events WHERE timestamp > NOW() - INTERVAL '7' DAY GROUP BY event` → feed the dashboard | 🟡 |
| **Impact dashboard** | admin route / small PWA querying AE | reads/day, growth, top readings, country map, "N.000 leyeron hoy" | 🔴 |

> **Enable first:** Analytics Engine must be on for the account (done). **Web Analytics is the 🟢 P1 win** — a one-click toggle on the `dreading-pwa` Pages project gives visits/referrers/geo/Web Vitals immediately, no code. Turn it on before anything else here.

### 1.3 Privacy posture

Aggregate + anonymous by design: Web Analytics is cookieless; Analytics Engine stores events, not identities; no cross-site tracking, no PII, no third-party ad SDKs. Streaks live in the device's `localStorage` (not server-side), so there is no per-user profile to leak. This is both the ethical stance and a selling point to parishes.

### 1.4 If you outgrow CF-native (research-grade, still ~free)

If you later want funnels/cohorts/session-replay out of the box (heavier, off the pure-CF line):
- **PostHog** (free cloud tier) — product analytics: funnels, retention cohorts, feature flags, session replay. Best for deep product research; adds a third-party.
- **Umami** / **Plausible CE** — lightweight, privacy-first, **self-hostable** (can run on a Worker/container) — a middle ground with nicer dashboards than raw AE.
- Keep the CF-native path as the default (zero third-party, zero cost); reach for these only when a specific research question needs a tool AE can't answer cheaply.

### 1.5 Phased order

1. 🟢 **P1** — flip on **Web Analytics** on the PWA (and landing) → instant visits/referrers/geo/vitals.
2. 🟡 **P2** — extend Analytics Engine events (worker already writes reads; add PWA `install/share/amen/listen/kids/read-by-date`).
3. 🟡 **P3** — query AE (SQL API) → **usage numbers in `/admin`** (reads/day, top readings, country, DAU).
4. 🔴 **P4** — public **impact dashboard** + shareable "reach" card for parishes/sponsors; retention cohorts + streak distribution.

---

## 2. Anti-abuse / CAPTCHA (only when a form or auth appears)

Not needed yet (no forms). When a donation/contact form or auth lands, protect it:
- **Cloudflare Turnstile** — free, privacy-friendly, no puzzles, native to this stack (zero-ops, not OSS).
- **Open-source** if self-hosting is preferred: **mCaptcha** (Rust, PoW), **Cap** (tiny PoW, OSS), **Friendly Captcha** (partly OSS). Lean mCaptcha/Cap for OSS + PoW, or Turnstile for zero-ops.

---

## 3. Clients & surfaces (each its own repo)

| Feature | What it is | Effort | Notes |
| --- | --- | --- | --- |
| **Landing CD** 🟢 | Connect `dreading-landing` to Pages git (or `deploy.yaml`) so it auto-deploys | 🟢 | production branch is `master`; align or set to `main` |
| **Liturgical calendar widget** | Full-month view, navigate months, minimalist (cleaner than ciudadredonda's `/calendario`), **highlighting feasts by liturgical color** (green Ordinary, purple Advent/Lent, gold feasts, red martyrs). Data from **our ingested readings** (we already store dated readings + derive the season from the title) — another reason to keep ingesting ahead. Unlocks browsing + reading plans. | 🔴 | no scraping their calendar; build on D1 |
| **dreading-kids** | Kids PWA: illustrated, simple language, audio narration, one tiny question (`kids_reflection` already exists) | 🔴 | reuses the API + enrichment |
| **Parish embed widget** | Web component / iframe parishes drop on their site (daily reading + art) → distribution via parishes | 🟡 | growth via parishes |
| **Admin / curation PWA** | Review + approve the AI reflection/art before publish (quality + theological guardrail) | 🟡 | pairs with the review-queue idea |
| **WhatsApp bot** | Same as the Telegram Worker, via the WhatsApp Cloud API | 🟡 | mirror `dreading-bot-tg` |
| **Social auto-publish** | Daily art + message auto-posted to Instagram / X — the growth flywheel | 🟡 | `dreading-social` |
| **Voice** | Alexa / Google "lee el evangelio de hoy" (TTS) | 🔴 | |
| **Home-screen widgets** | iOS/Android glanceable daily gospel line + art | 🔴 | |
| **Reading plans** | Advent / Lent guided PWA over the archive | 🔴 | needs backfill (below) |

---

## 4. Data & AI depth

- **Historical backfill** 🟡 — walk the `/events/` `prev` links (`services/source.prev_event_url` already exists) to seed a couple of months of archive → unlocks "readings for date X" + reading plans.
- **Liturgical metadata** 🟡 — store season, color, saints, cycle (A/B/C) explicitly → richer filters + theming.
- **Second source for resilience** 🟡 — the site changed once already; add a fallback source, merge by date (use `source_version`).
- **Idempotent re-enrich** 🟢 — upsert + re-enrich if a field is missing on the next run.
- **Reflection tones/lengths** 🟡 — 15-second / family / teen / theological-depth; **multi-language** reflections (readings stay source language).
- **TTS audio** 🟡 — narrate gospel + reflection (Workers AI or Piper/Coqui) for commute / kids / accessibility.
- **Guardrails** — label everything AI-generated (note: the on-screen disclaimer is currently hidden per request); optional human review queue; image moderation before social posting.

---

## Cross-cutting unlocks

- **Backfill** → archive + reading plans + the calendar widget's history.
- **Auth (later)** → per-user streaks (server-side), favorites, push notifications — but keep the readings free and ungated.
- **Analytics (§1)** → tells you which of the above to build first: follow where real visits and engagement point.
