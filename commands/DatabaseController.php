<?php

declare(strict_types=1);

namespace app\commands;

use app\models\db\{ISupporter, Motion, MotionSupporter, User};
use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

/**
 * @extends Controller<\yii\console\Application>
 *
 * Functions to create and destroy the database, and to fill it with initial data
 */
class DatabaseController extends Controller
{
    public const TEST_MODE_STD = 'std';
    public const TEST_MODE_YFJ = 'yfj';
    public const TEST_MODE_DBWV = 'dbwv';

    /**
     * Execute multi-statement SQL by splitting on statement boundaries
     * and running each statement individually. Runs an optional second pass
     * to handle statements that depend on tables created by other statements.
     *
     * Uses the mysql CLI internally for reliable multi-statement execution.
     */
    private function executeMultiStatementSql(string $sql, bool $force = false): void
    {
        $db = \Yii::$app->db;
        $dsnParts = [];
        preg_match('/host=([^;]+)/', $db->dsn, $dsnParts);
        $host = $dsnParts[1] ?? 'localhost';
        $port = '3306';
        if (preg_match('/port=(\d+)/', $db->dsn, $portMatch)) {
            $port = $portMatch[1];
        }
        preg_match('/dbname=([^;]+)/', $db->dsn, $dsnParts);
        $dbname = $dsnParts[1] ?? '';

        $sql = $this->applyTablePrefix($sql);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ag_sql_');
        file_put_contents($tmpFile, $sql);

        $stderrFile = tempnam(sys_get_temp_dir(), 'ag_err_');
        $forceFlag = $force ? '--force' : '';

        $command = sprintf(
            'mysql %s -h %s -P %s -u %s -p%s %s < %s 2>%s',
            $forceFlag,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($db->username),
            escapeshellarg($db->password),
            escapeshellarg($dbname),
            escapeshellarg($tmpFile),
            escapeshellarg($stderrFile)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        unlink($tmpFile);

        $stderr = file_exists($stderrFile) ? file_get_contents($stderrFile) : '';
        unlink($stderrFile);

        if ($returnVar !== 0 || $stderr !== '') {
            $prefix = $returnVar !== 0 ? 'SQL execution failed' : 'SQL warnings';
            $this->stderr($prefix . ' (rc=' . $returnVar . '): ' . $stderr . "\n");
        }
    }

    private function applyTablePrefix(string $sql): string
    {
        return str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $sql);
    }

    /**
     * Deletes the whole database. CAUTION!
     *
     * @throws \yii\db\Exception
     */
    public function actionDestroy(): void
    {
        if (!file_exists(__DIR__ . '/../config/DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        if ($this->confirm('Do you really want to DESTROY and reinitialize the database?')) {
            $this->executeMultiStatementSql((string)file_get_contents(__DIR__ . '/../assets/db/delete.sql'));
        }
    }

    /**
     * Creates the tables
     *
     * @throws \yii\db\Exception
     */
    public function actionCreate(): void
    {
        if (!file_exists(__DIR__ . '/../config/DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        $this->executeMultiStatementSql(
            (string)file_get_contents(__DIR__ . '/../assets/db/create.sql'),
            true
        );
        $this->executeMultiStatementSql(
            (string)file_get_contents(__DIR__ . '/../assets/db/data.sql'),
            true
        );
    }

    /**
     * Insertes some test data into the tables. Do not use this on a production environment!
     *
     * @throws \yii\db\Exception
     */
    public function actionInsertTestData(string $testmode): void
    {
        if (!file_exists(__DIR__ . '/../config/DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        $testdata = match($testmode) {
            self::TEST_MODE_DBWV => (string)file_get_contents(__DIR__ . '/../tests/Support/Data/dbdata-dbwv.sql'),
            self::TEST_MODE_YFJ => (string)file_get_contents(__DIR__ . '/../tests/Support/Data/dbdata-yfj.sql'),
            default => (string)file_get_contents(__DIR__ . '/../tests/Support/Data/dbdata1.sql'),
        };
        $this->executeMultiStatementSql($testdata, true);
    }

    /**
     * Create tables and insert test data. For development only.
     *
     * @throws \yii\db\Exception
     */
    public function actionCreateTest(string $testmode = self::TEST_MODE_STD): void
    {
        if (!file_exists(__DIR__ . '/../config/DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        if ($this->confirm('Do you really want to DESTROY and reinitialize the database?')) {
            $this->executeMultiStatementSql(
                (string)file_get_contents(__DIR__ . '/../assets/db/delete.sql')
            );

            $this->actionCreate();
            $this->actionInsertTestData($testmode);
        }
    }

    private function createOrGetUserAccount(string $email, string $password, string $givenName, string $familyName, string $organization): User
    {
        $user = User::findOne(['auth' => 'email:' . $email]);
        if ($user) {
            return $user;
        }

        $user = new User();
        $user->name = $givenName . ' ' . $familyName;
        $user->nameFamily = $familyName;
        $user->nameGiven = $givenName;
        $user->organization = $organization;
        $user->email = $email;
        $user->emailConfirmed = 1;
        $user->auth = 'email:' . $email;
        $user->dateCreation = date('Y-m-d H:i:s');
        $user->fixedData = User::FIXED_NAME | User::FIXED_ORGA;
        $user->status = User::STATUS_CONFIRMED;
        $user->pwdEnc = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return $user;
    }

    private function supportMotion(User $user, Motion $motion, int $position): void
    {
        $support = new MotionSupporter();
        $support->motionId = $motion->id;
        $support->userId = $user->id;
        $support->personType = ISupporter::PERSON_NATURAL;
        $support->role = ISupporter::ROLE_SUPPORTER;
        $support->position = $position;
        $support->dateCreation = date('Y-m-d H:i:s');
        $support->save();
    }

    /**
     * Create thousands of supports for a motion
     */
    public function actionMassSupportMotion(int $motionId): void
    {
        if (!file_exists(__DIR__ . '/../config/DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }

        /** @var Motion|null $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->stderr('Motion not found' . "\n");
        }

        for ($i = 0; $i < 10000; $i++) {
            $user = $this->createOrGetUserAccount('test-' . $i . '@example.org', 'Test', 'Test', (string)$i, 'Orga');
            $this->supportMotion($user, $motion, $i);
        }
    }
}
