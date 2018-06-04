<?php

use yii\db\Migration;

/**
 * Class m180604_080335_notification_settings
 */
class m180604_080335_notification_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('userNotification', 'settings', 'TEXT DEFAULT NULL AFTER notificationReferenceId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('userNotification', 'settings');
    }
}
