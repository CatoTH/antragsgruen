<?php

declare(strict_types=1);

namespace app\commands;

use app\models\settings\EnvironmentConfigLoader;
use yii\console\{Controller, ExitCode};

/**
 * @extends Controller<\yii\console\Application>
 *
 * Brings an installation up to the state described by its environment.
 */
class AppController extends Controller
{
    /**
     * MySQL truncates lock names after 64 characters and treats them as a global namespace.
     */
    private const LOCK_NAME = 'antragsgruen.auto-init';

    private const LOCK_TIMEOUT = 120;

    /**
     * Creates the database schema and the first site, if the environment asks for it.
     *
     * This is what the container entrypoint calls on every start, so that an image
     * plus a set of environment variables is enough to go from an empty database to a
     * running site - no interactive installer, no writable config.json, no one-off
     * commands to run by hand. It does nothing at all unless AUTO_INIT is enabled, and
     * every step it performs is idempotent, so restarts and rescheduling are harmless.
     *
     * Deliberately limited to *creating* what is missing: it never migrates or alters
     * an existing schema. Upgrades stay an explicit, deliberate step.
     */
    public function actionAutoInit(): int
    {
        if (!EnvironmentConfigLoader::getBoolEnv('AUTO_INIT', false)) {
            return ExitCode::OK;
        }

        return $this->withLock(function (): int {
            $status = $this->runInitAction('database/init');
            if ($status !== ExitCode::OK) {
                return $status;
            }

            return $this->initSite();
        });
    }

    /**
     * Runs $callback while holding a lock shared by every instance using this database.
     *
     * Nothing stops several replicas from starting at once, and the "is this already
     * there?" checks the init commands rely on are not safe against that on their own.
     * The lock is bound to the database connection, so an instance that dies mid-init
     * releases it instead of blocking the rest forever.
     *
     * @param callable(): int $callback
     */
    private function withLock(callable $callback): int
    {
        $db = \Yii::$app->db;

        $acquired = $db->createCommand('SELECT GET_LOCK(:name, :timeout)', [
            ':name' => self::LOCK_NAME,
            ':timeout' => self::LOCK_TIMEOUT,
        ])->queryScalar();

        if (!is_scalar($acquired) || (string)$acquired !== '1') {
            $this->stderr(
                'Auto-init: gave up after ' . self::LOCK_TIMEOUT .
                "s waiting for another instance to finish initializing.\n"
            );
            return ExitCode::TEMPFAIL;
        }

        try {
            return $callback();
        } finally {
            $db->createCommand('SELECT RELEASE_LOCK(:name)', [':name' => self::LOCK_NAME])->queryScalar();
        }
    }

    /**
     * Creates the first site from the environment, unless it already exists.
     *
     * Without SITE_ADMIN_EMAIL this does nothing, which is what a multisite
     * installation wants: the schema is created, the sites are not.
     */
    private function initSite(): int
    {
        $email = trim((string)EnvironmentConfigLoader::getEnv('SITE_ADMIN_EMAIL', ''));
        if ($email === '') {
            return ExitCode::OK;
        }

        $params = [$email];

        // A site cannot be created without these, and guessing either of them would put
        // wrong information in front of users, so fail loudly instead.
        foreach (['SITE_TITLE' => 'title', 'SITE_CONTACT' => 'contact'] as $variable => $option) {
            $value = trim((string)EnvironmentConfigLoader::getEnv($variable, ''));
            if ($value === '') {
                $this->stderr('Auto-init: ' . $variable . " is required when SITE_ADMIN_EMAIL is set.\n");
                return ExitCode::CONFIG;
            }
            $params[$option] = $value;
        }

        $optional = [
            'SITE_ADMIN_PASSWORD' => 'password',
            'SITE_ADMIN_GIVEN_NAME' => 'givenName',
            'SITE_ADMIN_FAMILY_NAME' => 'familyName',
            'SITE_ORGANIZATION' => 'organization',
            'SITE_LANGUAGE' => 'language',
            'SITE_FUNCTIONALITY' => 'functionality',
            'SITE_LOGIN_METHODS' => 'loginMethods',
        ];
        foreach ($optional as $variable => $option) {
            if (EnvironmentConfigLoader::hasEnv($variable)) {
                $params[$option] = (string)EnvironmentConfigLoader::getEnv($variable, '');
            }
        }

        // A password handed to the container is already as secret as the deployment can
        // make it, so do not force a change on top of it. A generated one is printed to
        // the log and should not survive first contact.
        $forceChange = EnvironmentConfigLoader::getBoolEnv(
            'SITE_FORCE_PASSWORD_CHANGE',
            !EnvironmentConfigLoader::hasEnv('SITE_ADMIN_PASSWORD')
        );
        $params['forcePasswordChange'] = $forceChange ? '1' : '0';

        // Superuser status is stored in config.json, which is exactly the file this is
        // meant to make unnecessary. ADMIN_USER_IDS covers it instead. The account is
        // still a full administrator of the site it was created with, through the
        // regular user group.
        $params['superuser'] = '0';

        return $this->runInitAction('site/init', $params);
    }

    /**
     * @param array<int|string, string> $params
     */
    private function runInitAction(string $route, array $params = []): int
    {
        $result = \Yii::$app->runAction($route, $params);

        return is_int($result) ? $result : ExitCode::OK;
    }
}
