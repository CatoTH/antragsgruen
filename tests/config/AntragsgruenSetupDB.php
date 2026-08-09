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

        $this->executeMultiStatementSql($init);
        $this->executeMultiStatementSql($data);

        // Schema caching (config/web.php) is on for tests (YII_DEBUG is forced false, see
        // tests/_bootstrap.php), and persists to disk (FileCache) for up to an hour. Since the DDL
        // above bypasses yii\db\Command (see executeMultiStatementSql()), nothing invalidates that
        // cache automatically - without this, a table dropped/recreated with the same name can keep
        // resolving against a stale "does not exist" (or stale column list) entry from an earlier run.
        $this->database->getSchema()->refresh();
    }

    protected function deleteDB(): void
    {
        if ($this->database) {
            $this->executeMultiStatementSql($this->database_delete);
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    protected function populateDB(string $file): void
    {
        $testdata = file_get_contents($file);
        $testdata = str_replace('###TABLE_PREFIX###', '', $testdata);

        $this->executeMultiStatementSql($testdata);
    }

    /**
     * yii\db\Command::execute() runs the given SQL through a single PDOStatement::execute() call.
     * Our .sql fixture files contain many statements in one string; PDO_MYSQL happily executes all
     * of them server-side, but leaves their result sets queued up on the connection afterward. The
     * next query on that connection then fails with "Cannot execute queries while there are pending
     * result sets" (or, depending on the query, gets misreported by Yii's schema loader as "table
     * does not exist"). Draining every rowset via PDOStatement::nextRowset() avoids that.
     */
    private function executeMultiStatementSql(string $sql): void
    {
        if (trim($sql) === '') {
            return;
        }

        $pdo  = $this->database->getMasterPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        do {
            // Drain all result sets so the connection is usable again afterward.
        } while ($stmt->nextRowset());
    }
}
