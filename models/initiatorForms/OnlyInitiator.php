<?php

namespace app\models\initiatorForms;

class OnlyInitiator extends DefaultFormBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return 'Nur die AntragstellerIn';
    }

    /**
     * @return bool
     */
    public static function hasSupporters()
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getSettings()
    {
        return null;
    }

    /**
     * @param array $settings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSettings($settings)
    {
    }
}
