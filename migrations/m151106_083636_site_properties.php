<?php

use yii\db\Schema;
use yii\db\Migration;

class m151106_083636_site_properties extends Migration
{
    public function safeUp()
    {
        $this->addColumn('site', 'organization', 'string null default null');
        $this->alterColumn('site', 'subdomain', 'string(45) null default null');
        $this->addColumn('site', 'status', 'smallint default 0');
    }

    public function safeDown()
    {
        $this->dropColumn('site', 'organization');
        $this->alterColumn('site', 'subdomain', 'string not null');
        $this->dropColumn('site', 'status');
    }
}
