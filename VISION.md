# DReading — Vision & Potential

*Making the Catholic daily readings something people actually look forward to — across every age.*

> **Status update (2026-07-14):** Phases 0–3 are **live**. The stack migrated to **Cloudflare-native** (Workers + Hono + **D1** replacing MongoDB, R2 for art, Workers AI for enrichment, Pages for the PWA/landing) — the MongoDB architecture described below is the original design; the live system is the Worker `dreading-api-worker`. The daily enrichment (reflection + kids version + message + questions + a generated image) runs automatically, and the PWA, Telegram bot, and landing all serve it. See the project [README](../README.md) for the live map and [ROADMAP.md](./ROADMAP.md) for what's next. The vision prose below stays as the north star.

## Why this matters

Millions pray the daily liturgical readings ("lecturas del día"). But the way they're delivered today is mostly static, text-heavy, and unengaging — a wall of text on a dated website. Meanwhile the same audience lives on beautiful, dynamic, bite-sized mobile experiences everywhere else. There's a real gap: **the most meaningful daily content in a believer's life has the worst daily UX.**

DReading closes that gap: take the authentic daily readings and wrap them in something dynamic, beautiful, and shareable — a daily habit that engages teens, adults, and even small children, and gives parishes and the wider Church a way to *see* and grow that engagement.

## What we already have (the foundation is real)

This isn't a blank page — the data engine works today:

- **`dreading-scrape`** — pulls the real daily readings from ciudadredonda.org (today + the upcoming week via dated event pages), parses first reading / psalm / (Sunday) second reading / gospel, dockerized, tested, runs daily via CI.
- **`dreading-api`** — a clean read API over those readings in MongoDB (paginated endpoints, rate-limited, tested).
- **Proven end-to-end**: live site → scraper → MongoDB → API endpoints serve the real content (including the Sunday second reading).

So the hard, unglamorous part — a reliable daily content pipeline — is **done**. Everything below builds on it.

## The product

Four pillars, all fed by the existing readings pipeline:

### 1. Daily reading, beautifully delivered
A fast PWA / mobile app / web: today's reading, clean typography, dark mode, **offline**, a daily push notification, audio (text-to-speech) for commuting or the visually impaired. One tap, done — the frictionless daily habit.

### 2. AI-generated reflection & meaning (cheap/free LLM)
A small daily batch job enriches each reading with AI-generated, clearly-labeled supplementary content:
- a short **reflection** (2–3 sentences) tying the gospel to daily life,
- a **kids' version** in simple language,
- a one-line **"message of the day"** for share cards,
- **discussion questions** for families / small groups / catechesis.

> The *readings themselves* stay the authentic approved liturgical text — AI is used only for the *supplementary* reflection/art, always labeled as such (see Risks).

### 3. A daily AI image (free/cheap image gen)
One generated illustration per day of the gospel scene — warm, age-appropriate art — auto-posted to social channels (Instagram / X / Telegram / WhatsApp broadcast) with the message of the day. This is the **growth engine**: beautiful daily art that people share, each linking back to the app. One image/day = near-zero cost.

### 4. Engagement that hooks every age
- **Teens/young adults**: streaks, share-cards, stories-format, saint-of-the-day, light gamification.
- **Adults**: the daily habit, reflections, reading plans (Advent, Lent), notifications.
- **Kids / early readers**: an illustrated "kids mode" with the simple-language version, audio narration, and a tiny daily question.
- **Parishes / catechists**: shareable weekly packs, embeddable widget, group discussion questions.

## Community impact & analytics

Instrument usage from day one so impact is visible:
- reads/day, retention, streaks, DAU/MAU, geography, most-shared readings, notification opt-in.
- a simple **impact dashboard** to show parishes, dioceses, and potential sponsors/donors real reach — "N,000 people read today's gospel through DReading."

This turns a hobby into something you can credibly grow, fund, and hand to the Church community.

## Architecture (evolution, not rewrite)

```
ciudadredonda.org ─▶ dreading-scrape ─▶ MongoDB (readings)
                                            │
                              enrichment batch (daily):
                              free LLM → reflection / kids / message / questions
                              free image gen → daily illustration
                                            │  (stored on the same reading doc)
                                            ▼
                                     dreading-api  ──▶  PWA / app / web
                                            └──────────▶  social auto-publish bots
                                            └──────────▶  analytics / dashboard
```

The reading document simply gains optional fields (`reflection`, `kids_reflection`, `message`, `questions`, `image_url`); the API exposes them; clients render them. Nothing already built is thrown away.

## Cheap / free AI options (keep cost ≈ €0)

Because enrichment is **one batch per day**, generous free tiers cover it easily:

| Need | Free / cheap options |
| --- | --- |
| Text (reflection, kids version, questions) | Cloudflare Workers AI (free tier, Llama), Groq free tier (fast Llama), Google Gemini free tier, HuggingFace Inference, or self-hosted Ollama |
| Image (daily illustration) | Cloudflare Workers AI (Flux / SDXL, free tier), or a self-hosted SD |
| Audio (TTS narration) | Piper / Coqui (self-host, free), or Workers AI / cloud TTS free tiers |
| Hosting | Cloudflare Pages/Workers, Vercel/Netlify free, MongoDB Atlas free tier |

Daily-batch + heavy caching (generate once, serve all day) means the whole thing can run on free tiers for a long time.

## Roadmap

- **Phase 0 — Data engine** ✅ *done*: scraper + API + MongoDB, tested, E2E-proven.
- **Phase 1 — Enrichment pipeline**: daily job adds reflection + kids version + message + questions + a generated image to each reading; API serves them.
- **Phase 2 — App**: PWA (today's reading + image + reflection + audio), notifications, streaks, offline.
- **Phase 3 — Reach & insight**: social auto-publish (daily image + message), analytics + impact dashboard.
- **Phase 4 — Depth**: kids mode, reading plans (Advent/Lent), multi-language, saints, group/parish features.

## MVP (the next concrete step)

After the daily scrape, run one enrichment job that calls a **free** LLM for a reflection + kids version + message and a **free** image generator for the gospel image, store them on the reading doc, and expose them via the existing API. Then a minimal one-screen PWA renders **today**: gospel + image + reflection + a "share" button. That single screen, shipped, is already something people would use and share every morning.

## Sustainability

Free to use (this should serve, not gate, the faithful). Fund via optional donations, parish/diocese sponsorship of the daily image, or small grants — kept lightweight and non-intrusive.

## Risks & principles (important)

- **Theological integrity**: the readings are the authentic, approved liturgical text — never AI-rewritten. AI produces only *supplementary* reflection/art, **always clearly labeled as AI-generated**, and ideally reviewable before publish.
- **Image appropriateness**: reverent, age-appropriate art only; run generated images through moderation; curate/allow override before social posting.
- **Source & copyright**: attribute the reading source; confirm redistribution terms for the liturgical text; treat the source site respectfully (light, daily crawl).
- **Reliability**: the source site changed once already — keep the parser resilient and consider a second source for redundancy.

---

*This started as a side project and it's worth becoming more. The engine runs; the next step is making the daily Word beautiful, shareable, and alive for every age.*
