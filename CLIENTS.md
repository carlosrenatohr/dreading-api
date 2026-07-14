# DReading — Clients

The API and scraper are the platform; **each client is its own repo** that consumes the reading endpoints. This is the client map — what to build, in what order, and where it lives.

All clients read the same API (the **`dreading-api-worker`** Hono/D1 Worker — `dreading-api` is the legacy Laravel reference), so they only need its base URL configured. Nothing here re-implements the readings; they render what the API serves (readings + the enrichment fields: `message`, `reflection`, `kids_reflection`, `questions`, `image_url`).

| Client | Repo | What it is | Status | Priority |
| --- | --- | --- | --- | --- |
| **Web app (PWA)** | `dreading-pwa` | Installable phone-first web app: today's reading + reflection + daily art + prayer→streak, liturgical color, dark/light, date navigation. The flagship interactive client. | ✅ **live** | 1 |
| **Telegram bot** | `dreading-bot-tg` | Worker cron; posts the daily art + caption to a channel each morning. Zero-UI reach + engagement. | ✅ **live** | 1 |
| **Landing / marketing** | `dreading-landing` | Public marketing page; hero shows today's real art live from the API + install CTA. | ✅ **live** | 1 |
| **Social auto-publish** | `dreading-social` | Daily generated image + message auto-posted to Instagram / X. Growth flywheel, not interactive. | planned | 2 |
| **Kids PWA** | `dreading-kids` | Illustrated, simple-language, audio narration, one tiny question (`kids_reflection` already exists). | planned | 2 |
| **Parish embed widget** | `dreading-widget` | Web component / iframe parishes drop on their site. Distribution via parishes. | planned | 2 |
| **Native app** | `dreading-app` | React Native / Flutter, once the PWA validates demand and store presence is worth it. | later | 3 |

## Conventions (shared by all clients)
- Config the API base URL per environment; never hardcode data.
- Follow the harness flow: conventional commits, test-first, a green gate before commit.
- Treat the readings as authentic text; render enrichment (reflection/art) clearly labeled as such.

See [VISION.md](./VISION.md) for the product direction and [IDEAS.md](./IDEAS.md) for the wider backlog.
