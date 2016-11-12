<?php

use yii\db\Migration;

class m161112_161536_add_date_delete extends Migration
{
    public function safeUp()
    {
        $this->addColumn('consultation', 'dateDeletion', 'TIMESTAMP NULL DEFAULT NULL AFTER dateCreation');
        $this->addColumn('site', 'dateDeletion', 'TIMESTAMP NULL DEFAULT NULL AFTER dateCreation');
    }

    public function safeDown()
    {
        $this->dropColumn('consultation', 'dateDeletion');
        $this->dropColumn('site', 'dateDeletion');
    }
}
