<?php

use yii\db\Migration;

class m250608_180000_multiple_proposals extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('amendmentProposal', [
            'id' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'amendmentId' => 'INTEGER NOT NULL',
            'version' => 'SMALLINT NOT NULL',
            'proposalStatus' => 'TINYINT DEFAULT NULL',
            'proposalReferenceId' => 'INTEGER DEFAULT NULL',
            'comment' => 'TEXT NULL DEFAULT NULL',
            'visibleFrom' => 'TIMESTAMP NULL DEFAULT NULL',
            'notifiedAt' => 'TIMESTAMP NULL DEFAULT NULL',
            'notifiedText' => 'TEXT NULL DEFAULT NULL',
            'userStatus' => 'TINYINT NULL DEFAULT NULL',
            'explanation' => 'TEXT NULL DEFAULT NULL',
            'publicToken' => 'VARCHAR(150) NOT NULL',
        ]);

        $this->createTable('motionProposal', [
            'id' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'motionId' => 'INTEGER NOT NULL',
            'version' => 'SMALLINT NOT NULL',
            'proposalStatus' => 'TINYINT DEFAULT NULL',
            'proposalReferenceId' => 'INTEGER DEFAULT NULL',
            'comment' => 'TEXT NULL DEFAULT NULL',
            'visibleFrom' => 'TIMESTAMP NULL DEFAULT NULL',
            'notifiedAt' => 'TIMESTAMP NULL DEFAULT NULL',
            'notifiedText' => 'TEXT NULL DEFAULT NULL',
            'userStatus' => 'TINYINT NULL DEFAULT NULL',
            'explanation' => 'TEXT NULL DEFAULT NULL',
            'publicToken' => 'VARCHAR(150) NOT NULL',
        ]);

        $this->addForeignKey('fk_amendment_proposal', 'amendmentProposal', 'amendmentId', 'amendment', 'id');
        $this->addForeignKey('fk_amendment_proposal_ref', 'amendmentProposal', 'proposalReferenceId', 'amendment', 'id');
        $this->addForeignKey('fk_motion_proposal', 'motionProposal', 'motionId', 'motion', 'id');
        $this->addForeignKey('fk_motion_proposal_ref', 'motionProposal', 'proposalReferenceId', 'amendment', 'id');

        $this->execute('INSERT INTO amendmentProposal
            (amendmentId, version, proposalStatus, proposalReferenceId, comment, visibleFrom, notifiedAt, userStatus, explanation, publicToken)
            SELECT id, 1, proposalStatus, proposalReferenceId, proposalComment, proposalVisibleFrom, proposalNotification, proposalUserStatus, proposalExplanation, round(RAND() * 1000000000)
            FROM amendment WHERE proposalStatus != 0');

        $this->execute('INSERT INTO motionProposal
            (motionId, version, proposalStatus, proposalReferenceId, comment, visibleFrom, notifiedAt, userStatus, explanation, publicToken)
            SELECT id, 1, proposalStatus, proposalReferenceId, proposalComment, proposalVisibleFrom, proposalNotification, proposalUserStatus, proposalExplanation, round(RAND() * 1000000000)
            FROM motion WHERE proposalStatus != 0');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropTable('amendmentProposal');
    }
}
