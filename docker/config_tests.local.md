# Local-stack-only config override

`docker/config_tests.local.json` is a stack-local copy of `config/config_tests.json`
(the runtime config that web/index.php loads when `YII_ENV=test` and the
`Host` header matches `config/TEST_DOMAIN`).

The tracked file `config/config_tests.json` is gitignored
(see `config/.gitignore`); this override is the same shape, with exactly
three values changed so URLs produced by `/test/url-builder` resolve against
the local dev stack instead of the legacy Selenium/Codeception pattern:

| key             | upstream value                                  | local-stack value              |
|-----------------|-------------------------------------------------|--------------------------------|
| `prettyUrl`     | unset (defaults to `true`)                      | `false`                        |
| `domainPlain`   | `http://localhost:8080/index-test.php`          | `""` (empty)                   |
| `domainSubdomain` | `http://localhost:8080/index-test.php`        | `""` (empty)                   |

`prettyUrl: false` keeps the URL manager in query-string mode, which is
how the legacy Selenium suite (which this migration replaces) drove the
app — every URL was `http://localhost:8080/index-test.php?r=<route>&...`.
With prettyUrl true, nginx would have to forward PATH_INFO for non-`.php`
requests (the dev `docker/nginx/default.conf` does not); the empty
domains then put subdomain and consultationPath in the URL path
(`/stdparteitag/std-parteitag/test/populate-db`) instead of in the
hostname (DNS-virtual-host setup that this stack does not implement).

`/test/url-builder` will return absolute URLs of the form
`http://127.0.0.1:22380/index.php?r=<route>&...` with this override.
Playwright's `BasePage.getUrl()` consumes those verbatim and `page.goto()`
navigates correctly against `E2E_BASE_URL=http://127.0.0.1:22380`.

## Drift

This file is a copy, not a symlink. Re-sync by
`cp config/config_tests.template.json docker/config_tests.local.json`
whenever `config/config_tests.template.json` gains new fields or its
existing fields change meaning, then re-apply the three edits above.

## CI

The corrected `e2e-playwright` CI job (deferred until the suite has passed
live) will need an equivalent override on the CI stack — same three-line
shape, with `domainPlain`/`domainSubdomain` either empty (matching this
override) or pointing at the CI stack URL.
