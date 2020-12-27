<?php

use yii\db\Migration;

/**
 * Class m200329_135701_speech_list
 */
class m200329_135701_speech_list extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('speechQueue', [
            'id'             => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'consultationId' => 'INTEGER NOT NULL',
            'agendaItemId'   => 'INTEGER NULL DEFAULT NULL',
            'motionId'       => 'INTEGER NULL DEFAULT NULL',
            'isActive'       => 'TINYINT NOT NULL DEFAULT 0',
            'settings'       => 'TEXT NULL DEFAULT NULL',
        ]);
        $this->addForeignKey('fk_speech_consultation', 'speechQueue', 'consultationId', 'consultation', 'id');
        $this->addForeignKey('fk_speech_motion', 'speechQueue', 'motionId', 'motion', 'id');
        $this->addForeignKey('fk_speech_agenda', 'speechQueue', 'agendaItemId', 'consultationAgendaItem', 'id');

        $this->createTable('speechSubqueue', [
            'id'       => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'queueId'  => 'INTEGER NOT NULL',
            'name'     => 'TEXT NOT NULL',
            'position' => 'INTEGER NOT NULL',
        ]);
        $this->addForeignKey('fk_speech_queue', 'speechSubqueue', 'queueId', 'speechQueue', 'id');

        $this->createTable('speechQueueItem', [
            'id'          => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'queueId'     => 'INTEGER NOT NULL',
            'subqueueId'  => 'INTEGER NULL DEFAULT NULL',
            'userId'      => 'INTEGER NULL DEFAULT NULL',
            'userToken'   => 'VARCHAR(32) NULL DEFAULT NULL',
            'name'        => 'TEXT NOT NULL',
            'position'    => 'INTEGER NULL DEFAULT NULL',
            'dateApplied' => 'TIMESTAMP NULL DEFAULT NULL',
            'dateStarted' => 'TIMESTAMP NULL DEFAULT NULL',
            'dateStopped' => 'TIMESTAMP NULL DEFAULT NULL',
        ]);
        $this->addForeignKey('fk_speechitem_queue', 'speechQueueItem', 'queueId', 'speechQueue', 'id');
        $this->addForeignKey('fk_speechitem_subqueue', 'speechQueueItem', 'subqueueId', 'speechSubqueue', 'id');
        $this->addForeignKey('fk_speechitem_user', 'speechQueueItem', 'userId', 'user', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_speechitem_subqueue', 'speechQueueItem');
        $this->dropForeignKey('fk_speechitem_queue', 'speechQueueItem');
        $this->dropForeignKey('fk_speechitem_user', 'speechQueueItem');
        $this->dropTable('speechQueueItem');

        $this->dropForeignKey('fk_speech_queue', 'speechSubqueue');
        $this->dropTable('speechSubqueue');

        $this->dropForeignKey('fk_speech_agenda', 'speechQueue');
        $this->dropForeignKey('fk_speech_motion', 'speechQueue');
        $this->dropForeignKey('fk_speech_consultation', 'speechQueue');
        $this->dropTable('speechQueue');
    }
}
