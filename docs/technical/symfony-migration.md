# Migration from Yii2 to Symfony

Status: planning. This document holds the agreed plan and the settled decisions.

## Settled decisions

| # | Decision |
|---|---|
| D1 | Target **Symfony 7.4 LTS**. |
| D2 | **Keep Yii2 ActiveRecord** (all of `models/db/`), running inside a headless Yii application that only provides `db`, `cache` and `i18n`. No Doctrine ORM. `./yii migrate` stays. |
| D3 | The **plugin API may change** (`ModuleBase`, plugin routes, plugin controllers). One breaking release, documented for third-party plugins. |
| D4 | **Stay with PHP templates.** Symfony has no PHP templating engine since 5.0, so we ship our own small `ViewRenderer`. No Twig. |

## Starting point

Things that make a gradual migration feasible:

- Controller actions return our own response objects (`models/http/*`), converted in one place (`ResponseInterface::renderYii()`).
- Yii is already partly hidden behind `RequestContext`, `UrlHelper` and the request helpers in `controllers/Base`. There are only about 190 direct `Yii::$app` calls left.
- i18n runs through our own `components/yii/MessageSource.php`, which works without a web application.
- `models/settings`, `models/policies` and `models/api` are plain PHP.
- Mailer, Serializer and Lock are already Symfony components. Assets are built by Vite.
- About 490 Selenium acceptance tests test over HTTP and therefore work regardless of framework. They are the safety net for every step.

Hard parts:

- **Views**: 256 files depend on `yii\web\View` (`$this->context`, `$this->title`, `layoutParams`), plus about 1,900 `Html::` calls.
- **Hybrid operation**: session, login state, the `_identity` remember-me cookie and CSRF tokens use different formats in Yii and Symfony. Users must stay logged in when moving between pages served by Yii and pages served by Symfony.
- **Plugins**: `ModuleBase extends yii\base\Module`, and plugin routes are Yii URL-rule strings.
- **Self-hosted distribution**: the web installer, the built-in updater and shared hosting. Symfony needs a writable `var/` and a cache clear/warmup on every update.

## Plan

Approach: "strangler". Symfony becomes the front controller and passes every request it does not handle yet to the Yii web application. Every step can be released on its own and must keep the acceptance suite green.

### Phase A: decouple, still running on Yii

The A steps are independent of each other and can be done in any order, alongside feature work.

| # | Step | Difficulty |
|---|---|---|
| A0 | ~~Remove the `conflict` caps on `symfony/*` <7, `psr/cache` <3 and `webmozart/assert` <2 in `composer.json`, then move to 7.4~~ **Done.** The caps were there for SimpleSAMLphp 2.4, which Guzzle 8 had already made uninstallable (`simplesamlphp/assert` needs `guzzlehttp/psr7` 2.x). SAML setups need SimpleSAMLphp ≥2.5 (Symfony 7.4, PHP ≥8.3) once it supports psr7 3 | simple–medium |
| A1 | Move console commands (`commands/`, 12 files) to `symfony/console`. Migrations stay on `./yii migrate` | simple |
| A2 | Replace `yii\helpers\Html` with `app\components\Html`, our own implementation of the methods we use. CSRF token and current URL come from `RequestContext`. **Done.** `yii\helpers\Url` (5 uses) and `ArrayHelper` (5 uses) are still left | simple, touches many files |
| A3 | Replace the remaining direct `Yii::$app` calls with `RequestContext` or services. `Yii::$app->end()` becomes a thrown response | simple–medium |
| A4 | `models/http/*` builds `Symfony\Component\HttpFoundation\Response` objects; the Yii adapter only passes them through. Convert the remaining `void`/`string` actions | medium |
| A5 | Free views from `yii\web\View`: explicit `$controller` / `$layout` variables and our own `ViewRenderer` (D4) | medium–large |
| A6 | Move Yii events to `symfony/event-dispatcher` | simple |
| A7 | Move cache access to PSR-16 / `symfony/cache`, keeping Redis support | simple |
| A8 | Move logging (including `OpenTelemetryLogTarget`) to PSR-3 / Monolog | simple |
| A9 | Move login, 2FA and identity storage out of `yii\web\User` into our own service | medium |
| A10 | Decouple unit tests from Codeception's Yii2 module | simple–medium |

### Phase B: Symfony as the front controller

| # | Step | Needs | Difficulty |
|---|---|---|---|
| B1 | Symfony kernel in `web/index.php`. Load `AntragsgruenApp` as a service. Start the headless Yii app (D2) lazily. Requests without a matching route go to the Yii web app | A0 | medium |
| B2 | Session, auth and CSRF bridge: an authenticator that reads Yii's session identity and `_identity` cookie, session storage without Symfony's attribute namespacing, and Yii-compatible CSRF validation | B1, A9 | complex |
| B3 | Generate Symfony routes from the rules in `config/urls.php` and from plugin routes, so there is a single source of routes. `UrlHelper` builds URLs with Symfony's URL generator | B1 | medium |
| B4 | Move the REST controllers (`controllers/rest/`). They are stateless JWT, so they don't need B2 | B1, B3, A4 | simple–medium |
| B5 | Move web controllers one group at a time: Pages → Consultation → User → admin → Motion/Amendment | B2, B3, A4, A5 | large, but each controller is its own step |
| B6 | Plugin API v2 (D3): `ModuleBase` no longer extends Yii, plugin controllers and routes move to Symfony, asset bundles become static manifests | B3; can run in parallel with B5 | medium |
| B7 | Installer, updater and release packaging: cache warmup at build time, cache clear on update, writable `var/` | B1 | medium |
| B8 | Remove the Yii web app, `yii2-debug` and `yii2-authclient` (only referenced in config) | B5, B6 | simple |

```
A0 ──► B1 ──► B3 ──► B4 (REST)
        ├──► B7 (installer/updater)
        └──► B2 ──► B5 (controllers, one by one) ──► B8
A1..A10 in any order; A4 + A5 before B5, A9 before B2.  B6 in parallel with B5.
```

## Performance

- Record a baseline with `docs/performance-tests/` before A0, and compare after B1, B4 and each B5 group. Warm up for more than 2 seconds, because opcache `revalidate_freq` would otherwise blur quick A/B comparisons.
- No double boot: Symfony first matches the route with its compiled router. The Yii web application only starts for fallback requests, and the headless Yii app starts on the first DB access.
- Production uses a compiled container, a dumped router and cache warmup at release build time (B7).
- Keep Yii's schema cache (`enableSchemaCache`) in the headless setup.
- REST polling (speech queue, votings) stays session-free, using a stateless firewall that matches today's `RequestContext::isRestApiRequest()`.
