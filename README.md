# Loser Pick'em

A season-long NFL pool: each week you pick one team you expect to **lose**.

Live: <https://loser-pool-2026.fly.dev>

## Rules

- Pick one team per week that you think will lose. **No repeat picks** all season.
- **A tie eliminates you.** You must pick a team that loses, and a team that
  ties did not lose.
- **Teams playing Wednesday, Thursday or Friday cannot be picked** — their games
  start before the deadline. **Saturday games are pickable.**
- Teams on a bye cannot be picked.
- Picks are open Tuesday through Saturday, and lock Sunday and Monday.
- Other players' picks for the current week stay hidden until the slate starts.
- **Buy back in: week 1 only.** Recorded with `bin/buyback.php`, since an
  eliminated player can still submit picks — continuing to play does not show
  that anyone bought back.
- **Final four or fewer may split the pot, by unanimous agreement.**

The last two are run by the commissioner and are not enforced in code.

## How it works

PHP 8 with HTMX on the front end and SQLite for storage. No build step, no
framework, and no database server to run.

Schedule, byes, kickoff times and results come from ESPN's public scoreboard
API. Earlier versions kept that data as hand-typed PHP arrays that somebody
edited every week; now the only thing edited between seasons is one config
file.

```
htdocs/          the docroot -- endpoints and assets, nothing else
  index.html     the page
  week.php       current week (HTMX)
  health.php     is this deployment actually working?
  picks/ users/ teams/   HTMX endpoints
src/             application code, not web-reachable
  Nfl/           ESPN payloads -> Schedule/Game value objects
  Pool/          the rules, the season config, week resolution
  Storage/       PoolStore interface + the SQLite implementation
  Handlers/      request handling, HTML rendering
data/snapshots/  committed schedule data: the offline fallback
tests/           unit + integration suites
bin/             CLI tools
```

Application code lives beside the docroot rather than inside it, so it is
unreachable over HTTP by layout rather than by a rule that could be
misconfigured.

### Reading the season from ESPN

Lookups walk a ladder and never throw: **fresh cache → live fetch → stale
cache → committed snapshot**. A total failure yields an empty schedule, which
blocks no teams and colours no results, so the page still renders.

Only live data carries scores, so a deployment that cannot reach ESPN will
serve a working site whose results never update. That failure is silent by
design, which is why `/health.php` exists to report it.

## Running it

Requires PHP 8 with `pdo_sqlite`. No Composer needed to run — that is dev-only.

```
php -S localhost:8000 -t htdocs
```

Tests:

```
composer install
vendor/bin/phpunit
```

CLI tools:

```
php bin/pool-status.php all      # every week's blocked teams
php bin/espn-check.php           # can this machine reach ESPN?
php bin/refresh-snapshots.php    # re-record the offline schedule data
php bin/buyback.php <username>   # record a week 1 buy-back
php bin/buyback.php --list       # who has bought back
```

## Deploying

Pushing to `master` deploys to Fly.io via GitHub Actions, gated on the test
suite, and then asserts the deployed site reports healthy. Needs a
`FLY_API_TOKEN` repository secret.

Manually:

```
flyctl deploy --remote-only
```

One machine with SQLite on a mounted volume. **SQLite pins this to a single
machine** — a volume attaches to one machine, so do not scale past one.

## Rolling over to a new season

Edit `src/Pool/SeasonConfig.php`: `YEAR`, `TABLE_SUFFIX` (the last two digits
of the year) and `WEEK_ONE_START` (the Tuesday before the opener). Then
`php bin/refresh-snapshots.php`.

Each season gets its own tables, created on first request, so a rollover
starts empty and everyone re-registers. Tests guard the mistakes that would
otherwise fail silently — bumping the year without the table suffix points the
new season at last season's data.

## History

Originally by Adam Shufelt as PHP + MySQL, at
[ashufelt/LoserPoolWebsite](https://github.com/ashufelt/LoserPoolWebsite).
