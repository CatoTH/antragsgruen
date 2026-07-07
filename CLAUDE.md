# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is Antragsgrün

Antragsgrün (motion.tools) is a PHP/Yii2 web application for NGOs and political parties to collaboratively manage motions, amendments, resolutions, votings, and speaking lists. Current version: 4.17.1.

## Commands

### Build / Assets
```bash
pnpm install                   # Install JS dependencies
pnpm run build                 # Compile SCSS/JS/Vue (runs Vite, see vite.config.js)
pnpm run watch                 # Watch and recompile on change
docs/create-static-resources.php dev   # Create static resource manifests (run after pnpm build)
```

### PHP dependencies
```bash
./composer.phar install        # Install/update PHP dependencies
./yii migrate                  # Apply database migrations
```

### After a git pull
```bash
./composer.phar install && ./yii migrate && pnpm run build
```

### Linting / Static analysis
```bash
pnpm run lint                  # ESLint on web/js/**/*.{js,ts}

# PHPStan - generate baseline first, then verify changes don't introduce new errors:
php -d memory_limit=1G vendor/bin/phpstan.phar analyse --configuration=phpstan.neon --generate-baseline
php -d memory_limit=1G vendor/bin/phpstan.phar analyse --configuration=phpstan.use-baseline.neon
```

### Testing
```bash
vendor/bin/codecept run Unit                          # All unit tests
vendor/bin/codecept run Unit --skip-group=database    # Unit tests without DB
vendor/bin/codecept run Acceptance                    # All acceptance tests (requires Selenium + test.antragsgruen.test)
vendor/bin/codecept run Acceptance motions/CreateCept # Single acceptance test
```

Acceptance tests require:
- MariaDB test database (`antragsgruen_tests`) configured in `config/config_tests.json`
- `test.antragsgruen.test` pointing to localhost in `/etc/hosts`
- Selenium Grid running: `java -jar selenium-server-4.x.jar standalone --config selenium-antragsgruen.toml`

### Development environment (Docker)
```bash
echo "RANDOM_SEED=$(openssl rand -base64 32)" > .env   # one-time
docker compose -f docker-compose.development.yml --profile pnpm-helper up
docker exec -it antragsgruen-web-1 /var/www/antragsgruen/docker/initialize-development-environment.sh
```
Dev credentials: `testadmin@example.org` / `testadmin`, `testuser@example.org` / `testuser`.

## Architecture

### Framework
Yii2 MVC framework. Entry point: `web/index.php`. Console entry: `./yii`. Configuration: `config/config.json` (or environment variables via `EnvironmentConfigLoader`).

### Core MVC layout
- `controllers/` — HTTP controllers extending `controllers/Base.php`. Admin sub-controllers in `controllers/admin/`.
- `models/db/` — ActiveRecord models (Yii2). Key entities: `Motion`, `Amendment`, `Consultation`, `ConsultationMotionType`, `IMotion` (abstract base for both Motion and Amendment), `Site`, `User`, `VotingBlock`.
- `models/settings/` — JSON-serialized settings objects (stored as JSON columns or config files). `AntragsgruenApp` is the global app settings, `Consultation` are per-consultation settings.
- `views/` — PHP view files. `views/layouts/` contains the main HTML layouts. `views/pdfLayouts/` are also scanned by PHPStan.
- `components/` — Reusable services and utilities (email, HTML tools, diff, caching, PDF generation wrappers).
- `commands/` — Yii console commands (admin, database, background job controllers).
- `migrations/` — Yii DB migrations.

### Key domain concepts
- **Site** — a top-level installation (can host multiple consultations in multisite mode).
- **Consultation** — a meeting/event. Has motion types, agenda, users, and settings.
- **ConsultationMotionType** — defines a category of motions with their own workflow, policies, and section layout.
- **IMotion / Motion / Amendment** — `IMotion` is an abstract interface; `Motion` and `Amendment` are concrete ActiveRecord models that both implement it.
- **IMotionSection / sectionTypes/** — sections of a motion (text, title, image, PDF, tabular data, etc.). The type system is in `models/sectionTypes/`.
- **Policies** (`models/policies/`) — who can submit, support, or comment (everybody, logged-in users, specific groups, etc.).
- **Support types** (`models/supportTypes/`) — how supporters/signatories are collected.
- **Votings** — `VotingBlock`, `Vote`, managed via `VotingController` and `models/votings/`.
- **Proposed procedures** — `AmendmentProposal`, `models/proposedProcedure/`.

### Frontend
- Legacy JS: `web_src/js/antragsgruen.js` bundled into `web/js/antragsgruen.min.js` (concat + terser, see `assets/vite/legacy-bundles-plugin.js`).
- Vue.js components: `web_src/js/vue/` (complex widgets: voting, speaking list, amendment merging).
- TypeScript: `web_src/typescript/` only typings provided, not actually used.
- SCSS: lives in `web_src/css/` and plugin asset directories; compiled to `web/css/`.
- Third-party npm packages copied to `web/npm/` by the Vite build (`assets/vite/copy-npm-files-plugin.js`).

### Plugins
Each plugin lives in `plugins/<id>/` and extends `plugins/ModuleBase.php`. Activated via the `plugins` array in `config/config.json`. Plugins can provide:
- Custom themes (SCSS + `Assets.php` + `getProvidedLayout()` in `Module.php`)
- Custom language variants (`getProvidedMessagesForLanguage()` + `messages/<lang>/`)
- Extra controllers, views, commands, policies, hooks
- SSO/authentication providers

Key plugins in this repo: `gruen_ci` (Green Party CI theme), `antragsgruen_sites` (multisite home page manager), `generic_sso` (OAuth2/SAML SSO).

### REST API
Disabled by default; enabled per consultation. All endpoints under `/rest`. OpenAPI spec at `docs/openapi.yaml`. API response models live in `models/api/`. The controllers are implemented in `controllers/rest/` and extend from `RestBase.php`. Countroller methods also need to be registered in config/urls.php.
The correct approach to make modifications to the API and DTOs is:
- First change the docs/openapi.yaml
- Then run `VENDOR_DIR=/Users/tobiashossl/Sites/openapi/vendor php docs/openapi-generate-dtos.php docs/openapi.yaml models/api/` to generate the PHP DTOs
- Then use those DTOs. It is acceptable to add methods to these DTOs, as only the constructur will be overwritten by the command above.

### PDF generation
Two backends: TCPDF (default, PHP-only) and Weasyprint (external binary, nicer output). Configured via `weasyprintPath`/`qpdfPath` in `config.json`.

### Caching
- Default: Yii2 file cache.
- Redis: enabled via `redis` key in `config.json`.
- Aggressive view cache: `viewCacheFilePath` in `config.json`.

### Debug mode
Create an empty file `config/DEBUG` to enable Yii2 debug mode.

## Configuration
`config/config.json` is the main config file (gitignored, created by installer). Template: `config/config.template.json`. For tests: `config/config_tests.json` (from `config/config_tests.template.json`). All supported settings can alternatively be passed as environment variables (see `docs/environment-variables.md`).
