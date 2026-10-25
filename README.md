# ClutchBase

ClutchBase is a Counter-Strike 2 community catalog and team strategy planner. It covers skins, knives, gloves, cases, stickers, agents, collections, search, Steamworks player stats, price observations, accounts, an admin importer, and saved five-player round plans.

The seeded catalog is original demo data. Prices, float ranges, drop rates, and tactical lineups in that dataset are not live market facts and are not claimed to be verified.

## Stack

| Piece | Version used here |
| --- | --- |
| PHP | 8.2+ (`^8.2`). Laravel 13 needs PHP 8.3, so this project stays on Laravel 12. |
| Laravel | 12 |
| Vue | 3 (Composition API, TypeScript) |
| Inertia | Laravel adapter 3, `@inertiajs/vue3` |
| MySQL | 8+ in production. Local XAMPP here is MariaDB 10.4, which runs the same schema. |
| Tailwind CSS | 4 via `@tailwindcss/vite` |
| Vite | 7 |
| Tests | PHPUnit 11, Vitest 5 |
| Style | Laravel Pint, ESLint, Prettier |

Redis is optional. The default cache, session, and queue drivers are `database`. Switch `CACHE_STORE=redis` when the Redis PHP extension is installed.

## Setup

Requirements: PHP 8.2 with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `bcmath`, `fileinfo`, and `curl`; Composer; Node.js 20+; MySQL 8 or MariaDB 10.4+.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the database, then set `DB_*` in `.env`:

