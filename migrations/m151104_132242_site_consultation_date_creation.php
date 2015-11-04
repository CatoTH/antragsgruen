<?php

use yii\db\Schema;
use yii\db\Migration;

class m151104_132242_site_consultation_date_creation extends Migration
{
    public function safeUp()
    {
        $this->addColumn('site', 'dateCreation', 'timestamp null default null');
        $this->addColumn('consultation', 'dateCreation', 'timestamp null default null');
    }

    public function safeDown()
    {
        $this->dropColumn('site', 'dateCreation');
        $this->dropColumn('consultation', 'dateCreation');
    }
}
