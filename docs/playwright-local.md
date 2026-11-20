# Run the local Playwright pilot

Use a fresh disposable checkout. This recipe requires PHP with the project's
extensions (including `pdo_mysql`), Composer, Node.js 22+, pnpm 10, and Docker.
Run the checkout commands from its root.

## Install and seed

```sh
composer install --no-interaction --prefer-dist --no-progress
pnpm install --frozen-lockfile
pnpm exec playwright install chromium --only-shell
pnpm run build
php docs/create-static-resources.php dev
```

Start a fixture database with in-memory storage and a loopback port. The
credentials below are the defaults in the project's test template.

```sh
docker run --rm --detach --name antragsgruen-playwright-db \
  --tmpfs /var/lib/mysql --publish 127.0.0.1:43306:3306 \
  --env MYSQL_ROOT_PASSWORD=yii --env MYSQL_DATABASE=yii \
  --env MYSQL_USER=yii --env MYSQL_PASSWORD=yii mysql:latest
docker exec antragsgruen-playwright-db mysqladmin ping -h 127.0.0.1 --silent
```

Wait for the readiness command to succeed before seeding. If the name or either
port conflicts, use a fresh name or free loopback ports and update the commands
and configuration together.

```sh
unset ANTRAGSGRUEN_CONFIG
node --input-type=module <<'JS' &&
import { readFileSync, writeFileSync } from 'node:fs';
const config = JSON.parse(readFileSync('config/config_tests.template.json', 'utf8'));
config.dbConnection.dsn = 'mysql:host=127.0.0.1;port=43306;dbname=yii';
config.domainPlain = 'http://127.0.0.1:43123';
config.domainSubdomain = 'http://127.0.0.1:43123/<subdomain:[\\w_-]+>/';
writeFileSync('config/config.json', JSON.stringify(config, null, 2) + '\n', { flag: 'wx' });
writeFileSync('config/DEBUG', '', { flag: 'wx' });
JS
php yii database/init &&
php yii database/insert-test-data std
```

The exclusive file writes protect existing local settings. `config/DEBUG`
enables the seed command in the disposable checkout.

## Serve and test

Use the same PHP built-in server routing as the accessibility workflow.

```sh
mkdir -p tests/_output
cat > tests/_output/playwright-router.php <<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = $_SERVER['DOCUMENT_ROOT'] . $path;
if ($path !== '/' && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
PHP
php -S 127.0.0.1:43123 -t web/ "$PWD/tests/_output/playwright-router.php"
```

Once the server binds successfully, run the checks in a second shell.

```sh
node --test tests/playwright/fixture-target.test.mjs
ANTRAGSGRUEN_PLAYWRIGHT_URL=http://127.0.0.1:43123 pnpm test:playwright
```

The runner requires an HTTP origin with host `127.0.0.1` and an explicit port.
Browser artifacts are in `tests/_output/playwright`. After testing, stop the PHP
server with Ctrl-C and run `docker stop antragsgruen-playwright-db` to remove the
fixture container and its data.
