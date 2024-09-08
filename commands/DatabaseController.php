<?php

declare(strict_types=1);

namespace app\commands;

use app\models\db\{ISupporter, Motion, MotionSupporter, User};
use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

/**
 * Functions to create and destroy the database, and to fill it with initial data
 */
class DatabaseController extends Controller
{
    public const TEST_MODE_STD = 'std';
    public const TEST_MODE_YFJ = 'yfj';
    public const TEST_MODE_DBWV = 'dbwv';

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
            $deleteString = (string)file_get_contents(__DIR__ . '/../assets/db/delete.sql');
            $deleteString = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $deleteString);
            $command      = \Yii::$app->db->createCommand($deleteString);
            $command->execute();
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
        $createString = (string)file_get_contents(__DIR__ . '/../assets/db/create.sql');
        $createString = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $createString);
        $command      = \Yii::$app->db->createCommand($createString);
        $command->execute();

        $createString = (string)file_get_contents(__DIR__ . '/../assets/db/data.sql');
        $createString = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $createString);
        $command      = \Yii::$app->db->createCommand($createString);
        $command->execute();
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
        $testdata = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $testdata);
        $command  = \Yii::$app->db->createCommand($testdata);
        $command->execute();
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
            $deleteString = (string)file_get_contents(__DIR__ . '/../assets/db/delete.sql');
            $deleteString = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $deleteString);
            $command      = \Yii::$app->db->createCommand($deleteString);
            $command->execute();
            unset($command);

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
