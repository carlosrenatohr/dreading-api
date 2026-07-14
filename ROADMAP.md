# Roadmap — Analytics, Landing & more clients

Planned next steps beyond the live stack (scraper → worker+D1+AI → PWA + Telegram bot). Kept here so it isn't lost. See also [VISION.md](./VISION.md), [IDEAS.md](./IDEAS.md), [CLIENTS.md](./CLIENTS.md).

## 1. Analytics (privacy-first, Cloudflare-native, ~$0)

**Why:** see usage + retention, and show real reach to parishes/donors. Aggregate only, no PII.

**Metrics:** reads/day, DAU/MAU, retention, streak distribution, most-read days, shares, installs, notification opt-in, country, listen / kids-mode usage.

**Stack (all CF free tier):**
- **Cloudflare Web Analytics** — *P1, instant*: drop the beacon into the PWA → page views, referrers, Core Web Vitals. No cookies, no code beyond the snippet.
- **Workers Analytics Engine** — *P2*: from the API Worker, `env.ANALYTICS.writeDataPoint({...})` on each read/ingest — event type, reading date, endpoint, `request.cf.country`. Time-series, free tier, queried via the GraphQL/SQL API. Keeps D1 clean.
- **Custom PWA events** — *P2*: PWA posts lightweight events to a Worker route → Analytics Engine (`share_click`, `install`, `streak_milestone`, `listen_play`, `kids_toggle`).
- **Impact dashboard** — *P3*: small admin route/PWA querying Analytics Engine → reads/day, growth, top readings, map ("N.000 leyeron hoy").

**Order:** P1 beacon → P2 Analytics Engine events (worker + PWA) → P3 dashboard.

> **Enable first (dashboard):** Analytics Engine must be turned on for the account (`…/workers/analytics-engine`) before the Worker can bind it; Web Analytics is a 1-click toggle on the Pages project (auto-injects the beacon, no code).

## 1b. CAPTCHA / anti-abuse (future — donations, contact, auth)

Not needed yet (no forms). When we add a donation/contact form or auth, protect it:
- **Cloudflare Turnstile** — free, privacy-friendly, no puzzles, native to this stack (not OSS but zero-friction).
- **Open-source** options if self-hosting is preferred: **mCaptcha** (Rust, self-host, PoW), **Cap** (tiny PoW, OSS), **Friendly Captcha** (partly OSS). Lean mCaptcha/Cap for OSS + PoW (no user puzzles), or Turnstile for zero-ops.

## 2. Landing — commercial & attractive (`dreading-landing`)

**Why:** sell the app → drive installs + donations / parish sponsorship. It's the marketing surface.

- New repo, static on **Cloudflare Pages** (CD like the others).
- **Hero = today's real illustration** pulled live from the API + tagline + a big **Instalar** CTA (→ PWA). OG image = the daily art (shareable link).
- Sections: what it is · the three experiences (jóvenes / adultos / niños) · features (lectura diaria, reflexión IA, arte, modo niños, offline, escuchar) · for parishes (embed widget) · mission · **donar / apadrinar el arte del día**.
- Design: distinctive + reverent + modern (frontend-design skill), fast, SEO + social cards.
- Tech: Astro or plain static; live hero via the API.

## 3. More clients / PWAs

- **dreading-kids** — kids PWA: illustrated, simple language, audio narration, one tiny question (the `kids_reflection` field already exists).
- **Parish embed widget** — a web component / iframe parishes drop on their site (daily reading + art) → distribution via parishes.
- **Admin / curation PWA** — review + approve the AI reflection/art before publish (quality + theological guardrail).
- **WhatsApp bot** — same idea as the Telegram Worker, via the WhatsApp Cloud API.
- **Voice** — Alexa / Google "lee el evangelio de hoy" (TTS).
- **Home-screen widgets** (iOS/Android) — glanceable daily gospel line + art.
- **Reading plans** — Advent / Lent guided PWA over the archive.

**Cross-cutting unlocks:** historical backfill (walk the `/events/` `prev` links) → archive + reading plans; auth (later) → per-user streaks, favorites, push notifications.
