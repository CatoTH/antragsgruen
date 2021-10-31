<?php

use yii\db\Migration;

class m211031_004346_failed_login_attempts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('failedLoginAttempt', [
            'id' => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'ipHash' => 'CHAR(64) NOT NULL',
            'username' => 'VARCHAR(190) NOT NULL',
            'dateAttempt' => 'TIMESTAMP NOT NULL',
        ]);
        $this->createIndex('failedlogin_ip', 'failedLoginAttempt', 'ipHash', false);
        $this->createIndex('failedlogin_username', 'failedLoginAttempt', 'username', false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('failedlogin_ip', 'failedLoginAttempt');
        $this->dropIndex('failedlogin_username', 'failedLoginAttempt');
        $this->dropTable('failedLoginAttempt');
    }
}
