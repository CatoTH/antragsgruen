<?php
namespace app\commands;

use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

/**
 * Functions to create and destroy the database, and to fill it with initial data
 */
class DatabaseController extends Controller
{
    /**
     * Deletes the whole database. CAUTION!
     *
     * @throws \yii\db\Exception
     */
    public function actionDestroy(): void
    {
        if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        if ($this->confirm('Do you really want to DESTROY and reinitialize the database?')) {
            $deleteString = (string)file_get_contents(
                \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
                'db' . DIRECTORY_SEPARATOR . 'delete.sql'
            );
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
        if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        $createString = (string)file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'create.sql'
        );
        $createString = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $createString);
        $command      = \Yii::$app->db->createCommand($createString);
        $command->execute();

        $createString = (string)file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'data.sql'
        );
        $createString = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $createString);
        $command      = \Yii::$app->db->createCommand($createString);
        $command->execute();
    }

    /**
     * Insertes some test data into the tables. Do not use this on a production environment!
     *
     * @throws \yii\db\Exception
     */
    public function actionInsertTestData(): void
    {
        if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        $testdata = (string)file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . 'dbdata1.sql'
        );
        $testdata = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $testdata);
        $command  = \Yii::$app->db->createCommand($testdata);
        $command->execute();
    }

    /**
     * Create tables and insert test data. For development only.
     *
     * @throws \yii\db\Exception
     */
    public function actionCreateTest(): void
    {
        if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'DEBUG')) {
            $this->stderr('This action is only available in Debug-Mode' . "\n");
            return;
        }
        if ($this->confirm('Do you really want to DESTROY and reinitialize the database?')) {
            $deleteString = (string)file_get_contents(
                \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
                'db' . DIRECTORY_SEPARATOR . 'delete.sql'
            );
            $deleteString = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $deleteString);
            $command      = \Yii::$app->db->createCommand($deleteString);
            $command->execute();
            unset($command);

            $this->actionCreate();
            $this->actionInsertTestData();
        }
    }
}
