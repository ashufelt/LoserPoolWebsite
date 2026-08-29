# Deployment contingency plan

**Status:** contingency, not the current plan. The site still deploys to Adam's
host. This exists so that switching is a decision rather than a scramble.

**Written:** 2026-08-29. **Season opener:** 2026-09-09 (Wednesday).

---

## Why this exists

The 2026 season code is finished and tested, but we have never verified it
running anywhere real. We do not know whether we can deploy it, and every
unknown is on someone else's infrastructure. If those unknowns resolve badly
close to the opener there is no time left to react, so the fallback is written
down now while it is cheap to think about.

## What we currently depend on Adam for

| Dependency | Status | Blocks the season? |
|---|---|---|
| Merging the PRs | unknown | **Yes** — nothing ships otherwise |
| Upload/deploy access to the host | unknown | **Yes** |
| Host PHP version (needs ≥ 7.4) | unknown | **Yes** if older |
| Outbound HTTPS from the host to ESPN | unknown | No — degrades results only |
| MySQL credentials, and rotating the committed one | known-bad, uncommitted change | No |
| DNS / the existing domain | Adam's | No, if we accept a new URL |

Only the first three are hard blockers. The rest degrade quality, not
availability.

### The one question worth asking first

Ask Adam to run this on the web host and send the output:

```
php bin/espn-check.php
php --version
```

It answers three of the six rows above in one go: the PHP version, whether
outbound HTTP works, and whether the cache directory is writable. If outbound
HTTP is blocked, the site still runs but **results never update all season**,
because only live data carries scores.

## Decision triggers

Execute this plan if **any** of these is true:

1. **No deploy access confirmed by Tuesday 2026-09-02.** That leaves 1.5 days
   of work plus slack before the opener.
2. Host PHP is older than 7.4.
3. Adam is unreachable or unwilling to merge before 09-05.
4. Outbound HTTP is blocked *and* he cannot run the snapshot refresh on a cron.

Trigger 1 is the real one; the date matters more than the reasoning. If we are
still waiting on 09-02, stop waiting.

## Target architecture

**A single PHP container with SQLite on a persistent volume, on Fly.io.**

```
  repo ──fly deploy──▶ one shared-cpu-1x machine (256MB)
                        ├── php:8.3-apache, docroot = htdocs/
                        ├── /data/pool.sqlite  (1GB volume)
                        └── outbound HTTPS ▶ ESPN
```

Why this shape:

- **It removes the database server**, which is the last thing tying us to
  Adam's infrastructure. Storage becomes a file in the container's volume.
- **Nothing is rewritten.** The app stays PHP. Only the storage layer changes,
  and that layer is now 7 public methods after the dead-code sweep.
- **It makes the storage layer testable for the first time.** SQLite runs
  in-memory in tests; MySQL never will in our setup. Today 55 tests cover the
  rules and data layer and *zero* cover storage, purely because the local PHP
  build has no `mysqli`.
- **It is portable onward.** If the pool ever moves to Cloudflare Workers, D1
  is SQLite — the schema and queries survive the move.

### Cost

| Item | Cost |
|---|---|
| shared-cpu-1x, 256MB, with auto-stop when idle | ~$2.02/mo if always on; only billed while running |
| 1GB persistent volume | $0.15/mo |
| Stopped machine rootfs | ~$0.15/GB/mo |
| `*.fly.dev` subdomain + TLS | free |

Realistically **$1–3/month**, and plausibly less: Fly's `auto_stop_machines`
stops idle machines and bills no CPU/RAM while stopped, and this pool sees
traffic in bursts a few days a week.

### Why not the alternatives

- **Render free tier** — free web services spin down after 15 minutes idle and
  take about a minute to wake. A minute-long wait when someone is making a
  Saturday-deadline pick is unacceptable. Persistent disks are also paid-only,
  so SQLite is not an option there; always-on starts at $7/mo.
- **Rewrite on Cloudflare Workers + D1** — genuinely $0 and the best long-term
  home, but it is a rewrite of the handlers, storage and templates in another
  language. Days of work we do not have before 09-09. Revisit in the offseason.
