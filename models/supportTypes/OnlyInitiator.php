<?php

namespace app\models\supportTypes;

class OnlyInitiator extends SupportBase
{
    public static function getTitle(): string
    {
        return \Yii::t('structure', 'supp_only_initiators');
    }

    public static function hasInitiatorGivenSupporters(): bool
    {
        return false;
    }

    protected function fixSettings(): void
    {
        $this->settingsObject->minSupporters       = 0;
        $this->settingsObject->allowMoreSupporters = false;
    }
}
