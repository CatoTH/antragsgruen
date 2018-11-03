<?php

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\EMailLog;
use app\models\exceptions\MailNotSent;

class AmendmentProposedProcedure
{
    /**
     * AmendmentProposedProcedure constructor.
     *
     * @param Amendment $amendment
     * @param string $text
     * @param null|string $fromName
     * @param null|string $replyTo
     * @throws MailNotSent
     */
    public function __construct(Amendment $amendment, $text = '', $fromName = null, $replyTo = null)
    {
        $initiator = $amendment->getInitiators();
        if (count($initiator) === 0 || $initiator[0]->contactEmail === '') {
            return;
        }

        if (trim($text) === '') {
            $text = static::getDefaultText($amendment);
        }

        MailTools::sendWithLog(
            EMailLog::TYPE_AMENDMENT_PROPOSED_PROCEDURE,
            $amendment->getMyConsultation(),
            trim($initiator[0]->contactEmail),
            null,
            str_replace('%PREFIX%', $amendment->getShortTitle(), \Yii::t('amend', 'proposal_email_title')),
            $text,
            '',
            null,
            $fromName,
            $replyTo
        );
    }

    /**
     * @param Amendment $amendment
     * @return string
     */
    public static function getDefaultText(Amendment $amendment)
    {
        $initiator = $amendment->getInitiators();

        switch ($amendment->proposalStatus) {
            case Amendment::STATUS_ACCEPTED:
                $body = \Yii::t('amend', 'proposal_email_accepted');
                break;
            case Amendment::STATUS_MODIFIED_ACCEPTED:
                $body = \Yii::t('amend', 'proposal_email_modified');
                break;
            default:
                $body = \Yii::t('amend', 'proposal_email_other');
                break;
        }

        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
        $plain         = str_replace(
            ['%LINK%', '%NAME%', '%NAME_GIVEN%'],
            [$amendmentLink, $amendment->getShortTitle(), $initiator[0]->getGivenNameOrFull()],
            $body
        );

        return $plain;
    }
}
