<?php

use yii\db\Migration;

class m240830_181716_user_secret_key extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'secretKey', 'VARCHAR(100) NULL DEFAULT NULL AFTER `authKey`');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'secretKey');
    }
}
