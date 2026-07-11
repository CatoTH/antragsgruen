<?php

declare(strict_types=1);

use yii\db\Migration;

class m260711_000525_debate_item extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('debateItem', [
            'id' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'consultationId' => 'INTEGER NOT NULL',
            'motionId' => 'INTEGER DEFAULT NULL',
            'amendmentId' => 'INTEGER DEFAULT NULL',
            'agendaItemId' => 'INTEGER DEFAULT NULL',
            'votingBlockId' => 'INTEGER DEFAULT NULL',
            'dateStarted' => 'TIMESTAMP NOT NULL',
            'dateStopped' => 'TIMESTAMP NULL DEFAULT NULL',
            'settings' => 'TEXT NULL DEFAULT NULL',
        ]);

        $this->addForeignKey('fk_debate_consultation', 'debateItem', 'consultationId', 'consultation', 'id');
        $this->addForeignKey('fk_debate_motion', 'debateItem', 'motionId', 'motion', 'id');
        $this->addForeignKey('fk_debate_amendment', 'debateItem', 'amendmentId', 'amendment', 'id');
        $this->addForeignKey('fk_debate_agenda_item', 'debateItem', 'agendaItemId', 'consultationAgendaItem', 'id');
        $this->addForeignKey('fk_debate_voting_block', 'debateItem', 'votingBlockId', 'votingBlock', 'id');
        $this->createIndex('ix_debate_current', 'debateItem', ['consultationId', 'dateStopped'], false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropTable('debateItem');
    }
}
