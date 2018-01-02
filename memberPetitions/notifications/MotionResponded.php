<?php

namespace app\memberPetitions\notifications;

use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\Motion;
use app\models\exceptions\MailNotSent;
use app\components\mail\Tools as MailTools;

class MotionResponded
{
    /**
     * MotionResponded constructor.
     *
     * @param Motion $motion
     * @throws MailNotSent
     */
    public function __construct(Motion $motion)
    {
        $initiator = $motion->getInitiators();
        if (count($initiator) == 0 || $initiator[0]->contactEmail == '') {
            return;
        }

        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
        $plain      = str_replace(
            ['%LINK%', '%NAME%', '%NAME_GIVEN%'],
            [$motionLink, $motion->getTitleWithPrefix(), $initiator[0]->getGivenNameOrFull()],
            'Wurde beantwortet' // @TODO
        );

        MailTools::sendWithLog(
            EMailLog::TYPE_MEMBER_PETITION,
            $motion->getMyConsultation()->site,
            trim($initiator[0]->contactEmail),
            $initiator[0]->userId,
            str_replace('%PREFIX%', $motion->getTitleWithPrefix(), 'Beantwortet'),
            $plain
        );
    }
}
