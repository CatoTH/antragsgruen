<?php

use yii\db\Expression;
use yii\db\Migration;

class m160305_214526_support_likes_dislikes extends Migration
{
    /**
     */
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'motionLikesDislikes', 'int');
        $this->addColumn('consultationMotionType', 'amendmentLikesDislikes', 'int');
        $this->update('consultationMotionType', ['motionLikesDislikes' => new Expression('policySupportMotions != 0')]);
        $this->update('consultationMotionType', ['amendmentLikesDislikes' => new Expression('policySupportAmendments != 0')]);
    }

    /**
     */
    public function safeDown()
    {
        $this->dropColumn('consultationMotionType', 'motionLikesDislikes');
        $this->dropColumn('consultationMotionType', 'amendmentLikesDislikes');
    }
}
