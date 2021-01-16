<?php

use yii\db\Migration;

/**
 * Class m210116_080438_rename_email_blocklist
 */
class m210116_080438_rename_email_blocklist extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameTable('emailBlacklist', 'emailBlocklist');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameTable('emailBlocklist', 'emailBlacklist');
    }
}
