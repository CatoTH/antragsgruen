<?php

use yii\db\Migration;

class m241027_074032_pages_policies extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationText', 'policyRead', 'TEXT DEFAULT NULL AFTER `menuPosition`');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationText', 'policyRead');
    }
}
