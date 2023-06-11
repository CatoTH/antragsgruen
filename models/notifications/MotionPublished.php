<?php

namespace app\models\notifications;

use app\models\exceptions\ServerConfiguration;
use app\models\layoutHooks\Layout;
use app\models\db\{Consultation, EMailLog, ISupporter, Motion};
use app\components\{mail\Tools as MailTools, HTMLTools, RequestContext, UrlHelper};
use app\models\exceptions\MailNotSent;
use yii\helpers\Html;

class MotionPublished
{
    protected Consultation $consultation;

    public function __construct(
        protected Motion $motion
    ) {
        $this->consultation = $motion->getMyConsultation();

        $this->notifyInitiators();
    }

    /**
     * Sent to the initiator of the motion, if this option is enabled by the administrator
     * ("Send a confirmation e-mail to the proposer of a motion when it is published")
     *
     * This notification is sent to the contact e-mail-address entered when creating the motion,
     * regardless if it was created by a registered user or not.
     * (But not if it was created by an admin in the name of this user)
     */
    private function notifyInitiators(): void
    {
        if (!$this->consultation->getSettings()->initiatorConfirmEmails) {
            return;
        }
        if (count($this->motion->getInitiators()) === 0) {
            return;
        }
        $initiator = $this->motion->getInitiators()[0];
        if (!$initiator->getContactOrUserEmail() || $initiator->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, false)) {
            return;
        }

        $pluginEmail = Layout::getMotionPublishedInitiatorEmail($this->motion);
        if ($pluginEmail) {
            $html  = $pluginEmail['html'];
            $plain = $pluginEmail['plain'];
        } else {
            $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));

            $title      = $this->motion->getMyMotionType()->titleSingular . ': ' . $this->motion->title;
            $motionHtml = '<h1>' . Html::encode($title) . '</h1>';
            $sections   = $this->motion->getSortedSections(true);
            foreach ($sections as $section) {
                $motionHtml   .= '<div>';
                $motionHtml   .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
                $typedSection = $section->getSectionType();
                $typedSection->setAbsolutizeLinks(true);
                $motionHtml .= $typedSection->getMotionEmailHtml();
                $motionHtml .= '</div>';
            }

            $plainBase = str_replace(
                ['%LINK%', '%NAME_GIVEN%', '%TITLE%'],
                [$motionLink, $initiator->getGivenNameOrFull(), $this->motion->getTitleWithPrefix()],
                \Yii::t('motion', 'published_email_body')
            );

            $html  = HTMLTools::plainToHtml($plainBase) . '<br><br>' . $motionHtml;
            $plain = $plainBase . "\n\n" . HTMLTools::toPlainText($motionHtml);
        }

        try {
            MailTools::sendWithLog(
                EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                $this->consultation,
                trim($initiator->getContactOrUserEmail()),
                null,
                \Yii::t('motion', 'published_email_title'),
                $plain,
                $html
            );
        } catch (MailNotSent | ServerConfiguration $e) {
            $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
            RequestContext::getSession()->setFlash('error', $errMsg);
        }
    }
}
