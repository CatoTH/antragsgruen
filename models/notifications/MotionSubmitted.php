<?php

namespace app\models\notifications;

use app\components\{HTMLTools, UrlHelper, mail\Tools};
use app\models\db\{EMailLog, ISupporter, Motion};
use app\models\exceptions\{MailNotSent, ServerConfiguration};
use yii\helpers\Html;

class MotionSubmitted extends Base implements IEmailAdmin
{
    public function __construct(
        protected Motion $motion
    ) {
        $this->consultation = $motion->getMyConsultation();

        parent::__construct();
    }

    public function send(): void
    {
        parent::send(); // This sends the admin-email
        $this->sendInitiatorConfirmation();
    }

    public function getEmailAdminText(): string
    {
        // @TODO Use different texts depending on the status
        $motionType = $this->motion->getMyMotionType();
        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->motion->getTitleWithIntro(), $motionLink, $this->motion->getInitiatorsStr()],
            $motionType->getConsultationTextWithFallback('motion', 'submitted_adminnoti_body')
        );
    }

    public function getEmailAdminSubject(): string
    {
        $motionType = $this->motion->getMyMotionType();
        return $motionType->getConsultationTextWithFallback('motion', 'submitted_adminnoti_title');
    }

    public function sendInitiatorConfirmation(): void
    {
        if ($this->motion->status === Motion::STATUS_SUBMITTED_SCREENED) {
            // The user will receive a "MotionPublished" notification through the "published_first"-handler
            return;
        }
        if (!$this->consultation->getSettings()->initiatorConfirmEmails) {
            return;
        }
        if (count($this->motion->getInitiators()) === 0) {
            return;
        }

        $motionType = $this->motion->getMyMotionType();
        $initiator = $this->motion->getInitiators()[0];

        if (!$initiator->getContactOrUserEmail() || $initiator->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, false)) {
            return;
        }

        if ($this->motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
            $emailText  = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email');
            $min        = $this->motion->getMyMotionType()->getMotionSupportTypeClass()->getSettingsObj()->minSupporters;
            $emailText  = str_replace('%MIN%', (string)$min, $emailText);
            $emailTitle = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email_subject');
        } else {
            $emailText  = $motionType->getConsultationTextWithFallback('motion', 'submitted_screening_email');
            $emailTitle = $motionType->getConsultationTextWithFallback('motion', 'submitted_screening_email_subject');
        }
        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        $plain      = $emailText;
        $motionHtml = '<h1>' . Html::encode($motionType->titleSingular) . ': ';
        $motionHtml .= Html::encode($this->motion->title);
        $motionHtml .= '</h1>';

        $sections = $this->motion->getSortedSections(true);
        foreach ($sections as $section) {
            $motionHtml   .= '<div>';
            $motionHtml   .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
            $typedSection = $section->getSectionType();
            $typedSection->setAbsolutizeLinks(true);
            $motionHtml .= $typedSection->getMotionEmailHtml();
            $motionHtml .= '</div>';
        }

        if (str_contains($emailText, '<br>') || str_contains($emailText, '<p>')) {
            $html = $plain . '<br><br>' . $motionHtml;
        } else {
            $html  = nl2br(Html::encode($plain)) . '<br><br>' . $motionHtml;
        }

        $plain .= "\n\n" . HTMLTools::toPlainText($motionHtml);

        $plain = str_replace('%LINK%', $motionLink, $plain);
        $html  = str_replace('%LINK%', Html::a(Html::encode($motionLink), $motionLink), $html);

        $plain = str_replace('%NAME_GIVEN%', $initiator->getGivenNameOrFull(), $plain);
        $html  = str_replace('%NAME_GIVEN%', Html::encode($initiator->getGivenNameOrFull()), $html);

        try {
            Tools::sendWithLog(
                EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                $this->consultation,
                trim($initiator->getContactOrUserEmail()),
                null,
                $emailTitle,
                $plain,
                $html
            );
        } catch (MailNotSent | ServerConfiguration $e) {
        }
    }
}
