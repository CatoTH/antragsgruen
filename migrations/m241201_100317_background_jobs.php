<?php

use yii\db\Migration;

class m241201_100317_background_jobs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('backgroundJob', [
            'id' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'siteId' => 'INTEGER DEFAULT NULL',
            'consultationId' => 'INTEGER DEFAULT NULL',
            'type' => 'VARCHAR(150) NOT NULL',
            'dateCreation' => 'TIMESTAMP NOT NULL',
            'dateStarted' => 'TIMESTAMP DEFAULT NULL',
            'dateUpdated' => 'TIMESTAMP DEFAULT NULL',
            'dateFinished' => 'TIMESTAMP DEFAULT NULL',
            'payload' => 'MEDIUMTEXT NOT NULL',
            'error' => 'TEXT DEFAULT NULL',
        ]);

        $this->addForeignKey('fk_background_site', 'backgroundJob', 'siteId', 'site', 'id');
        $this->addForeignKey('fk_background_consultation', 'backgroundJob', 'consultationId', 'consultation', 'id');
        $this->createIndex('ix_background_pending', 'backgroundJob', ['dateStarted', 'id'], false);
        $this->createIndex('ix_background_todelete', 'backgroundJob', 'dateFinished', false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropTable('backgroundJob');
    }
}
