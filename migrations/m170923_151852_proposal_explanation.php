<?php

use yii\db\Migration;

class m170923_151852_proposal_explanation extends Migration
{
    public function safeUp()
    {
        $this->addColumn('amendment', 'proposalExplanation', 'TEXT NULL DEFAULT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('amendment', 'proposalExplanation');
    }
}
