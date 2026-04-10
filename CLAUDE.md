# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is Antragsgrün

Antragsgrün (motion.tools) is a PHP/Yii2 web application for NGOs and political parties to collaboratively manage motions, amendments, resolutions, votings, and speaking lists. Current version: 4.17.1.

## General Instructions for Claude Code

Whenever there are non-trivial design decisions, ask rather than make assumptions.

## Commands

### Build / Assets
```bash
pnpm install                   # Install JS dependencies
pnpm run build                 # Compile SCSS/JS/Vue (runs gulp + rollup, see gulpfile.js and assets/rollup.js)
pnpm run watch                 # Watch and recompile on change (gulp watch)
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
- **Policies** (`models/policies/`) — who can submit, support, or comment (everybody, logged-in users, specific groups, etc.). See "Permission system" below.
- **Support types** (`models/supportTypes/`) — how supporters/signatories are collected.
- **Votings** — `VotingBlock`, `Vote`, managed via `VotingController` and `models/votings/`.
- **Proposed procedures** — `AmendmentProposal`, `models/proposedProcedure/`.

### Permission system
Two separate mechanisms exist: **privileges** (admin-side rights, granted via user groups) and **policies** (who may submit/support/comment, configured per motion type). Do not confuse `models/settings/Permissions.php` (business rules, see below) with `models/settings/UserGroupPermissions.php` (per-group privilege storage).

#### Privileges (admin rights)
- Defined as integer constants in `models/settings/Privileges.php`. Plugins can add custom privileges via `ModuleBase::addCustomPrivileges()`.
- Check with `User::havePrivilege($consultation, $privilege, $context)` (current user) or `$user->hasPrivilege(...)`. Privileges are always consultation-scoped.
- Motion-related privileges are *restrictable*: a grant can be limited to a motion type, agenda item, or tag. When checking such a privilege, pass a `PrivilegeQueryContext` (e.g. `PrivilegeQueryContext::motion($motion)`) so the restriction can be evaluated; `null` context only matches unrestricted grants.

Special values:
- `PRIVILEGE_ANY` (0) — pseudo-privilege: matches if the user holds any privilege at all. Used e.g. to let admins act past deadlines.
- `PRIVILEGE_SITE_ADMIN` (6) — all privileges on all consultations of the site; only grantable via a site-wide group with `admin-all`.
- `PRIVILEGE_GLOBAL_USER_ADMIN` (10) — edit user account data itself (name, password), not just group assignments. Cannot be granted through groups; only superusers (`adminUserIds`) have it.

General privileges (not motion-restrictable):
- `PRIVILEGE_CONSULTATION_SETTINGS` (1) — manage consultation settings, motion types, user groups; also retains access when the consultation is in maintenance mode.
- `PRIVILEGE_CONTENT_EDIT` (2) — edit content pages/texts and uploaded documents, see draft motions (not edit motions).
- `PRIVILEGE_AGENDA` (15) — manage the agenda.
- `PRIVILEGE_SPEECH_QUEUES` (8) — manage speech queues.
- `PRIVILEGE_VOTINGS` (9) — manage votings.

Motion-related privileges (restrictable to motion type / agenda item / tag):
- `PRIVILEGE_SCREENING` (3) — review & publish (screen) submitted motions, amendments, and comments.
- `PRIVILEGE_MOTION_SEE_UNPUBLISHED` (13) — see the admin motion list including unpublished motions/amendments (read-only, no editing rights).
- `PRIVILEGE_MOTION_STATUS_EDIT` (4) — edit motion/amendment metadata: status, supporters/signatures, tags, title. Not text, initiators, or deletion. Dedicated screening actions require `PRIVILEGE_SCREENING`, but setting statuses through the admin form achieves the same effect. Also the privilege behind the `Admins` policy.
- `PRIVILEGE_MOTION_TEXT_EDIT` (11) — edit the text; merge amendments into motions. In the admin UI it can only be granted together with `PRIVILEGE_MOTION_STATUS_EDIT` (see `Privilege::$dependentOnId`).
- `PRIVILEGE_MOTION_INITIATORS` (5) — edit initiators; create motions in the name of someone else; move motions *to* this consultation. Also only grantable together with `PRIVILEGE_MOTION_STATUS_EDIT`.
- `PRIVILEGE_MOTION_DELETE` (12) — delete motions/amendments; move motions away *from* this consultation.
- `PRIVILEGE_CHANGE_PROPOSALS` (7) — edit the proposed procedure.
- `PRIVILEGE_CHANGE_EDITORIAL` (14) — edit editorial texts / progress reports.

#### User groups (how privileges are granted)
- `models/db/ConsultationUserGroup` — scoped to a consultation, to a site, or global (externally synced groups identified by `externalId`, e.g. `gruenesnetz:`/OpenSlides). Membership via the `userGroup` join table (`User::getUserGroupsForConsultation()`).
- Each group stores a `UserGroupPermissions` JSON blob in its `permissions` column: either `default_permissions` bundles (`admin-all`, `proposed-procedure`, `admin-speech-list`) or fine-grained `privileges` entries (`UserGroupPermissionEntry`, optionally restricted to motionType/agendaItem/tag). Fine-grained entries can only be set on consultation-level groups. A legacy plain comma-separated format is still parsed when reading old DB rows, but never written anymore.
- Default template groups (`templateId`): Site admin, Consultation admin, Proposed procedure, Progress report, Participant. Template-based and system-wide groups are not user-editable.
- **Superusers**: user IDs listed in `adminUserIds` (config.json) pass every privilege check (`User::currentUserIsSuperuser()`). The old `siteAdmin`/`consultationAdmin` DB tables were dropped long ago; user groups are the only grant mechanism besides `adminUserIds`.

#### Policies (who may act)
- `models/policies/` classes implementing `IPolicy`; serialized into the `policyMotions`, `policyAmendments`, `policyComments`, `policySupportMotions`, `policySupportAmendments` columns of `ConsultationMotionType`. `ConsultationText` and `VotingBlock` also implement `IHasPolicies`.
- Registered policies: `Nobody`, `All`, `LoggedIn`, `Admins` (= holders of `PRIVILEGE_MOTION_STATUS_EDIT`), `UserGroups` (members of selected user groups), `GruenesNetz` (only when SAML is active), plus plugin-provided ones. `IPolicy::POLICY_ORGANIZATION` is reserved but currently has no implementation (the `organisations` consultation setting only feeds the initiator form dropdown).
- Check via `checkCurrUserMotion()` / `checkCurrUserAmendment()` / `checkCurrUserComment()`, which also enforce the motion type's deadlines; users with any privilege may override deadlines unless `$allowAdmins = false`.

#### Business rules
`models/settings/Permissions.php` combines status, deadlines, policies, and privileges into concrete decisions (`motionCanEditText()`, `isCurrentlyAmendable()`, `motionCanMergeAmendments()`, ...). Obtained via `IMotion::getPermissionsObject()`; plugins can substitute their own subclass via `ModuleBase::getPermissionsClass()`.

#### Consultation access gating
`components/ConsultationAccess.php` restricts access to a whole consultation, checked before controllers run: maintenance mode (only users with `PRIVILEGE_CONSULTATION_SETTINGS` get in), `forceLogin`, `managedUserAccounts` (user must belong to a user group of the consultation; registration requests are queued in `UserConsultationScreening`), a consultation access password (`accessPwd`), and the site's allowed `loginMethods`. Plugins can grant limited access to specific pages.

### Frontend
- Legacy JS: `web_src/js/antragsgruen.js` and the other files listed in `gulpfile.js` are concatenated and minified into `web/js/antragsgruen.min.js` (gulp-concat + gulp-terser).
- Vue.js components: `web_src/js/vue/**/*.vue` (complex widgets: voting, speaking list, amendment merging), compiled per-file by gulp using `@vue/compiler-sfc` (see `assets/gulpfile.vue.js`) into `web/js/vue/`.
- TypeScript: `web_src/typescript/` only typings provided, not actually used.
- SCSS: lives in `web/css/`, plugin asset directories, and `assets/html2pdf/`; gulp compiles it in place next to the sources (dart-sass + autoprefixer).
- Third-party npm packages: gulp's `copy-files` task copies prebuilt files to `web/npm/`; `rollup -c assets/rollup.js` bundles ESM-only packages (e.g. vuedraggable) into `web/npm/` as well.

### Plugins
Each plugin lives in `plugins/<id>/` and extends `plugins/ModuleBase.php`. Activated via the `plugins` array in `config/config.json`. Plugins can provide:
- Custom themes (SCSS + `Assets.php` + `getProvidedLayout()` in `Module.php`)
- Custom language variants (`getProvidedMessagesForLanguage()` + `messages/<lang>/`)
- Extra controllers, views, commands, policies, hooks
- SSO/authentication providers

Key plugins in this repo: `gruen_ci` (Green Party CI theme), `antragsgruen_sites` (multisite home page manager), `generic_sso` (OAuth2/SAML SSO).

### REST API
Disabled by default; enabled per site (`apiEnabled` in the site settings, see `Base::handleRestHeaders()`); authenticated users can use the API even when public API access is disabled. Authentication via JWT bearer tokens (`OptionalHttpBearerAuth`). All endpoints under `/rest`. OpenAPI spec at `docs/openapi.yaml`. API response models live in `models/api/`. The controllers are implemented in `controllers/rest/` and extend from `RestBase.php`. Countroller methods also need to be registered in config/urls.php.
The correct approach to make modifications to the API and DTOs is:
- First change the docs/openapi.yaml
- Then run `VENDOR_DIR=/Users/tobiashossl/Sites/openapi/vendor php docs/openapi-generate-dtos.php docs/openapi.yaml models/api/` to generate the PHP DTOs
- Then use those DTOs. It is acceptable to add methods to these DTOs, as only the constructor will be overwritten by the command above.

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
