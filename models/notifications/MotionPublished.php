<?php

namespace app\models\notifications;

use app\models\exceptions\ServerConfiguration;
use app\models\layoutHooks\Layout;
use app\models\db\{Consultation, EMailLog, Motion};
use app\components\{mail\Tools as MailTools, HTMLTools, UrlHelper};
use app\models\exceptions\MailNotSent;
use yii\helpers\Html;

class MotionPublished
{
    /** @var Motion */
    protected $motion;

    /** @var Consultation */
    protected $consultation;

    /** @var string[] */
    protected $alreadyNotified = [];

    public function __construct(Motion $motion)
    {
        $this->motion       = $motion;
        $this->consultation = $motion->getMyConsultation();

        $this->notifyInitiators();
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
        $initiator = $this->motion->getInitiators();
        if (count($initiator) === 0 || !$initiator[0]->getContactOrUserEmail()) {
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
                [$motionLink, $initiator[0]->getGivenNameOrFull(), $this->motion->getTitleWithPrefix()],
                \Yii::t('motion', 'published_email_body')
            );

            $html  = HTMLTools::plainToHtml($plainBase) . '<br><br>' . $motionHtml;
            $plain = $plainBase . "\n\n" . HTMLTools::toPlainText($motionHtml);
        }

        if (count($initiator) > 0 && $initiator[0]->getContactOrUserEmail()) {
            try {
                MailTools::sendWithLog(
                    EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                    $this->consultation,
                    trim($initiator[0]->getContactOrUserEmail()),
                    null,
                    \Yii::t('motion', 'published_email_title'),
                    $plain,
                    $html
                );
            } catch (MailNotSent | ServerConfiguration $e) {
                $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                \yii::$app->session->setFlash('error', $errMsg);
            }
        }
    }

}