```sql
CREATE DATABASE clutchbase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

Seeded accounts (local demo only):

- `admin@clutchbase.test` / `password` — administrator
- `user@clutchbase.test` / `password` — member with a personal team

Change or remove these before any shared deployment.

## Commands

```bash
php artisan test
npm test
npm run typecheck
npm run lint
vendor/bin/pint
php artisan prices:sync
```

`prices:sync` and the hourly `SyncMarketPrices` schedule call the configured price provider. The demo provider is not configured and returns no quotes, so it does not invent prices.

A queue worker is required when `QUEUE_CONNECTION=database`:

```bash
php artisan queue:work
php artisan schedule:work
```

## Environment

| Variable | Purpose |
| --- | --- |
| `PRICE_PROVIDER` | `demo` (default, no remote calls) or `http` |
| `PRICE_API_URL` | Base URL for the HTTP quote provider |
| `PRICE_API_TOKEN` | Bearer token. Server-only. Never expose it to Vue. |
| `PRICE_API_TIMEOUT` | HTTP timeout in seconds |
| `SUBSCRIPTION_PROVIDER` | Leave empty. `manual` records entitlements without checkout. |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Reserved. No Stripe SDK is installed. |
| `INERTIA_SSR_ENABLED` | `false` unless the SSR Node process is running |
| `CACHE_STORE` | `database` locally, `redis` when available |

## Catalog honesty

- `attribute_status` is `demo`, `unverified`, or `verified`.
- Demo seed rows use `demo`. Their images are original SVG placeholders.
- Float ranges, StatTrak, and Souvenir stay null until an administrator sets them. Null is shown as unavailable, not as false.
- Prices live in `price_histories` (append-only) and `item_prices` (latest snapshot). Money is `decimal(12,2)`.
- A price is treated as verified only when `is_verified` is true and `is_demo` is false. Demo observations cannot be marked verified.
- Sort and filter by price use verified, non-demo observations only.
- Product JSON-LD is emitted only for a verified item that also has a verified non-demo price. Other pages use breadcrumb structured data.
- Case contents do not include drop rates. The game files do not publish them, so every case page says the drop rate is unknown.

## HTTP price provider

Set `PRICE_PROVIDER=http`. The server sends:

`GET {PRICE_API_URL}/quotes`

with `Authorization: Bearer {PRICE_API_TOKEN}`.

Expected JSON:

```json
{
  "quotes": [
    {
      "subject_type": "item",
      "subject_slug": "ak-47-harbor-line",
      "exterior": "factory-new",
      "variant": "normal",
      "marketplace": "demo-board",
      "amount": "12.40",
      "currency": "USD",
      "observed_at": "2026-10-25T12:00:00Z",
      "verified": true
    }
  ]
}
```

`subject_type` is `item` or `case`. `marketplace` must already exist. Unknown marketplaces, exteriors, and subjects are skipped. `verified: true` is stored as a verified observation only when the provider is not the demo provider.

## Schema

Catalog items of every type share `items` (`skin`, `knife`, `glove`, `sticker`, `agent`). Knives and gloves are not separate tables. `collections` is the `ItemCollection` model. Cases are `WeaponCase` on `cases`.

| Table | Role |
| --- | --- |
| `users` | Accounts. `role` is `user` or `admin` and is not mass-assignable. |
| `rarities`, `exteriors`, `weapons` | Lookup data. |
| `collections`, `items`, `item_exteriors` | Finishes, collections, and stored exterior rows. |
| `cases`, `case_contents` | Cases and obtainable items. Probabilities are optional. |
| `marketplaces`, `data_sources` | Where a price came from. |
| `price_histories`, `item_prices` | Append-only observations and the latest snapshot. Polymorphic `item` or `case`. |
| `favorites`, `wishlists`, `wishlist_items`, `price_alerts`, `saved_filters` | Account lists. Free plan: one wishlist, no alerts. |
| `simulated_inventories`, `simulated_inventory_items`, `case_openings` | Unused tables left from the removed case simulator. |
| `import_jobs`, `slug_redirects`, `guides`, `featured_placements`, `search_queries` | Imports, SEO redirects, guides, homepage, search log. |
| `subscriptions` | Manual premium grants. Checkout stays closed until a real provider is configured. |
| `teams`, `team_members`, `team_invites`, `strategy_folders` | Team playbooks. |
| `map_assets` | Original schematic maps. Not Valve radar images. |
| `strategies`, `strategy_players`, `strategy_markers`, `strategy_paths`, `strategy_utility_actions`, `strategy_timeline_steps` | A plan and its normalized pieces. Coordinates are 0–1. |
| `strategy_shares` | SHA-256 of the share token. The plain token is only in the URL. |
| `strategy_collaborators`, `strategy_versions` | Edit grants and revision snapshots. |

Saving a strategy locks the row, requires the current revision, replaces child rows, and writes a version inside one transaction. A stale revision returns HTTP 409.

Private account, admin, team, strategy, login, and register pages send `noindex`. Steam profile lookups and public search pages use `noindex,follow`. `robots.txt` and `sitemap.xml` are Laravel routes.

## Strategy planner

Captains pick a map, side, objective, and an example template or a blank plan. The editor places five players, utility markers, paths, and a timeline. Templates are labeled examples, not verified lineups.

Sharing is private, team-only, or link access, with view or edit permission. Edit links add the signed-in user as a collaborator. Tokens can be revoked. Shared plans are printable and exportable as JSON. They are not in the sitemap.

## Admin

`/admin` requires the `admin` role. Administrators can create and delete catalog records, upload images, import `database/data/demo-catalog.csv`, review import errors, grant roles and manual premium, trigger a price sync, and feature homepage items. Imports are rate limited and validated per row.

CSV columns: `record_type` (`item`, `case`, `case_content`), `name`, `slug`, `type`, `weapon_slug`, `rarity_slug`, `collection_slug`, `description`, `case_slug`, `item_slug`, `drop_group`, `is_special`, `min_float`, `max_float`, `has_stattrak`, `has_souvenir`.

## Deployment

- `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and HTTPS `APP_URL`.
- Run `php artisan migrate --force`, `php artisan storage:link`, and `npm run build`.
- Point the web root at `public/`.
- Run a queue worker and the scheduler.
- Back up the MySQL database and `storage/app/public`.
- Do not commit `.env`.
- Set `CACHE_STORE=redis` when Redis is available. Do not put user-specific data in public cache keys; catalog cache is versioned and public only.
- Leave `INERTIA_SSR_ENABLED=false` until `php artisan inertia:start-ssr` is supervised. The initial HTML is still rendered by Laravel with Inertia page props.
- Rate limits: search 60/min per IP, Steam page 30/min per IP, auth 10/min per IP, imports 5/min per user.
- `STEAM_WEB_API_KEY` is optional and server-only. `/steam` uses GetNumberOfCurrentPlayers, GetNewsForApp, and GetGlobalAchievementPercentagesForApp without it. Profile lookup also uses ResolveVanityURL, GetPlayerSummaries, GetSteamLevel, GetPlayerBans, GetOwnedGames, GetFriendList, GetRecentlyPlayedGames, GetPlayerAchievements, GetUserStatsForGame, and GetSchemaForGame. Get a key at https://steamcommunity.com/dev/apikey. The app does not call publisher-only or market methods.

## What is implemented

- Homepage, catalogs, detail pages, collections, weapons, guides, and search with filters, sort, and pagination.
- Favorites, one free wishlist, simulated inventory, and case-opening history.
- Admin catalog, CSV import, users, sources, and featured placements.
- Price history storage and an HTTP provider interface.
- Server-side premium checks for extra history, exports, saved filters, and alerts.
- Strategy planner with teams, templates, save/reload, sharing, print, and export.
- SEO titles, canonical URLs, Open Graph, sitemap, robots, and redirects when a slug changes.

## Limits

- Seeded rows are a small fictional demo. `php artisan catalog:import-cs2` replaces the public catalog with CS2 item definitions from the game files Steam distributes (names, rarities, float ranges, wears, collections, crate contents, and inventory images). Drop rates are left unknown.
- Steam Market prices are not imported. Valve does not publish a price API for app 730. Sites that show a euro price next to a Steam Market listing count collect Community Market pages on their own schedule. The Steam Subscriber Agreement forbids automating those processes, so this app does not. A price appears only after `PRICE_PROVIDER=http` points at an authorized source.
- No payment checkout. Premium is a manual database grant.
- No real-time multiplayer editing. Conflicting saves return 409.
- Redis and Inertia SSR are off in the default local setup.
- Map art is original schematics, not official radar images.
- The admin item screen creates and deletes records; finer field-by-field editing of an existing finish is still limited.
