<?php

namespace Tests\config;

use Yii;
use yii\db\Connection;

trait AntragsgruenSetupDB
{
    protected ?Connection $database = null;
    protected ?string $database_delete = null;

    protected function createDB(): void
    {
        $this->database = Yii::$app->db;

        $init                  = file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'create.sql'
        );
        $init = str_replace('###TABLE_PREFIX###', '', $init);
        $data                  = file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'data.sql'
        );
        $data = str_replace('###TABLE_PREFIX###', '', $data);
        $this->database_delete = file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'delete.sql'
        );
        $this->database_delete = str_replace('###TABLE_PREFIX###', '', $this->database_delete);

        $this->deleteDB();

        $command = $this->database->createCommand($init);
        $command->execute();
        $command = $this->database->createCommand($data);
        $command->execute();
    }

    protected function deleteDB(): void
    {
        if ($this->database) {
            $command = $this->database->createCommand($this->database_delete);
            $command->execute();
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    protected function populateDB(string $file): void
    {
        $testdata = file_get_contents($file);
        $testdata = str_replace('###TABLE_PREFIX###', '', $testdata);

        $command = $this->database->createCommand($testdata);
        $command->execute();
    }
}
