# DReading — Ideas & Backlog (brainstorm)

Raw idea dump for analysis now or later. Unlike [VISION.md](./VISION.md) (the curated direction), this is the wide net: keep, cut, or promote items into real phases as they earn it. Grouped, roughly prioritized within each group.

## Data & ingestion
- **Historical backfill (a couple of months).** The `/events/` pages link to the *previous* day too (`prev`, `pskip`), so `services/source.prev_event_url` already lets us walk **backwards**. Add a `run_backfill(days_back)` that walks prev from today to seed history — gives the client a real archive to serve "the readings for date X". *(Carlos wanted ~2 months of history seeded.)*
- **`source_version` flag (done).** Every stored reading now carries `source_version`; use it to filter/migrate if the schema changes again (`{source_version: 2}`).
- **Second source for resilience.** The site changed once already. Add a fallback source (another liturgical site or a public API) and merge by date — insurance against the next redesign.
- **Idempotent daily job.** Already deduped by `date_raw`; consider an "upsert + re-enrich if missing" so a reading fetched before enrichment existed gets enriched on the next run.
- **Liturgical metadata.** Store the liturgical day/season, color, saints of the day, cycle (A/B/C) — rich filters + theming for the app.

## AI enrichment (build on services/enrich.py)
- Wire a real free provider (Groq / Cloudflare Workers AI / Gemini) behind `ENRICH_PROVIDER=llm`; keep the stub for tests.
- **Daily image generation** (the growth engine): free image model (Workers AI Flux / SDXL) from `image_prompt`; store URL; auto-post to social.
- **Audio** (TTS): narrate the gospel + reflection (Piper/Coqui free) for a commute/kids/accessibility mode.
- Multiple reflection *lengths/tones*: 15-second, family, teen, theological-depth.
- **Multi-language** reflections/translations (readings stay source language; reflection localized).
- Guardrails: label everything AI-generated; optional human review queue before publish; moderation on images.

## Engagement & product
- Streaks, daily reminder notification, "you've read N days in a row".
- Share-cards (image + message of the day) — one-tap share to WhatsApp/IG stories.
- **Kids mode**: illustrated, simple-language, audio, one tiny question.
- Reading plans / seasons (Advent, Lent) with progress.
- Family / small-group mode: the discussion questions, shared streak.
- Gamification (gentle): saint badges, "gospel of the week" quiz.
- Widget / embeddable snippet for parish websites and bulletins.
- Telegram / WhatsApp bot that pushes the daily reading + image.

## Analytics & impact
- Usage dashboard: reads/day, DAU/MAU, retention, streak distribution, most-shared reading, geography, notification opt-in.
- "Impact" view for parishes/dioceses/sponsors: reach numbers, growth.
- Privacy-first: aggregate/anonymous; no selling data.

## Platform & tech
- Turn readings into a proper content API (public, cached at the edge — Cloudflare) so third parties/parishes can build on it.
- PWA first (installable, offline, push) before native apps.
- Edge caching of the daily enriched payload (generate once/day, serve free).
- Consider a monorepo or a shared `dreading-*` org as more surfaces appear.

## Growth & sustainability
- Social auto-publish is the flywheel: beautiful daily art → shares → installs.
- Partnerships: parishes, Catholic influencers, dioceses, Catholic media.
- Funding: donations, sponsor-a-day (image credit), grants — never gate the readings.

## Open questions
- Redistribution terms / copyright of the liturgical text — confirm before scaling.
- How much AI reflection is welcome vs. distracting? Test with real users; keep it optional.
- Backfill depth: how far back do the `/events/` pages exist? (probe before committing to 2 months).
- One app for all ages vs. separate kids app?
- Which free AI provider gives the best Spanish reflections at zero cost? (bench Groq vs Gemini vs Workers AI).
