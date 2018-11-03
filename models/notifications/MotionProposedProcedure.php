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
     * @param string $text
     * @param null|string $fromName
     * @param null|string $replyTo
     * @throws MailNotSent
     */
    public function __construct(Motion $motion, $text = '', $fromName = null, $replyTo = null)
    {
        $initiator = $motion->getInitiators();
        if (count($initiator) === 0 || $initiator[0]->contactEmail === '') {
            return;
        }

        if (trim($text) === '') {
            $text = static::getDefaultText($motion);
        }

        MailTools::sendWithLog(
            EMailLog::TYPE_AMENDMENT_PROPOSED_PROCEDURE,
            $motion->getMyConsultation(),
            trim($initiator[0]->contactEmail),
            null,
            str_replace('%PREFIX%', $motion->getTitleWithPrefix(), \Yii::t('motion', 'proposal_email_title')),
            $text,
            '',
            null,
            $fromName,
            $replyTo
        );
    }

    /**
     * @param Motion $motion
     * @return string
     */
    public static function getDefaultText(Motion $motion)
    {
        $initiator     = $motion->getInitiators();
        $initiatorName = (count($initiator) > 0 ? $initiator[0]->getGivenNameOrFull() : null);

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
            [$motionLink, $motion->getTitleWithPrefix(), $initiatorName],
            $body
        );
        return $plain;
    }
}
