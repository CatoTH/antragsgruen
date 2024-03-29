<?php

namespace app\plugins\member_petitions\notifications;

use app\models\db\Motion;
use app\models\notifications\{Base, IEmailAdmin};
use app\models\settings\AntragsgruenApp;

class AdminResponseRequired extends Base implements IEmailAdmin
{
    /** @var Motion */
    protected $motion;

    public function __construct(Motion $motion)
    {
        $this->motion       = $motion;
        $this->consultation = $motion->getMyConsultation();

        parent::__construct();
    }

    public function getEmailAdminText(): string
    {
        $text = 'Hallo,<br><br>Das folgende Mitgliederbegehren ist erfolgreich und sollte nun behandelt werden:<br><br>%LINK%<br><br>';

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $motionLink = $params->domainPlain . $this->consultation->urlPath . '/' . $this->motion->getMotionSlug();

        return str_replace(
            ['%LINK%', '%NAME_MOTION%'],
            [$motionLink, $this->motion->title],
            $text
        );
    }

    /**
     * @return string
     */
    public function getEmailAdminSubject(): string
    {
        return "Mitgliederbegehren erfolgereich";
    }
}
