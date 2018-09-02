<?php

namespace app\models\supportTypes;

use app\models\db\User;

class GivenByInitiator extends SupportBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'supp_given_by_initiator');
    }

    /**
     * @return bool
     */
    public static function hasInitiatorGivenSupporters()
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function collectSupportersBeforePublication()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasFullTextSupporterField()
    {
        return User::havePrivilege($this->motionType->getConsultation(), User::PRIVILEGE_ANY);
    }
}
