# Theatergruppe Freispiel

A modern Symfony web application for the theater group "Theatergruppe Freispiel", build with ❤️ and modern web technologies.

- Framework: Symfony 8.1 (PHP 8.4+)
- Frontend: Webpack Encore, Stimulus, Bootstrap 5, Twig
- Database: MariaDB (SQLite defaults also supported)
- Tooling: Composer, Yarn, Docker and Docker Compose

## 📁 Project structure

```
tg-freispiel.de/
├── assets/         # Frontend sources (TS, SCSS, Stimulus, images)
├── bin/            # Console & Docker wrapper scripts
├── config/         # Symfony configuration
├── docs/           # Project docs
├── migrations/     # Doctrine migrations
├── public/         # Web root
├── src/            # PHP application code (Controller, Entity, Service, ...)
├── templates/      # Twig templates
├── tests/          # PHPUnit tests
└── *.sh            # Dev, deploy, Docker and quality helper scripts
```

For the detailed layout see [docs/structure.md](docs/structure.md).

## ✅ Local development (recommended)

### Prerequisites

- PHP 8.4+
- Composer
- Node.js 24+ and Yarn
- Symfony CLI (optional, for local web server)

### Quick start

You can use the helper script which installs dependencies, clears cache, builds assets and starts both the Webpack dev watcher and Symfony server:

```bash
./develop.sh
```

If you prefer to run steps manually:

```bash
# 1) Install dependencies
yarn install
composer install

# 2) Clear cache (dev)
php bin/console cache:clear

# 3) Build assets in watch mode
yarn encore dev --watch

# 4) Start a local web server (one of)
symfony server:start --no-tls --port=8000
# or
php -S 127.0.0.1:8000 -t public
```

Open the app:

```
http://127.0.0.1:8000
```

### Configure environment variables

All configuration is via environment variables. Typical keys:

