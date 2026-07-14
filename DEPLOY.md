# Deployment topology

How the `dreading-*` repos deploy, and **what is / isn't Cloudflare-native**. TL;DR: Cloudflare hosts the static PWA, the AI (text + image) and image storage; the Laravel API, the Python scraper and MongoDB live elsewhere, with Cloudflare in front as CDN/DNS.

## Map

| Piece | Repo | Where it runs | Cloudflare role |
| --- | --- | --- | --- |
| PWA | `dreading-web` | **Cloudflare Pages** (static, no build) | native host |
| API | `dreading-api` (Laravel/PHP) | container host (Fly.io / Render / Railway / VPS) + **MongoDB Atlas** | in front: DNS, CDN/cache, WAF, or **Tunnel** for self-host |
| Scraper + enrichment | `dreading-scrape` (Python) | **GitHub Actions** cron (already) | *calls* Workers AI (text) + Flux→R2 (image) |
| Bot | `dreading-bot` (Python) | GitHub Actions cron — or rewrite as a **Worker** with a cron trigger | optional native host |
| Daily images | (produced by scraper) | **Cloudflare R2** | native storage, public URL |
| Database | — | **MongoDB Atlas** (free tier) | none (D1 is SQL, would be a rewrite) |

Cloudflare is **not** a drop-in host for Laravel (no PHP at the edge) or MongoDB. Going "100% Cloudflare" would mean rewriting the API as a Worker over Atlas' Data API — a real option later, not required now.

## AI (Workers AI)

Daily **batch** (one run/day after scraping), so free tiers cover it.

- **Text** — reflection / kids version / message / questions. Already implemented in `dreading-scrape/services/enrich.py` (`LLMProvider`, OpenAI-compatible). Turn it on with env only, no code change:
  ```
  ENRICH_PROVIDER=llm
  ENRICH_API_URL=https://api.cloudflare.com/client/v4/accounts/<ACCOUNT_ID>/ai/v1/chat/completions
  ENRICH_MODEL=@cf/meta/llama-3.3-70b-instruct-fp8-fast
  ENRICH_API_KEY=<CF API token>
  ```
- **Image** — daily illustration (planned). Model `@cf/black-forest-labs/flux-1-schnell` (or FLUX.2 [dev]) via `POST …/ai/run/<model>` with the reading's `image_prompt`; store the bytes in **R2**; save the public URL as `image_url` on the reading. Build after R2 is enabled.

## What you need to do (needs your Cloudflare account / dashboard)

1. **Enable R2** in the Cloudflare dashboard (currently off — the MCP can create the bucket once it's enabled).
2. Create a **Cloudflare API token** with `Workers AI: Read/Run` (+ `R2: Edit` for images). Grab your **Account ID**.
3. **PWA → Pages**: from `dreading-web`, `npx wrangler pages deploy .` (or connect the `dreading-pwa` repo in the Pages dashboard — no build command, output = repo root). Then point it at the deployed API with `?api=` or by editing `config.js`.
4. **API host**: deploy the Laravel Docker image to Fly.io/Render/Railway (or a VPS) with a real `DBM_URI` to MongoDB Atlas; put it behind Cloudflare (proxied DNS record, or `cloudflared tunnel` for self-host). Set `TZ=Europe/Madrid` if you want `/today` to match Spain.
5. **Scraper/bot secrets**: add `ENRICH_*` (above), `DB_*`/`UPSTACK_*` and `TELEGRAM_*` as GitHub Actions repository secrets.

See [VISION.md](./VISION.md) · [IDEAS.md](./IDEAS.md) · [CLIENTS.md](./CLIENTS.md).
