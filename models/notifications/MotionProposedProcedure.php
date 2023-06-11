<?php

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\components\UrlHelper;
use app\models\settings\AntragsgruenApp;
use app\models\db\{EMailLog, Motion};

class MotionProposedProcedure
{
    public static function getPpOpenAcceptToken(Motion $motion): string
    {
        $base = 'getPpOpenAcceptToken' . AntragsgruenApp::getInstance()->randomSeed . $motion->id;

        /** @noinspection PhpUnhandledExceptionInspection */
        return substr(preg_replace('/[^\w]/siu', '', base64_encode(sodium_crypto_generichash($base))), 0, 20);
    }

    public function __construct(Motion $motion, ?string $text = '', ?string $fromName = null, ?string $replyTo = null)
    {
        $initiator = $motion->getInitiators();
        if (count($initiator) === 0 || !$initiator[0]->getContactOrUserEmail()) {
            return;
        }

        if ($text === null || trim($text) === '') {
            $text = static::getDefaultText($motion);
        }
        if ($replyTo === null || trim($replyTo) === '') {
            $replyTo = MailTools::getDefaultReplyTo($motion, $motion->getMyConsultation(), \app\models\db\User::getCurrentUser());
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        MailTools::sendWithLog(
            EMailLog::TYPE_AMENDMENT_PROPOSED_PROCEDURE,
            $motion->getMyConsultation(),
            trim($initiator[0]->getContactOrUserEmail()),
            null,
            str_replace('%PREFIX%', $motion->getTitleWithPrefix(), \Yii::t('motion', 'proposal_email_title')),
            $text,
            '',
            null,
            $fromName,
            $replyTo
        );
    }

    public static function getDefaultText(Motion $motion): string
    {
        $initiator     = $motion->getInitiators();
        $initiatorName = (count($initiator) > 0 ? $initiator[0]->getGivenNameOrFull() : null);

        $body = match ($motion->proposalStatus) {
            Motion::STATUS_ACCEPTED => \Yii::t('motion', 'proposal_email_accepted'),
            Motion::STATUS_MODIFIED_ACCEPTED => \Yii::t('motion', 'proposal_email_modified'),
            default => \Yii::t('motion', 'proposal_email_other'),
        };

        $procedureToken = static::getPpOpenAcceptToken($motion);
        $motionLink     = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'view', ['procedureToken' => $procedureToken]));

        return str_replace(
            ['%LINK%', '%NAME%', '%NAME_GIVEN%'],
            [$motionLink, $motion->getTitleWithPrefix(), $initiatorName],
            $body
        );
    }
}