- APP_ENV: dev | prod (default: dev)
- APP_SECRET: random string (generate via `php bin/console regenerate-app-secret`)
- DEFAULT_URI: base URL used for URL generation in CLI contexts (e.g. http://127.0.0.1)
- LOCK_DSN: lock store DSN (default in dev: `flock`). Examples: `flock`, `semaphore`, `redis://localhost:6379`
- DATABASE_URL: Doctrine DSN
  - SQLite (default): `DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"`
  - MariaDB/MySQL: `DATABASE_URL="mysql://user:pass@127.0.0.1:3306/db?serverVersion=10.11.2-MariaDB&charset=utf8mb4"`
  - Postgres: `DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/db?serverVersion=17&charset=utf8"`
- MESSENGER_TRANSPORT_DSN: default `doctrine://default?auto_setup=0` (use `sync://` for simple dev)
- Mail settings (compose into MAILER_DSN): MAIL_SCHEME, MAIL_HOST, MAIL_ENCRYPTION, MAIL_PORT, MAIL_USER, MAIL_PASSWORD

Security: Do not commit production secrets. Prefer real env vars or Symfony Secrets Vault for prod.

## 🐳 Docker development

If you prefer Docker for a fully containerized setup, see:

- docs/docker.md

### Docker and Docker Compose

To start the application with services like _MariaDB_, _Adminer_ and _PHPMyAdmin_ the **Docker Compose** is used:

```shell
# Start only the basic web application
docker compose -p tg-freispiel -f docker-compose.yml up -d --build --force-recreate
# Start the basic web application with MariaDB
docker compose -p tg-freispiel -f docker-compose.yml -f docker-compose.mariadb.yml up -d --build --force-recreate
# Start the full environment with MariaDB, Adminer and PHPMyAdmin
docker compose -p tg-freispiel -f docker-compose.yml -f docker-compose.mariadb.yml -f docker-compose.adminer.yml -f docker-compose.phpmyadmin.yml up -d --build --force-recreate
```

### Database migrations (inside the Docker container)

All Doctrine console commands run inside the `php` container of the running dev stack:

```shell
# Show which migrations are available/executed
docker compose -p tg-freispiel exec php php bin/console doctrine:migrations:status

# Migrate UP to the latest version
docker compose -p tg-freispiel exec php php bin/console doctrine:migrations:migrate --no-interaction

# Migrate DOWN one step (revert the most recent migration)
docker compose -p tg-freispiel exec php php bin/console doctrine:migrations:migrate prev --no-interaction

# Migrate UP/DOWN a single specific migration
docker compose -p tg-freispiel exec php php bin/console doctrine:migrations:execute --up 'DoctrineMigrations\Version20260712000000'
docker compose -p tg-freispiel exec php php bin/console doctrine:migrations:execute --down 'DoctrineMigrations\Version20260712000000'

# Generate a new migration from entity changes / verify schema is in sync
docker compose -p tg-freispiel exec php php bin/console doctrine:migrations:diff --no-interaction
docker compose -p tg-freispiel exec php php bin/console doctrine:schema:validate
```

> ⚠️ On macOS the MariaDB data directory is a bind mount (`./mariadb/data`). `ALTER`/`DROP TABLE`
> statements can occasionally crash the MariaDB container (it restarts automatically, and the
> statement usually **has** been applied). Check the actual state with
> `doctrine:migrations:status` / `doctrine:schema:validate` before re-running a failed migration.

### Debugging with Adminer, phpMyAdmin and Mailpit

The dev stack ships three debugging UIs (host ports are configured in `.env`):

| Tool       | URL                   | Purpose                                |
| ---------- | --------------------- | -------------------------------------- |
| Adminer    | http://127.0.0.1:8091 | Lightweight DB client (quick queries)  |
| phpMyAdmin | http://127.0.0.1:8092 | Full-featured DB client                |
| Mailpit    | http://127.0.0.1:8025 | Catches all outgoing mail from the app |

**Adminer** — log in with: System `MySQL`, Server `mariadb` (the compose service name, not
localhost), user/password/database from `.env` (`DB_USER`, `DB_PASSWORD`, `DB_NAME`). Useful to
inspect form submissions (`form_contact`, `form_registration`), the `date` table or
`doctrine_migration_versions` after a migration.

**phpMyAdmin** — pre-wired to the `mariadb` service; log in with `DB_USER`/`DB_PASSWORD` (or
`root`/`DB_ROOT_PASSWORD` for schema-level work). Prefer it over Adminer for browsing/editing
rows, exports and index/FK inspection.

**Mailpit** — the dev `MAILER_DSN` points at the `mailer` service, so no real mail leaves the
machine. Every mail the app sends (contact form, registration confirmations) shows up in the web
UI at http://127.0.0.1:8025 with full HTML/text source and headers. SMTP listens on
`127.0.0.1:1025` if you want to test with an external client. The REST API is handy in scripts,
e.g. `curl http://127.0.0.1:8025/api/v1/messages`.

## 🧹 Code Quality & Linting

The project includes several linting and formatting tools to ensure code quality:

### Run all linters (recommended)

```bash
./lint.sh
```

This aggregate script runs:

- CSS/SCSS linting with auto-fix (Stylelint)
- TypeScript type checking
- Twig template linting
- PHP code formatting (PHP-CS-Fixer)

### Individual linting commands

**CSS/SCSS (Stylelint):**

```bash
# Check only
yarn lint:css

# Auto-fix issues
yarn lint:css:fix
```

**TypeScript type checking:**

```bash
# One-time check
yarn tsc:check

# Watch mode (continuous)
yarn tsc:watch
```

**Twig templates:**

```bash
php bin/console lint:twig templates
```

**PHP code formatting (PHP-CS-Fixer):**

```bash
./php-cs-fixer.sh
```

This installs PHP-CS-Fixer locally to `php-cs-fixer/` directory and runs formatting on your PHP code.

## 🧪 Testing

### PHP Unit Tests

Run the full PHPUnit test suite:

```bash
./phpunit.sh
```

Or directly:

```bash
./vendor/bin/phpunit tests
```

### Frontend Build Verification

Verify that all frontend assets compile without errors:

```bash
# Development build
yarn dev

# Production build (includes optimizations)
yarn build
```

### Complete Quality Check

For a full quality check before committing or deploying, run:

```bash
# 1. Run all linters
./lint.sh

# 2. Run tests
./phpunit.sh

# 3. Verify production build
yarn build
```

## 🛠 Helper scripts

### develop.sh

Local development helper that:

- Installs dependencies (Yarn and Composer)
- Clears Symfony cache (dev)
- Builds front-end assets
- Starts Webpack Encore watch and Symfony local server in parallel

Usage:

```bash
./develop.sh
```

Notes:

- Requires Node/Yarn (or NPM), PHP and Composer available on your machine.
- Press Ctrl+C to stop both background processes.

### deploy.sh

Production deployment helper that:

- Ensures production env (APP_ENV=prod)
- Installs Node deps, builds assets (prod)
- Installs Composer deps (no-dev, optimized)
- Runs database migrations (can be skipped)
- Clears and warms Symfony cache (prod)

Usage:

```bash
# Default (runs migrations)
./deploy.sh

# Skip migrations
SKIP_MIGRATIONS=true ./deploy.sh

# Skip composer auto-scripts (if you need to)
SKIP_COMPOSER_AUTOSCRIPTS=true ./deploy.sh
```

### bin/command, bin/php and bin/yarn (Docker dev stack)

Short wrappers for the running Docker dev stack — no local PHP/Node required. All load `.env`
(project name `APP_NAME`, default `tg-freispiel`) and support `COMPOSE=mutagen` to use
`mutagen-compose` instead of `docker compose`.

- `bin/command` runs Symfony console commands in the `php` service (prefixes `bin/console`)
- `bin/php` runs raw PHP in the `php` service (e.g. vendor binaries)
- `bin/yarn` runs Yarn in the `node` service

```bash
./bin/command app:user:list
./bin/command cache:clear
./bin/php vendor/bin/phpstan --memory-limit=1G
./bin/php vendor/bin/php-cs-fixer fix --dry-run
./bin/yarn lint:css
./bin/yarn tsc:check
```

The stack must be running (`./docker-start.sh`), since the scripts `exec` into the existing
containers.

## ⚙️ App console commands

Naming follows `app:<domain>:<action>` with CRUD wording; the class name matches the command
name (e.g. `app:date:create` → `DateCreateCommand`). More actions (update, delete) will come
with the admin area.

### Running commands: local PHP vs. Docker container

With a local PHP setup (develop.sh option 1) run commands directly:

```bash
php bin/console app:date:list
```

With the Docker dev stack (develop.sh option 2) there is no PHP on the host path — use the
`bin/command` wrapper, which runs `bin/console` inside the `php` container of the running stack:

```bash
./bin/command app:date:list
```

Or address the container manually:

```bash
# One-off command
docker compose -p tg-freispiel exec php php bin/console app:date:list

# In scripts/CI (no TTY): add -T
docker compose -p tg-freispiel exec -T php php bin/console app:date:list

# Or open a shell in the container and work as usual
docker compose -p tg-freispiel exec php sh
php bin/console app:date:list
```

The examples below use the local form; with Docker replace `php bin/console` with
`./bin/command`.

| Command                    | Description                                              |
| -------------------------- | -------------------------------------------------------- |
| `app:contact:list`         | List stored contact form submissions                     |
| `app:contact:mail-preview` | Send preview emails (owner + visitor) to check templates |
| `app:date:list`            | List public dates shown on /termine                      |
| `app:date:create`          | Create a public date (one-off or recurring)              |
| `app:registration:list`    | List stored trial-session registrations                  |
| `app:user:create`          | Create a user for the upcoming admin area                |
| `app:user:list`            | List users of the upcoming admin area                    |
| `app:secret`               | Regenerate the APP_SECRET in the project env file        |

### List submissions and dates

Both list commands support `--csv` for exports; the submission lists also take `--limit` (default 100).

```bash
php bin/console app:contact:list
php bin/console app:registration:list --limit=20
php bin/console app:date:list            # all dates, including inactive/past
php bin/console app:date:list --upcoming # exactly what the website shows
php bin/console app:registration:list --csv > registrations.csv
```

### Create a date

Run without arguments to be interviewed for all values (title, one-off/recurring, schedule,
description, sort order, active):

```bash
php bin/console app:date:create
```

Or pass everything on the command line — then provide exactly one of `--starts-at` (one-off) or
`--recurrence` (recurring).

```bash
# One-off date
php bin/console app:date:create "Probestunde zum Reinschnuppern" --starts-at="2026-09-12 10:30"

# Recurring date
php bin/console app:date:create "Proben" --recurrence="jeden Dienstag um 19 Uhr"

# Optional: --description="..." --sort=2 --inactive
```

### Create a user

Run without arguments to be interviewed for all values (email, admin role, name, password):

```bash
php bin/console app:user:create
```

Or pass everything on the command line. The password is hashed before storing; omit
`--password` to be asked interactively (hidden input, min. 8 characters).

```bash
php bin/console app:user:create admin@example.com "Admin Name" --admin
php bin/console app:user:create member@example.com "Member Name" --password="s3cret-Pass!"
```

### Preview emails

Sends the contact form emails (owner + visitor copy) to verify the templates — in the dev stack
they land in Mailpit (http://127.0.0.1:8025).

```bash
php bin/console app:contact:mail-preview            # uses MAIL_TO_ADDRESS from .env
php bin/console app:contact:mail-preview me@example.com "My Name"
```

## 🧰 Symfony commands

Moved to:

- docs/symfony.md

## 🆘 Troubleshooting

Troubleshooting has been split by topic:

- Docker: docs/docker.md
- Symfony: docs/symfony.md
- Database: docs/database.md

## 📄 License

See [license](license) for details.

## 🤝 Contact

For questions or issues, please open a GitHub issue in this repository.
