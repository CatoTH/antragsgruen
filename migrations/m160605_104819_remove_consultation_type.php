<?php

use yii\db\Migration;

class m160605_104819_remove_consultation_type extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('consultation', 'type');
    }

    public function safeDown()
    {
        echo "m160605_104819_remove_consultation_type cannot be reverted.\n";

        return false;
    }
}
