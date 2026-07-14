# DReading — Clients

The API and scraper are the platform; **each client is its own repo** that consumes the reading endpoints. This is the client map — what to build, in what order, and where it lives.

All clients read the same API (`dreading-api`), so they only need its base URL configured. Nothing here re-implements the readings; they render what the API serves (readings + the enrichment fields: `message`, `reflection`, `kids_reflection`, `questions`, `image_prompt`).

| Client | Repo | What it is | Status | Priority |
| --- | --- | --- | --- | --- |
| **Web app (PWA)** | `dreading-web` | Installable phone-first web app: today's reading + reflection + image + share, kids mode, listen (TTS), streak, date navigation, offline. The flagship interactive client. | **building** | 1 |
| **Telegram bot** | `dreading-bot` | Posts the daily reading + message (+ image later) to a channel. Zero-UI reach + engagement. | **scaffolding** | 1 |
| **Social auto-publish** | `dreading-social` | Daily generated image + message auto-posted to Instagram / X. Growth flywheel, not interactive. | planned | 2 |
| **Landing / marketing** | `dreading-landing` | Public marketing page: what it is, install CTA, screenshots — the advertising surface Carlos flagged as important. | planned | 2 |
| **Native app** | `dreading-app` | React Native / Flutter, once the PWA validates demand and store presence is worth it. | later | 3 |

## Conventions (shared by all clients)
- Config the API base URL per environment; never hardcode data.
- Follow the harness flow: conventional commits, test-first, a green gate before commit.
- Treat the readings as authentic text; render enrichment (reflection/art) clearly labeled as such.

See [VISION.md](./VISION.md) for the product direction and [IDEAS.md](./IDEAS.md) for the wider backlog.
