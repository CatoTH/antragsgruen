<?php

use yii\db\Migration;

class m170611_195343_global_alternatives extends Migration
{
    public function up()
    {
        $this->addColumn('amendment', 'globalAlternative', 'TINYINT DEFAULT 0');
    }

    public function down()
    {
        $this->dropColumn('amendment', 'globalAlternative');
    }
}
