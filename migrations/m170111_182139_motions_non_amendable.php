<?php

use yii\db\Migration;

class m170111_182139_motions_non_amendable extends Migration
{
    public function up()
    {
        $this->addColumn('motion', 'nonAmendable', 'TINYINT DEFAULT 0 AFTER statusString');
    }

    public function down()
    {
        $this->dropColumn('motion', 'nonAmendable');
    }
}
