<?php

use yii\db\Migration;

class m260809_172443_proposal_status extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('motionProposal', 'status', 'SMALLINT NOT NULL DEFAULT 0 after version');
        $this->addColumn('amendmentProposal', 'status', 'SMALLINT NOT NULL DEFAULT 0 after version');
    }

    public function safeDown(): void
    {
        $this->dropColumn('motionProposal', 'status');
        $this->dropColumn('amendmentProposal', 'status');
    }
}
