<?php

use yii\db\Migration;

class m221224_151157_remove_site_admins extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('siteAdmin');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221224_151157_remove_site_admins cannot be reverted.\n";

        return false;
    }
}
