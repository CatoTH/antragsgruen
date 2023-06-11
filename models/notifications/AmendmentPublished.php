<?php

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\models\exceptions\ServerConfiguration;
use app\models\layoutHooks\Layout;
use app\components\{HTMLTools, RequestContext, UrlHelper};
use app\models\db\{Amendment, Consultation, ConsultationMotionType, EMailLog, ISupporter, UserNotification};
use app\models\exceptions\MailNotSent;
use yii\helpers\Html;

class AmendmentPublished
{
    protected Consultation$consultation;

    /** @var string[] */
    protected array $alreadyNotified = [];

    public function __construct(
        protected Amendment $amendment
    ) {
        $this->consultation = $amendment->getMyConsultation();

        $this->notifyInitiators();
        $this->notifyMotionInitiators();
        $this->notifyAllUsers();
    }

    /**
     * Sent to the initiator of the amendments, if this option is enabled by the administrator
     * ("Send a confirmation e-mail to the proposer of a motion when it is published")
     *
     * This notification is sent to the contact e-mail-address entered when creating the motion,
     * regardless if this amendment was created by a registered user or not.
     * (But not if it was created by an admin in the name of this user)
     */
    private function notifyInitiators(): void
    {
        if (!$this->consultation->getSettings()->initiatorConfirmEmails) {
            return;
        }
        if (count($this->amendment->getInitiators()) === 0) {
            return;
        }
        $initiator = $this->amendment->getInitiators()[0];
        if (!$initiator->getContactOrUserEmail() || $initiator->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, false)) {
            return;
        }

        $pluginEmail = Layout::getAmendmentPublishedInitiatorEmail($this->amendment);
        if ($pluginEmail) {
            $html  = $pluginEmail['html'];
            $plain = $pluginEmail['plain'];
        } else {
            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
            $motionTitle   = $this->amendment->getMyMotion()->getTitleWithPrefix();
            $plain         = str_replace(
                ['%LINK%', '%MOTION%', '%NAME_GIVEN%'],
                [$amendmentLink, $motionTitle, $initiator->getGivenNameOrFull()],
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
            $plain = HTMLTools::toPlainText($html);
        }

        try {
            MailTools::sendWithLog(
                EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                $this->consultation,
                trim($initiator->getContactOrUserEmail()),
                null,
                \Yii::t('amend', 'published_email_title'),
                $plain,
                $html
            );
            $this->alreadyNotified[] = strtolower($initiator->getContactOrUserEmail());
        } catch (MailNotSent | ServerConfiguration $e) {
            $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
            RequestContext::getSession()->setFlash('error', $errMsg);
        }
    }

    /**
     * Sent to to the initiator of the motion that is affected by this amendment
     * Only sent if the initiator is a registered user with confirmed e-mail and enabled notifications
     */
    private function notifyMotionInitiators(): void
    {
        $motion    = $this->amendment->getMyMotion();
        $initiator = $motion->getInitiators();
        if (count($initiator) === 0 || !$initiator[0]->user) {
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
                ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION,
                ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION
            ]
        );

        $motionTitle = $this->amendment->getMyMotion()->getTitleWithPrefix();
        $subject     = str_replace('%TITLE%', $motionTitle, \Yii::t('user', 'noti_new_amend_title'));
        $initiators  = $this->amendment->getInitiatorsStr();

        if ($mergingAllowed) {
            $mergeHint = \Yii::t('user', 'noti_amend_mymotion_merge');
        } else {
            $mergeHint = '';
        }
        $text = str_replace(
            ['%CONSULTATION%', '%TITLE%', '%LINK%', '%MERGE_HINT%', '%INITIATOR%'],
            [$this->consultation->title, $motionTitle, $amendmentLink, $mergeHint, $initiators],
            \Yii::t('user', 'noti_amend_mymotion')
        );

        $user->notificationEmail($this->consultation, $subject, $text, EMailLog::TYPE_MOTION_NOTIFICATION_USER);

        $this->alreadyNotified[] = strtolower($user->email);
        $noti->lastNotification  = date('Y-m-d H:i:s');
        $noti->save();
    }

    private function notifyAllUsers(): void
    {
        foreach ($this->consultation->getUserNotificationsType(UserNotification::NOTIFICATION_NEW_AMENDMENT) as $noti) {
            if (in_array(strtolower($noti->user->email), $this->alreadyNotified)) {
                continue;
            }

            $motionTitle = $this->amendment->getMyMotion()->getTitleWithPrefix();
            $subject     = str_replace('%TITLE%', $motionTitle, \Yii::t('user', 'noti_new_amend_title'));
            $link        = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
            $text        = str_replace(
                ['%CONSULTATION%', '%TITLE%', '%LINK%', '%INITIATOR%'],
                [$this->consultation->title, $motionTitle, $link, $this->amendment->getInitiatorsStr()],
                \Yii::t('user', 'noti_new_motion_body')
            );
            $notiType = EMailLog::TYPE_MOTION_NOTIFICATION_USER;
            $noti->user->notificationEmail($this->consultation, $subject, $text, $notiType);

            $this->alreadyNotified[] = strtolower($noti->user->email);
            $noti->lastNotification  = date('Y-m-d H:i:s');
            $noti->save();
        }
    }
}
