<?php

use yii\db\Migration;

class m170826_180536_proposal_notifications extends Migration
{
    public function safeUp()
    {
        $this->addColumn('amendment', 'proposalNotification', 'TIMESTAMP NULL DEFAULT NULL');
        $this->addColumn('amendment', 'proposalUserStatus', 'TINYINT NULL DEFAULT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('amendment', 'proposalNotification');
        $this->dropColumn('amendment', 'proposalUserStatus');
    }
}
