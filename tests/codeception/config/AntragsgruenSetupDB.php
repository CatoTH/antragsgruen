<?php

namespace app\tests;

use Yii;

trait AntragsgruenSetupDB
{
    /** @var \yii\db\Connection */
    protected $database;

    /** @var  string */
    protected $database_delete;

    protected function createDB()
    {
        $init                  = file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'schema_create.sql'
        );
        $this->database        = Yii::$app->db;
        $this->database_delete = file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'schema_delete.sql'
        );
        $command               = $this->database->createCommand($init);
        $command->execute();
    }

    protected function deleteDB()
    {
        $command = $this->database->createCommand($this->database_delete);
        $command->execute();
    }

    /**
     * @param string $file
     * @throws \yii\db\Exception
     */
    protected function populateDB($file)
    {
        $testdata = file_get_contents($file);
        $command  = $this->database->createCommand($testdata);
        $command->execute();
    }
}
