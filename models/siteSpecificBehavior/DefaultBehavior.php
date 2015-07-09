<?php
namespace app\models\siteSpecificBehavior;

use app\models\db\Motion;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Layout;
use app\models\settings\PDF;

class DefaultBehavior
{

    /**
     * @param Layout $params
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setLayoutParams(Layout $params)
    {
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
     * @param string $initiators
     * @return PDF
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPDFSettings(Motion $motion, $initiators)
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $settings                    = new PDF();
        $settings->logo              = $params->pdfLogo;
        $settings->initiators        = $initiators;
        $settings->motionTitle       = $motion->title;
        $settings->motionTextTitle   = 'Motion Text';
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
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        return $params->mailFromName;
    }
}
