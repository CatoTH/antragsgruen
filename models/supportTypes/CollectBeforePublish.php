<?php

namespace app\models\supportTypes;

use app\models\db\User;

class CollectBeforePublish extends SupportBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'supp_collect_before');
    }

    /**
     * @return bool
     */
    public static function hasInitiatorGivenSupporters()
    {
        return false;
    }

    /**
     * @return bool
     */
    public static function collectSupportersBeforePublication()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasFullTextSupporterField()
    {
        return User::havePrivilege($this->motionType->getConsultation(), User::PRIVILEGE_ANY);
    }
}
