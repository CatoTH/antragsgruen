<?php

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\Motion;
use app\models\exceptions\MailNotSent;

class MotionProposedProcedure
{
    /**
     * MotionProposedProcedure constructor.
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

        switch ($motion->proposalStatus) {
            case Motion::STATUS_ACCEPTED:
                $body = \Yii::t('motion', 'proposal_email_accepted');
                break;
            case Motion::STATUS_MODIFIED_ACCEPTED:
                $body = \Yii::t('motion', 'proposal_email_modified');
                break;
            default:
                $body = \Yii::t('motion', 'proposal_email_other');
                break;
        }

        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
        $plain      = str_replace(
            ['%LINK%', '%NAME%', '%NAME_GIVEN%'],
            [$motionLink, $motion->getTitleWithPrefix(), $initiator[0]->getGivenNameOrFull()],
            $body
        );

        MailTools::sendWithLog(
            EMailLog::TYPE_AMENDMENT_PROPOSED_PROCEDURE,
            $motion->getMyConsultation()->site,
            trim($initiator[0]->contactEmail),
            null,
            str_replace('%PREFIX%', $motion->getTitleWithPrefix(), \Yii::t('motion', 'proposal_email_title')),
            $plain
        );
    }
}
