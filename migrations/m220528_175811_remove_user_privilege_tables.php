<?php

use yii\db\Migration;

class m220528_175811_remove_user_privilege_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('consultationUserPrivilege');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220528_175811_remove_user_privilege_tables cannot be reverted.\n";

        return false;
    }
}
