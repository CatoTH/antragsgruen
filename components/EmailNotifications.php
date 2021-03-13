<?php

namespace app\components;

use app\components\mail\Tools as MailTools;
use app\models\db\{Amendment, EMailLog, Motion};
use app\models\exceptions\MailNotSent;
use app\models\exceptions\ServerConfiguration;
use yii\helpers\Html;

class EmailNotifications
{
    public static function sendMotionSupporterMinimumReached(Motion $motion): void
    {
        $initiator = $motion->getInitiators();
        if (count($initiator) > 0 && trim($initiator[0]->contactEmail) !== '') {
            $emailText  = $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'support_reached_email_body');
            $emailTitle = $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'support_reached_email_subject');

            $emailText = str_replace('%TITLE%', $motion->getTitleWithPrefix(), $emailText);
            $emailText = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $emailText);
            $html      = $emailText;
            $plain     = HTMLTools::toPlainText($html);

            $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
            $plain      = str_replace('%LINK%', $motionLink, $plain);
            $html       = str_replace('%LINK%', Html::a(Html::encode($motionLink), $motionLink), $html);

            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUPPORTER_REACHED,
                    $motion->getMyConsultation(),
                    trim($initiator[0]->contactEmail),
                    null,
                    $emailTitle,
                    $plain,
                    $html
                );
            } catch (MailNotSent | ServerConfiguration $e) {
            }
        }
    }

    public static function sendAmendmentSupporterMinimumReached(Amendment $amendment): void
    {
        $initiator = $amendment->getInitiators();
        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            $emailText  = $amendment->getMyMotionType()->getConsultationTextWithFallback('amend', 'support_reached_email_body');
            $emailTitle = $amendment->getMyMotionType()->getConsultationTextWithFallback('amend', 'support_reached_email_subject');

            $emailText = str_replace('%TITLE%', $amendment->getTitle(), $emailText);
            $emailText = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $emailText);
            $html      = $emailText;
            $plain     = HTMLTools::toPlainText($html);

            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
            $plain         = str_replace('%LINK%', $amendmentLink, $plain);
            $html          = str_replace('%LINK%', Html::a(Html::encode($amendmentLink), $amendmentLink), $html);

            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUPPORTER_REACHED,
                    $amendment->getMyConsultation(),
                    trim($initiator[0]->contactEmail),
                    null,
                    $emailTitle,
                    $plain,
                    $html
                );
            } catch (MailNotSent | ServerConfiguration $e) {
            }
        }
    }
}
