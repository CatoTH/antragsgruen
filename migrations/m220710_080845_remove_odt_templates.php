<?php

use yii\db\Migration;

class m220710_080845_remove_odt_templates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('consultationOdtTemplate');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220710_080845_remove_odt_templates cannot be reverted.\n";

        return false;
    }
}
