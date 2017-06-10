<?php

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\ConsultationMotionType;
use app\models\db\EMailLog;
use app\models\db\UserNotification;
use app\models\exceptions\MailNotSent;
use yii\helpers\Html;

class AmendmentPublished
{
    /** @var Amendment */
    protected $amendment;

    /** @var string[] */
    protected $alreadyNotified = [];

    /**
     * MotionInitiallySubmitted constructor.
     * @param Amendment $amendment
     */
    public function __construct(Amendment $amendment)
    {
        $this->amendment    = $amendment;
        $this->consultation = $amendment->getMyConsultation();

        $this->notifyInitiators();
        $this->notifyMotionInitiators();
        $this->notifyAllUsers();
    }

    /**
     * Sent to to the initiator of the amendments, if this option is enabled by the administrator
     * ("Send a confirmation e-mail to the proposer of a motion when it is published")
     *
     * This notification is sent to the contact e-mail-address entered when creating the motion,
     * regardless if this amendment was created by a registered user or not
     */
    private function notifyInitiators()
    {
        if (!$this->consultation->getSettings()->initiatorConfirmEmails) {
            return;
        }
        $initiator = $this->amendment->getInitiators();
        if (count($initiator) == 0 || $initiator[0]->contactEmail == '') {
            return;
        }
        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
        $plain         = str_replace(
            ['%LINK%', '%MOTION%', '%NAME_GIVEN%'],
            [$amendmentLink, $this->amendment->getMyMotion()->titlePrefix, $initiator[0]->getGivenNameOrFull()],
            \Yii::t('amend', 'published_email_body')
        );

        $amendmentHtml = '<h2>' . Html::encode(\Yii::t('amend', 'amendment')) . '</h2>';

        $sections = $this->amendment->getSortedSections(true);
        foreach ($sections as $section) {
            $amendmentHtml .= '<div>';
            $amendmentHtml .= $section->getSectionType()->getAmendmentPlainHtml();
            $amendmentHtml .= '</div>';
        }

        $html  = nl2br(Html::encode($plain)) . '<br><br>' . $amendmentHtml;
        $plain .= HTMLTools::toPlainText($html);

        try {
            MailTools::sendWithLog(
                EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                $this->consultation->site,
                trim($initiator[0]->contactEmail),
                null,
                \Yii::t('amend', 'published_email_title'),
                $plain,
                $html
            );
            $this->alreadyNotified[] = strtolower($initiator[0]->contactEmail);
        } catch (MailNotSent $e) {
            $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
            \yii::$app->session->setFlash('error', $errMsg);
        }
    }

    /**
     * Sent to to the initiator of the motion that is affected by this amendment
     * Only sent if the initiator is a registered user with confirmed e-mail and enabled notifications
     */
    private function notifyMotionInitiators()
    {
        $motion    = $this->amendment->getMyMotion();
        $initiator = $motion->getInitiators();
        if (count($initiator) == 0 || !$initiator[0]->user) {
            return;
        }

        $user = $initiator[0]->user;
        if (in_array(strtolower($user->email), $this->alreadyNotified)) {
            return;
        }

        $notiType = UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION;
        $noti     = UserNotification::getNotification($user, $this->consultation, $notiType);
        if (!$noti) {
            return;
        }

        $amendmentLink  = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
        $mergingAllowed = in_array(
            $motion->getMyMotionType()->initiatorsCanMergeAmendments,
            [
                ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION,
                ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISSION
            ]
        );

        $motionTitle = $this->amendment->getMyMotion()->getTitleWithPrefix();
        $subject     = str_replace('%TITLE%', $motionTitle, \Yii::t('user', 'noti_new_amend_title'));

        if ($mergingAllowed) {
            $mergeHint = \Yii::t('user', 'noti_amend_mymotion_merge');
        } else {
            $mergeHint = '';
        }
        $text = str_replace(
            ['%CONSULTATION%', '%TITLE%', '%LINK%', '%MERGE_HINT%'],
            [$this->consultation->title, $motionTitle, $amendmentLink, $mergeHint],
            \Yii::t('user', 'noti_amend_mymotion')
        );

        $user->notificationEmail($this->consultation, $subject, $text);

        $this->alreadyNotified[] = strtolower($user->email);
        $noti->lastNotification  = date('Y-m-d H:i:s');
        $noti->save();
    }

    /**
     */
    private function notifyAllUsers()
    {
        foreach ($this->consultation->getUserNotificationsType(UserNotification::NOTIFICATION_NEW_AMENDMENT) as $noti) {
            if (in_array(strtolower($noti->user->email), $this->alreadyNotified)) {
                continue;
            }

            $motionTitle = $this->amendment->getMyMotion()->getTitleWithPrefix();
            $subject     = str_replace('%TITLE%', $motionTitle, \Yii::t('user', 'noti_new_amend_title'));
            $link        = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
            $text        = str_replace(
                ['%CONSULTATION%', '%TITLE%', '%LINK%'],
                [$this->consultation->title, $motionTitle, $link],
                \Yii::t('user', 'noti_new_motion_body')
            );
            $noti->user->notificationEmail($this->consultation, $subject, $text);

            $this->alreadyNotified[] = strtolower($noti->user->email);
            $noti->lastNotification  = date('Y-m-d H:i:s');
            $noti->save();
        }
    }
}