- **A $4–6/mo VPS** — more control, more ops: patching, TLS renewal, backups,
  a web server to configure. More expensive and more work than Fly for this.
- **Free PHP shared hosts** — free, but ad injection, unreliable uptime, and
  usually no outbound HTTP, which is precisely what we need for ESPN.

## Work required

Roughly **1.5 days**, sequenced so each step is independently useful. Steps 1
and 2 are worth doing *regardless* of whether we self-deploy.

1. **Storage abstraction** (~3h) — extract a `PickStore` interface from the 7
   remaining public methods on `SqlAccessController`. Keep the MySQL
   implementation. No behaviour change; this is the seam.
2. **`SqliteStore` + tests** (~4h) — a PDO/SQLite implementation behind the
   same interface, with real constraints the current schema lacks (`NOT NULL`,
   a unique key on `(username, week)`, a foreign key to users). First tests the
   storage layer has ever had, using an in-memory database.
3. **Container** (~2h) — `Dockerfile` on `php:8.3-apache`, docroot `htdocs/`,
   `pdo_sqlite` enabled, cache dir writable.
4. **Config from environment** (~1h) — store driver, SQLite path and DB
   credentials read from env, so nothing secret is committed. Removes the
   cleartext password problem rather than rotating it.
5. **`fly.toml` + volume** (~1h) — 1GB volume at `/data`, `auto_stop_machines`
   on, `min_machines_running = 0`, health check on `bin/espn-check.php`.
6. **Deploy and verify** (~1h) — run the full end-to-end check below.

### Migration data

**None.** Each season creates fresh empty tables
(`CREATE TABLE IF NOT EXISTS Users_26`), the site never displays previous
seasons, and every player re-registers annually — as they already did in 2024
and 2025. There is nothing to migrate, on this path or any other. This is the
single biggest reason the switch is cheap.

## Runbook

```bash
fly launch --no-deploy                 # generates fly.toml; pick a region
fly volumes create pool_data --size 1  # persistent SQLite storage
fly secrets set LP_STORE=sqlite LP_SQLITE_PATH=/data/pool.sqlite
fly deploy
fly ssh console -C "php /app/bin/espn-check.php"   # must report: live
```

Then, before telling anyone the URL:

1. Register a user; confirm it appears in the dropdown.
2. Make a pick; change it; confirm the change sticks.
3. Confirm a repeat pick is rejected.
4. Confirm the current week is right and the blocked teams match
   `php bin/pool-status.php`.
5. Load it on a phone: no horizontal scroll, the grid scrolls internally.

## Risks

- **SQLite pins us to one machine.** Volumes attach to a single machine, so do
  not scale past one. At ~60 users and ~1,000 rows a season that is not a real
  constraint, but it must not be scaled out absent-mindedly.
- **Cold starts.** With `min_machines_running = 0` the first request after idle
  waits for the container — around a second for this image, versus Render's
  minute. If it ever feels slow, `min_machines_running = 1` costs ~$2/mo.
- **A new URL.** `loserpool.fly.dev` rather than the existing domain. Since
  everyone re-registers each season anyway, nobody loses anything except
  familiarity. A custom domain can be pointed later, and that still needs Adam.
- **Backups.** SQLite makes this easy and also easy to forget: add
  `fly ssh console -C "sqlite3 /data/pool.sqlite .dump"` to a weekly habit, or
  a scheduled job. MySQL on Adam's host has the same gap today, unmonitored.

## What we need from Adam either way

Regardless of where this deploys:

1. Run `php bin/espn-check.php` and `php --version` on the host (above).
2. Rotate the MySQL password committed at `htdocs/SqlAccess/conn_info.php`, and
   take that file out of the repo. Only he can do this.
3. Decide the tie rule: ESPN reports a tie as `winner: false` for both teams,
   which would score as a losing pick. The code currently treats a tie as
   undecided. Confirm that is what the pool wants.
