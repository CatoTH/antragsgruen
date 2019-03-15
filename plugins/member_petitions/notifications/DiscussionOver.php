<?php

namespace app\plugins\member_petitions\notifications;

use app\components\HTMLTools;
use app\models\db\EMailLog;
use app\models\db\Motion;
use app\models\exceptions\MailNotSent;
use app\components\mail\Tools as MailTools;
use app\models\settings\AntragsgruenApp;

class DiscussionOver
{
    /**
     * MotionResponded constructor.
     *
     * @param Motion $motion
     * @throws MailNotSent
     * @throws \app\models\exceptions\ServerConfiguration
     */
    public function __construct(Motion $motion)
    {
        $initiator = $motion->getInitiators();
        if (count($initiator) == 0 || $initiator[0]->contactEmail == '') {
            return;
        }

        $text = 'Hallo %NAME_GIVEN%,<br><br>Die Diskussionsphase deines Begehrens &quot;%NAME_MOTION%&quot; ist nun beendet. Du hast nun die Möglichkeit, die Rückmeldungen in deinen Antrag einzuarbeiten und dann Unterstützer*innen für dein Anliegen zu sammeln. Gehe dazu auf folgende Seite:<br><br>%LINK%<br><br>';

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $motionLink = $params->domainPlain . $motion->getMyConsultation()->urlPath . '/' . $motion->getMotionSlug();
        $text = str_replace(
            ['%LINK%', '%NAME_MOTION%', '%NAME_GIVEN%'],
            [$motionLink, $motion->title, $initiator[0]->getGivenNameOrFull()],
            $text
        );

        MailTools::sendWithLog(
            EMailLog::TYPE_MEMBER_PETITION,
            $motion->getMyConsultation(),
            trim($initiator[0]->contactEmail),
            $initiator[0]->userId,
            str_replace('%PREFIX%', $motion->getTitleWithPrefix(), 'Diskussionsphase vorbei'),
            HTMLTools::toPlainText($text)
        );
    }
}
