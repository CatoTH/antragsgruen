<?php
namespace app\commands;

use yii\console\Controller;

/**
 * Functions to create and destroy the database, and to fill it with initial data
 * @package app\commands
 */
class DatabaseController extends Controller
{
    /**
     * @throws \yii\db\Exception
     */
    public function actionDestroy()
    {
        if ($this->confirm('Do you really want to DESTROY and reinitialize the database?')) {
            $deleteString = file_get_contents(
                \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
                'db' . DIRECTORY_SEPARATOR . 'delete.sql'
            );
            $command      = \Yii::$app->db->createCommand($deleteString);
            $command->execute();
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        $createString = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'create.sql'
        );
        $command      = \Yii::$app->db->createCommand($createString);
        $command->execute();

        $createString = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'data.sql'
        );
        $command      = \Yii::$app->db->createCommand($createString);
        $command->execute();
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionInsertTestData()
    {
        $testdata = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . 'dbdata1.sql'
        );
        $command  = \Yii::$app->db->createCommand($testdata);
        $command->execute();
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionCreateTest()
    {
        if ($this->confirm('Do you really want to DESTROY and reinitialize the database?')) {
            $deleteString = file_get_contents(
                \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
                'db' . DIRECTORY_SEPARATOR . 'delete.sql'
            );
            $command      = \Yii::$app->db->createCommand($deleteString);
            $command->execute();
            unset($command);

            $this->actionCreate();
            $this->actionInsertTestData();
        }
    }
}
