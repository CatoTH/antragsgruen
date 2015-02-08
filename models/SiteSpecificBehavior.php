<?php
namespace app\models;

use app\models\db\Motion;
use app\models\wording\Wording;

class SiteSpecificBehavior
{

    /**
     * @param LayoutParams $params
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setLayoutParams(LayoutParams $params)
    {
    }

    /**
     * @return bool
     */
    public function isLoginForced()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function showAntragsgruenInSidebar()
    {
        return true;
    }

    /**
     * @param string $text
     * @return string
     */
    public function getNamespacedAccountHint($text)
    {
        return $text;
    }

    /**
     * @return string
     */
    public function getSubmitMotionStr()
    {
        return "";
    }

    /**
     * @param Motion $motion
     * @param Wording $wording
     * @param string $initiators
     * @return PDFSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPDFSettings(Motion $motion, Wording $wording, $initiators)
    {
        /** @var AntragsgruenAppParams $params */
        $params = \Yii::$app->params;

        $settings                    = new PDFSettings();
        $settings->logo              = $params->pdfLogo;
        $settings->initiators        = $initiators;
        $settings->motionTitle       = $motion->title;
        $settings->motionTextTitle   = $wording->get("Motion Text");
        $settings->motionTitlePrefix = $motion->titlePrefix;
        $settings->fontFamily        = "Courier";
        $settings->fontSize          = 10;

        return $settings;
    }

    /**
     * @return string
     */
    public function getMailFromName()
    {
        /** @var AntragsgruenAppParams $params */
        $params = \Yii::$app->params;
        return $params->mailFromName;
    }
}
