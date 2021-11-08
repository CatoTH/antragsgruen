<?php

use yii\db\Migration;

class m211108_192545_non_public_motion_sections extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motionSection', 'public', 'tinyint NOT NULL default 1 AFTER dataRaw');
        $this->addColumn('amendmentSection', 'public', 'tinyint NOT NULL default 1 AFTER dataRaw');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('motionSection', 'public');
        $this->dropColumn('amendmentSection', 'public');
    }
}
