<?php

use yii\db\Migration;

class m250817_080222_date_submission extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->addColumn('motion', 'dateSubmission', 'timestamp null default null after dateCreation');
        $this->addColumn('amendment', 'dateSubmission', 'timestamp null default null after dateCreation');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropColumn('motion', 'dateSubmission');
        $this->dropColumn('amendment', 'dateSubmission');
    }
}
