<?php

namespace app\models\supportTypes;

use app\models\settings\Privileges;
use app\models\db\User;

class GivenByInitiator extends SupportBase
{
    public static function getTitle(): string
    {
        return \Yii::t('structure', 'supp_given_by_initiator');
    }

    public static function hasInitiatorGivenSupporters(): bool
    {
        return true;
    }

    public static function collectSupportersBeforePublication(): bool
    {
        return false;
    }

    public function hasFullTextSupporterField(): bool
    {
        return User::havePrivilege($this->motionType->getConsultation(), Privileges::PRIVILEGE_ANY, null);
    }
}
