<?php

namespace app\models\supportTypes;

use app\models\settings\Privileges;
use app\models\db\User;

class CollectBeforePublish extends SupportBase
{
    public static function getTitle(): string
    {
        return \Yii::t('structure', 'supp_collect_before');
    }

    public static function hasInitiatorGivenSupporters(): bool
    {
        return false;
    }

    public static function collectSupportersBeforePublication(): bool
    {
        return true;
    }

    public function hasFullTextSupporterField(): bool
    {
        return User::havePrivilege($this->motionType->getConsultation(), Privileges::PRIVILEGE_ANY, null);
    }
}
