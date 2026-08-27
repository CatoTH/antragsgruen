# Task: Migrate Antragsgrün E2E tests from Codeception/Selenium to Playwright

You are executing a pre-reviewed migration plan. The plan document is `e2e-review-2026-08-21.md` in the repo root. It is the source of truth for WHAT to build: findings, BLOCKER fixes (B1–B12, B14), file lists, phase order. This prompt governs HOW you work. Where the two conflict, stop and report instead of improvising.

## Hard rules

1. Work on branch `e2e/playwright-migration`, created from latest `main`. Never commit to `main` directly. Never force-push.
2. Execute phases sequentially, in the plan's order (1 → 10). No skipping, no reordering.
3. Validation gate: after every phase AND after every batch inside a phase, run `cd e2e && bunx tsc --noEmit`. It must exit 0. Never advance on a broken build.
4. Commit after every green phase, and within Phase 4 after every batch of 10–15 spec files: `git add -A && git commit -m "e2e: <phase or batch>"`. Many small commits, never one giant one.
5. Do all work inline, yourself. The plan mentions parallel subagents — that was written for a different orchestrator. You have no subagents. Process everything in batches.
6. Scope lock: only create/modify the files the plan lists. Do NOT touch `.github/workflows/a11y-checks.yml`, `bin/test-a11y.mjs`, or `pa11y.json` — that is a separate live feature, not part of this migration. Do not refactor production code beyond `controllers/TestController.php` (Phase 5) and the explicit edits in Phases 6–8.
7. The plan's empirical claims (238/239 specs, file counts, dead code) were verified at review time. Verify cheaply before acting (`grep -c`, `ls`). If reality diverges materially, stop and report — do not improvise around it.
8. Phase 1 is marked "DONE" in the plan, but that refers to a previous session's worktree. First check whether `e2e/package.json`, `e2e/playwright.config.ts`, `e2e/auth.setup.ts`, `e2e/tsconfig.json`, `e2e/.gitignore` exist in THIS checkout with the described content. If anything is missing or diverges, (re)create it exactly as the plan specifies.
9. Deletions (Phase 6): delete exactly the listed paths, nothing more. Before deleting `tests/Support/AcceptanceTester.php`, grep the whole Unit suite for `AcceptanceTester::` and port EVERY referenced constant into `tests/Support/TestFixtures.php` — the plan names one known consumer (`tests/Unit/AmendmentNumberingTest.php`); there may be more. Check.
10. Live smoke tests are optional. If no dev stack is reachable, mark smoke specs as skipped and move on — the plan explicitly allows this. Do NOT attempt to bootstrap MySQL, Selenium, or a web server yourself.
11. If you ever face a choice between fidelity-to-plan and working code, stop and ask. Otherwise proceed autonomously.

## Environment facts (verified 2026-08-21 — trust these over guessing)

- `web/index-test.php` does not exist and is never generated. The entry point is `web/index.php`. (This is the plan's B2.)
- The app's test mode is gated in `web/index.php`: requests from localhost whose `Host` header exactly matches the contents of `config/TEST_DOMAIN` (`test.antragsgruen.test`, no port) load `config/config_tests.json` with `YII_ENV=test`.
- `config/js-dependencies.php` is generated, not committed: run `php docs/create-static-resources.php dev` after `pnpm install && pnpm run build`. Every page render 500s without it.
- The PHP built-in server has no URL rewriting; pretty URLs need a router script. A working reference implementation lives in `.github/workflows/a11y-checks.yml` (read it if you ever need to serve the app).
- Fixture console commands: `php yii database/init` creates the schema; `php yii database/insert-test-data std` loads the standard fixture (also `yfj`, `dbwv` variants).
- Consultation URLs in the test fixture are path-based: `/stdparteitag/std-parteitag` (consultation subdomain / urlPath).

## Phase notes

- Phase 4 (specs): work through the category groups A–F from the plan, 10–15 files per commit. Use `e2e/tests/motions/create.spec.ts` as the reference pattern; if it does not exist yet, write it first according to the plan's conventions and validate it before starting any batch.
- Phase 5 (TestController): the IP allowlist (B14) and `PDO::MYSQL_ATTR_MULTI_STATEMENTS = true` (B4) are mandatory, not optional.
- Phase 7: in `.github/workflows/php-checks.yml` change `index-test.php` → `index.php` in both places (B2) and pin the Docker image to `4.17.1-full` (B3). Do not modify any other workflow file.
- Phase 10 (re-audit): do it yourself, inline, walking the B1–B14 checklist from the plan and confirming each fix in the actual code. Append the result as a final section of `e2e-review-2026-08-21.md`. Requirement for done: zero new BLOCKER or MAJOR findings.

## Definition of done

- `find e2e/tests -name "*.spec.ts" | wc -l` prints 239
- `cd e2e && bunx tsc --noEmit` exits 0
- `git status --short` shows only intended paths; everything is committed
- Final message contains: files created/deleted counts, the BLOCKER fixes applied (B1–B12, B14), deferred items, and anything you skipped with the reason

## First action

Read `e2e-review-2026-08-21.md` in full. Then reply with a restatement of at most 10 lines: the branch name, the phase order, the exact validation-gate command, whether the Phase 1 files exist in this checkout, and the first three files you will create. Do not write any code until that restatement is correct.
