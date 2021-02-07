<?php

namespace app\components;

use app\components\mail\Tools as MailTools;
use app\models\db\{Amendment, EMailLog, Motion};
use app\models\exceptions\Internal;
use app\models\exceptions\MailNotSent;
use app\models\exceptions\ServerConfiguration;
use yii\helpers\Html;

class EmailNotifications
{
    /**
     * @throws Internal|ServerConfiguration
     */
    public static function sendMotionSubmissionConfirm(Motion $motion): void
    {
        if (!$motion->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $motionType = $motion->getMyMotionType();
        $initiator = $motion->getInitiators();
        if (count($initiator) > 0 && trim($initiator[0]->contactEmail) !== '') {
            if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
                $emailText  = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email');
                $min        = $motion->getMyMotionType()->getMotionSupportTypeClass()->getSettingsObj()->minSupporters;
                $emailText  = str_replace('%MIN%', $min, $emailText);
                $emailTitle = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email_subject');
            } else {
                $emailText  = $motionType->getConsultationTextWithFallback('motion', 'submitted_screening_email');
                $emailTitle = $motionType->getConsultationTextWithFallback('motion', 'submitted_screening_email_subject');
            }
            $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
            $plain      = $emailText;
            $motionHtml = '<h1>' . Html::encode($motionType->titleSingular) . ': ';
            $motionHtml .= Html::encode($motion->title);
            $motionHtml .= '</h1>';

            $sections = $motion->getSortedSections(true);
            foreach ($sections as $section) {
                $motionHtml   .= '<div>';
                $motionHtml   .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
                $typedSection = $section->getSectionType();
                $typedSection->setAbsolutizeLinks(true);
                $motionHtml .= $typedSection->getMotionEmailHtml();
                $motionHtml .= '</div>';
            }

            $html  = nl2br(Html::encode($plain)) . '<br><br>' . $motionHtml;
            $plain .= "\n\n" . HTMLTools::toPlainText($motionHtml);

            $plain = str_replace('%LINK%', $motionLink, $plain);
            $html  = str_replace('%LINK%', Html::a(Html::encode($motionLink), $motionLink), $html);

            $plain = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $plain);
            $html  = str_replace('%NAME_GIVEN%', Html::encode($initiator[0]->getGivenNameOrFull()), $html);

            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $motion->getMyConsultation(),
                    trim($initiator[0]->contactEmail),
                    null,
                    $emailTitle,
                    $plain,
                    $html
                );
            } catch (MailNotSent $e) {
            }
        }
    }

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

    /**
     * @throws Internal
     */
    public static function sendAmendmentSubmissionConfirm(Amendment $amendment): void
    {
        if (!$amendment->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $initiator  = $amendment->getInitiators();
        $motionType = $amendment->getMyMotion()->getMyMotionType();
        if (count($initiator) > 0 && $initiator[0]->contactEmail) {
            if ($amendment->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
                $emailText  = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email');
                $min        = $motionType->getAmendmentSupportTypeClass()->getSettingsObj()->minSupporters;
                $emailText  = str_replace('%MIN%', $min, $emailText);
                $emailTitle = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email_subject');
            } else {
                $emailText  = $motionType->getConsultationTextWithFallback('amend', 'submitted_screening_email');
                $emailTitle = $motionType->getConsultationTextWithFallback('amend', 'submitted_screening_email_subject');
            }
            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
            $plain         = $emailText;
            $amendmentHtml = '<h1>' . Html::encode(\Yii::t('amend', 'amendment')) . '</h1>';

            $sections = $amendment->getSortedSections(true);
            foreach ($sections as $section) {
                $amendmentHtml .= '<div>';
                $amendmentHtml .= $section->getSectionType()->getAmendmentPlainHtml();
                $amendmentHtml .= '</div>';
            }

            $html  = nl2br(Html::encode($plain)) . '<br><br>' . $amendmentHtml;
            $plain .= "\n\n" . HTMLTools::toPlainText($amendmentHtml);

            $plain = str_replace('%LINK%', $amendmentLink, $plain);
            $html  = str_replace('%LINK%', Html::a(Html::encode($amendmentLink), $amendmentLink), $html);

            $plain = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $plain);
            $html  = str_replace('%NAME_GIVEN%', Html::encode($initiator[0]->getGivenNameOrFull()), $html);

            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
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

    /**
     * @throws ServerConfiguration
     */
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
            } catch (MailNotSent $e) {
            }
        }
    }
}
