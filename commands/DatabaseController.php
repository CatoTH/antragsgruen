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
        $delete_string = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'schema_delete.sql'
        );
        $command       = \Yii::$app->db->createCommand($delete_string);
        $command->execute();
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        $delete_string = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'schema_create.sql'
        );
        $command       = \Yii::$app->db->createCommand($delete_string);
        $command->execute();
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionInsertTestData()
    {
        $testdata = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'codeception' .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'dbdata1.sql'
        );
        $command       = \Yii::$app->db->createCommand($testdata);
        $command->execute();
    }
}
