# Theatergruppe Freispiel - Web Hosting and Infrastructure Development

A modern Symfony web application for the theater group "Theatergruppe Freispiel", build with ❤️ and modern web technologies.

- Framework: Symfony 7.3 (PHP 8.3+)
- Frontend: Webpack Encore, Stimulus, Bootstrap 5, Twig
- Database: MariaDB (SQLite defaults also supported)
- Tooling: Composer, Yarn, Docker and Docker Compose

## 📁 Project structure

```
tg-freispiel.de/
├── develop.sh                    # Local dev helper (installs deps, builds, runs dev services)
├── deploy.sh                     # Production deploy helper (builds, installs, migrates, warms cache)
├── assets/
│   ├── app.ts                    # TS entry (Webpack Encore)
│   ├── bootstrap.ts              # Stimulus bootstrap (TS)
│   ├── controllers/              # Stimulus controllers
│   ├── controllers.json          # Stimulus bridge entry
│   ├── scripts/
│   │   ├── main.ts               # Main UI behaviors (TS)
│   │   └── theme-toggle.ts       # Theme handling (TS)
│   └── styles/
│       ├── app.css               # App-specific styles
│       ├── theme.css             # Shared theme styles
│       ├── theme-light.css       # Light theme overrides
│       └── theme-dark.css        # Dark theme overrides
├── config/                       # Symfony configuration
├── docs/                         # Project docs
│   ├── docker.md                 # Docker installation & usage
│   ├── symfony.md                # Symfony commands & troubleshooting
│   └── database.md               # Database troubleshooting
├── public/                       # Web root
│   ├── index.php                 # Front controller
│   ├── build/                    # Compiled assets (generated)
│   └── bundles/
├── src/
│   ├── Controller/
│   │   ├── HomeController.php    # Homepage
│   │   └── ContactController.php # Contact form page
│   ├── Entity/
│   │   ├── FormContactEntity.php        # Contact form entity
│   │   └── FormSubmissionMetaEntity.php # Form submission metadata
│   ├── Form/
│   │   └── FormContactType.php          # Contact form type
│   ├── Repository/
│   │   └── FormContactRepository.php    # Contact form repository
│   └── Service/
│       └── FormContactService.php       # Contact form service
├── templates/
│   ├── base.html.twig                   # Base layout with theme support
│   ├── _partials/
│   │   └── navbar.html.twig             # Navigation with theme switcher
│   ├── home/
│   │   └── index.html.twig              # Modern homepage
│   └── contact/
│       └── index.html.twig              # Contact form page
├── migrations/
├── vendor/                              # Composer deps (generated)
├── var/                                 # Cache & logs (generated)
├── composer.json
├── package.json
└── readme.md
```

## ✅ Local development (recommended)

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ and Yarn
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
    - Postgres: `DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/db?serverVersion=16&charset=utf8"`
- MESSENGER_TRANSPORT_DSN: default `doctrine://default?auto_setup=0` (use `sync://` for simple dev)
- Mail settings (compose into MAILER_DSN): MAIL_SCHEME, MAIL_HOST, MAIL_ENCRYPTION, MAIL_PORT, MAIL_USER, MAIL_PASSWORD

Security: Do not commit production secrets. Prefer real env vars or Symfony Secrets Vault for prod.

## 🐳 Docker development

If you prefer Docker for a fully containerized setup, see:

- docs/docker.md

### Docker and Docker Compose

To start the application with services like *MariaDB*, *Adminer* and *PHPMyAdmin*  the **Docker Compose** is used:

```shell
# Start only the basic web application
docker compose -p tg-freispiel -f docker-compose.yml up -d --build --force-recreate
# Start the basic web application with MariaDB
docker compose -p tg-freispiel -f docker-compose.yml -f docker-compose.mariadb.yml up -d --build --force-recreate
# Start the full environment with MariaDB, Adminer and PHPMyAdmin
docker compose -p tg-freispiel -f docker-compose.yml -f docker-compose.mariadb.yml -f docker-compose.adminer.yml -f docker-compose.phpmyadmin.yml up -d --build --force-recreate
```

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
