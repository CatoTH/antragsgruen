<?php

declare(strict_types=1);

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\components\UrlHelper;
use app\models\db\{EMailLog, Motion, MotionProposal};

class MotionProposedProcedure
{
    public function __construct(MotionProposal $proposal, ?string $text = '', ?string $fromName = null, ?string $replyTo = null)
    {
        $motion = $proposal->getMotion();
        $initiator = $motion->getInitiators();
        if (count($initiator) === 0 || !$initiator[0]->getContactOrUserEmail()) {
            return;
        }

        if ($text === null || trim($text) === '') {
            $text = static::getDefaultText($proposal);
        }
        if ($replyTo === null || trim($replyTo) === '') {
            $replyTo = MailTools::getDefaultReplyTo($motion, $motion->getMyConsultation(), \app\models\db\User::getCurrentUser());
        }

        $acceptLink = UrlHelper::createMotionUrl($motion, 'view', ['procedureToken' => $proposal->publicToken]);

        /** @noinspection PhpUnhandledExceptionInspection */
        MailTools::sendWithLog(
            EMailLog::TYPE_AMENDMENT_PROPOSED_PROCEDURE,
            $motion->getMyConsultation(),
            trim($initiator[0]->getContactOrUserEmail()),
            null,
            str_replace('%PREFIX%', $motion->getTitleWithPrefix(), \Yii::t('motion', 'proposal_email_title')),
            $text,
            '',
            ['%LINK%' => UrlHelper::absolutizeLink($acceptLink)],
            $fromName,
            $replyTo
        );
    }

    public static function getDefaultText(MotionProposal $proposal): string
    {
        $motion = $proposal->getMotion();
        $initiator     = $motion->getInitiators();
        $initiatorName = (count($initiator) > 0 ? $initiator[0]->getGivenNameOrFull() : null);

        $body = match ($proposal->proposalStatus) {
            Motion::STATUS_ACCEPTED => \Yii::t('motion', 'proposal_email_accepted'),
            Motion::STATUS_MODIFIED_ACCEPTED => \Yii::t('motion', 'proposal_email_modified'),
            default => \Yii::t('motion', 'proposal_email_other'),
        };

        return str_replace(
            ['%NAME%', '%NAME_GIVEN%'],
            [$motion->getTitleWithPrefix(), $initiatorName],
            $body
        );
    }
}
