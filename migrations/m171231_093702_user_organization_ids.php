<?php

use yii\db\Migration;

/**
 * Class m171231_093702_user_organization_ids
 */
class m171231_093702_user_organization_ids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('user', 'organizationIds', 'TEXT');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'organizationIds');
    }
}
