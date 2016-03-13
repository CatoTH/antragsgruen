<?php

namespace components;

use app\components\HTMLTools;
use app\components\mail\Tools;
use app\components\UrlHelper;
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
        if (!$motion->getConsultation()->getSettings()->initiatorConfirmEmails) {
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
            $plain .= HTMLTools::toPlainText($html);

            $plain = str_replace('%LINK%', $motionLink, $plain);
            $html  = str_replace('%LINK%', Html::a($motionLink, $motionLink), $html);

            try {
                Tools::sendWithLog(
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
        if (!$motion->getConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $initiator = $motion->getInitiators();

        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
        $plain      = \Yii::t('motion', 'published_email_body');
        $plain      = str_replace('%LINK%', $motionLink, $plain);
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
                Tools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $motion->getConsultation()->site,
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
            $html      = $emailText;
            $plain     = HTMLTools::toPlainText($html);

            $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
            $plain      = str_replace('%LINK%', $motionLink, $plain);
            $html       = str_replace('%LINK%', Html::a($motionLink, $motionLink), $html);

            try {
                Tools::sendWithLog(
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
    public static function sendAmendmentOnPublish(Amendment $amendment)
    {
        if (!$amendment->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $initiator = $amendment->getInitiators();
        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
            $plain         = str_replace(
                ['%LINK%', '%MOTION%'],
                [$amendmentLink, $amendment->getMyMotion()->titlePrefix],
                \Yii::t('amend', 'published_email_body')
            );

            $amendmentHtml = '<h2>' . Html::encode(\Yii::t('amend', 'amendment')) . '</h2>';

            $sections = $amendment->getSortedSections(true);
            foreach ($sections as $section) {
                $amendmentHtml .= '<div>';
                $amendmentHtml .= $section->getSectionType()->getAmendmentPlainHtml();
                $amendmentHtml .= '</div>';
            }

            $html = nl2br(Html::encode($plain)) . '<br><br>' . $amendmentHtml;
            $plain .= HTMLTools::toPlainText($html);

            try {
                Tools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $amendment->getMyConsultation()->site,
                    trim($initiator[0]->contactEmail),
                    null,
                    \Yii::t('amend', 'published_email_title'),
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
     * @param Amendment $amendment
     */
    public static function sendAmendmentSubmissionConfirm(Amendment $amendment)
    {
        if (!$amendment->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
            return;
        }

        $initiator = $amendment->getInitiators();
        if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
            $plain         = str_replace('%LINK%', $amendmentLink, \Yii::t('amend', 'submitted_screening_email'));
            $amendmentHtml = '<h2>' . Html::encode(\Yii::t('amend', 'amendment')) . '</h2>';

            $sections = $amendment->getSortedSections(true);
            foreach ($sections as $section) {
                $amendmentHtml .= '<div>';
                $amendmentHtml .= $section->getSectionType()->getAmendmentPlainHtml();
                $amendmentHtml .= '</div>';
            }

            $html = nl2br(Html::encode($plain)) . '<br><br>' . $amendmentHtml;
            $plain .= HTMLTools::toPlainText($html);

            try {
                Tools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $amendment->getMyConsultation()->site,
                    trim($initiator[0]->contactEmail),
                    null,
                    \Yii::t('amend', 'submitted_screening_email_subject'),
                    $plain,
                    $html
                );
            } catch (MailNotSent $e) {
            }
        }
    }
}
