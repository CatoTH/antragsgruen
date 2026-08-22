# Local-stack-only TEST_DOMAIN override

`docker/TEST_DOMAIN.local` is a stack-local copy of `config/TEST_DOMAIN`.
The tracked file `config/TEST_DOMAIN` contains `test.antragsgruen.test`,
which is the upstream convention requiring a `/etc/hosts` entry pointing
that hostname at `127.0.0.1`.

This stack does not have that `/etc/hosts` entry (no sudo in the local
sandbox). Instead, the stack-local override is `127.0.0.1:22380` so:

- `web/index.php` switches to `YII_ENV=test` when `Host: 127.0.0.1:22380`
  is sent (Playwright sends this naturally because `E2E_BASE_URL=
  http://127.0.0.1:22380`).
- `Yii::$app->request->getHostInfo()` returns `http://127.0.0.1:22380`,
  so every `Response::redirect()` Location header already points at the
  local stack. No nginx `proxy_redirect` shim required.
- `TestController::isRemoteIpAllowed()` still passes: nginx forwards
  `REMOTE_ADDR=127.0.0.1` to PHP-FPM via the existing override.

## Drift

This file is a copy, not a symlink. Re-sync if upstream changes
`config/TEST_DOMAIN` (it rarely does).

## CI

The corrected `e2e-playwright` CI job (deferred until the suite has
passed live) needs an equivalent `TEST_DOMAIN` value matching the CI
stack's host:port (e.g. `localhost:8080` if the CI job runs nginx on
the standard Selenium port).
