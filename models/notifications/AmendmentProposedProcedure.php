<?php

declare(strict_types=1);

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\components\UrlHelper;
use app\models\db\{Amendment, AmendmentProposal, EMailLog};

class AmendmentProposedProcedure
{
    public function __construct(AmendmentProposal $proposal, ?string $text = null, ?string $fromName = null, ?string $replyTo = null)
    {
        $amendment = $proposal->getAmendment();
        $initiator = $amendment->getInitiators();
        if (count($initiator) === 0 || !$initiator[0]->getContactOrUserEmail()) {
            return;
        }

        if ($text === null || trim($text) === '') {
            $text = static::getDefaultText($proposal);
        }
        if ($replyTo === null || trim($replyTo) === '') {
            $replyTo = MailTools::getDefaultReplyTo($amendment, $amendment->getMyConsultation(), \app\models\db\User::getCurrentUser());
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        MailTools::sendWithLog(
            EMailLog::TYPE_AMENDMENT_PROPOSED_PROCEDURE,
            $amendment->getMyConsultation(),
            trim($initiator[0]->getContactOrUserEmail()),
            null,
            str_replace('%PREFIX%', $amendment->getShortTitle(), \Yii::t('amend', 'proposal_email_title')),
            $text,
            '',
            null,
            $fromName,
            $replyTo
        );
    }

    public static function getDefaultText(AmendmentProposal $proposal): string
    {
        $initiator = $proposal->getMyIMotion()->getInitiators();

        $body = match ($proposal->proposalStatus) {
            Amendment::STATUS_ACCEPTED => \Yii::t('amend', 'proposal_email_accepted'),
            Amendment::STATUS_MODIFIED_ACCEPTED => \Yii::t('amend', 'proposal_email_modified'),
            default => \Yii::t('amend', 'proposal_email_other'),
        };

        $url = UrlHelper::createAmendmentUrl($proposal->getAmendment(), 'view', ['procedureToken' => $proposal->publicToken]);
        $amendmentLink  = UrlHelper::absolutizeLink($url);

        return str_replace(
            ['%LINK%', '%NAME%', '%NAME_GIVEN%'],
            [$amendmentLink, $proposal->getAmendment()->getShortTitle(), $initiator[0]->getGivenNameOrFull()],
            $body
        );
    }
}
