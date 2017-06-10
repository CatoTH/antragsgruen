<?php

use yii\db\Migration;

class m170226_134156_motionInitiatorsAmendmentMerging extends Migration
{
    public function up()
    {
        $this->addColumn('consultationMotionType', 'initiatorsCanMergeAmendments', 'tinyint NOT NULL DEFAULT 0 AFTER policySupportAmendments');
    }

    public function down()
    {
        $this->dropColumn('consultationMotionType', 'initiatorsCanMergeAmendments');
    }
}
