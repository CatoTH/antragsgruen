<?php

use yii\db\Migration;

/**
 * Class m210207_145533_remove_obsolete_fields
 */
class m210207_145533_remove_obsolete_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('consultationMotionType', 'supportType');
        $this->dropColumn('consultationMotionType', 'supportTypeSettings');
        $this->dropColumn('consultation', 'eventDateFrom');
        $this->dropColumn('consultation', 'eventDateTo');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('consultation', 'eventDateFrom', 'DATE');
        $this->addColumn('consultation', 'eventDateTO', 'DATE');
        $this->addColumn('consultationMotionType', 'supportType', 'INT');
        $this->addColumn('consultationMotionType', 'supportTypeSettings', 'TEXT NULL DEFAULT NULL');
    }
}
