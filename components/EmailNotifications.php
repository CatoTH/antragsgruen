<?php

namespace app\components;

use app\components\mail\Tools as MailTools;
use app\models\db\Amendment;
use app\models\db\EMailLog;
use app\models\db\Motion;
use app\models\exceptions\MailNotSent;
use yii\helpers\Html;

class EmailNotifications
{
    /**
     * @param Motion $motion
     * @throws \app\models\exceptions\MailNotSent
     */
    public static function sendMotionSubmissionConfirm(Motion $motion)
    {
        if (!$motion->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $initiator = $motion->getInitiators();
        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            if ($motion->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
                $emailText  = \Yii::t('motion', 'submitted_supp_phase_email');
                $min        = $motion->motionType->getAmendmentSupportTypeClass()->getMinNumberOfSupporters();
                $emailText  = str_replace('%MIN%', $min, $emailText);
                $emailTitle = \Yii::t('motion', 'submitted_supp_phase_email_subject');
            } else {
                $emailText  = \Yii::t('motion', 'submitted_screening_email');
                $emailTitle = \Yii::t('motion', 'submitted_screening_email_subject');
            }
            $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
            $plain      = $emailText;
            $motionHtml = '<h1>' . Html::encode($motion->motionType->titleSingular) . ': ';
            $motionHtml .= Html::encode($motion->title);
            $motionHtml .= '</h1>';

            $sections = $motion->getSortedSections(true);
            foreach ($sections as $section) {
                $motionHtml .= '<div>';
                $motionHtml .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
                $motionHtml .= $section->getSectionType()->getMotionPlainHtml();
                $motionHtml .= '</div>';
            }

            $html = nl2br(Html::encode($plain)) . '<br><br>' . $motionHtml;
            $plain .= "\n\n" . HTMLTools::toPlainText($motionHtml);

            $plain = str_replace('%LINK%', $motionLink, $plain);
            $html  = str_replace('%LINK%', Html::a($motionLink, $motionLink), $html);

            $plain = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $plain);
            $html  = str_replace('%NAME_GIVEN%', Html::encode($initiator[0]->getGivenNameOrFull()), $html);

            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $motion->getMyConsultation()->site,
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

    /**
     * @param Motion $motion
     * @throws \app\models\exceptions\MailNotSent
     */
    public static function sendMotionOnPublish(Motion $motion)
    {
        if (!$motion->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $initiator = $motion->getInitiators();

        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
        $plain      = \Yii::t('motion', 'published_email_body');
        $plain      = str_replace('%LINK%', $motionLink, $plain);
        $plain      = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $plain);
        $title      = $motion->motionType->titleSingular . ': ' . $motion->title;
        $motionHtml = '<h1>' . Html::encode($title) . '</h1>';
        $sections   = $motion->getSortedSections(true);

        foreach ($sections as $section) {
            $motionHtml .= '<div>';
            $motionHtml .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
            $motionHtml .= $section->getSectionType()->getMotionPlainHtml();
            $motionHtml .= '</div>';
        }
        $html = nl2br(Html::encode($plain)) . '<br><br>' . $motionHtml;
        $plain .= HTMLTools::toPlainText($html);

        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $motion->getMyConsultation()->site,
                    trim($initiator[0]->contactEmail),
                    null,
                    \Yii::t('motion', 'published_email_title'),
                    $plain,
                    $html
                );
            } catch (MailNotSent $e) {
                $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                \yii::$app->session->setFlash('error', $errMsg);
            }
        }
    }

    /**
     * @param Motion $motion
     */
    public static function sendMotionSupporterMinimumReached(Motion $motion)
    {
        $initiator = $motion->getInitiators();
        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            $emailText  = \Yii::t('motion', 'support_reached_email_body');
            $emailTitle = \Yii::t('motion', 'support_reached_email_subject');

            $emailText = str_replace('%TITLE%', $motion->getTitleWithPrefix(), $emailText);
            $emailText = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $emailText);
            $html      = $emailText;
            $plain     = HTMLTools::toPlainText($html);

            $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
            $plain      = str_replace('%LINK%', $motionLink, $plain);
            $html       = str_replace('%LINK%', Html::a($motionLink, $motionLink), $html);
            
            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUPPORTER_REACHED,
                    $motion->getMyConsultation()->site,
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

    /**
     * @param Amendment $amendment
     */
    public static function sendAmendmentSubmissionConfirm(Amendment $amendment)
    {
        if (!$amendment->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $initiator = $amendment->getInitiators();
        $motionType = $amendment->getMyMotion()->motionType;
        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            if ($amendment->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
                $emailText  = \Yii::t('motion', 'submitted_supp_phase_email');
                $min        = $motionType->getAmendmentSupportTypeClass()->getMinNumberOfSupporters();
                $emailText  = str_replace('%MIN%', $min, $emailText);
                $emailTitle = \Yii::t('motion', 'submitted_supp_phase_email_subject');
            } else {
                $emailText  = \Yii::t('amend', 'submitted_screening_email');
                $emailTitle = \Yii::t('amend', 'submitted_screening_email_subject');
            }
            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
            $plain      = $emailText;
            $amendmentHtml = '<h1>' . Html::encode(\Yii::t('amend', 'amendment')) . '</h1>';

            $sections = $amendment->getSortedSections(true);
            foreach ($sections as $section) {
                $amendmentHtml .= '<div>';
                $amendmentHtml .= $section->getSectionType()->getAmendmentPlainHtml();
                $amendmentHtml .= '</div>';
            }

            $html = nl2br(Html::encode($plain)) . '<br><br>' . $amendmentHtml;
            $plain .= "\n\n" . HTMLTools::toPlainText($amendmentHtml);

            $plain = str_replace('%LINK%', $amendmentLink, $plain);
            $html  = str_replace('%LINK%', Html::a($amendmentLink, $amendmentLink), $html);

            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $amendment->getMyConsultation()->site,
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

    /**
     * @param Amendment $amendment
     */
    public static function sendAmendmentSupporterMinimumReached(Amendment $amendment)
    {
        $initiator = $amendment->getInitiators();
        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            $emailText  = \Yii::t('motion', 'support_reached_email_body');
            $emailTitle = \Yii::t('motion', 'support_reached_email_subject');

            $emailText = str_replace('%TITLE%', $amendment->getTitle(), $emailText);
            $emailText = str_replace('%NAME_GIVEN%', $initiator[0]->getGivenNameOrFull(), $emailText);
            $html      = $emailText;
            $plain     = HTMLTools::toPlainText($html);

            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
            $plain      = str_replace('%LINK%', $amendmentLink, $plain);
            $html       = str_replace('%LINK%', Html::a($amendmentLink, $amendmentLink), $html);

            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUPPORTER_REACHED,
                    $amendment->getMyConsultation()->site,
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
