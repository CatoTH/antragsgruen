<?php

namespace app\models\notifications;

use app\components\HTMLTools;
use app\components\mail\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\EMailLog;
use app\models\db\ISupporter;
use app\models\exceptions\MailNotSent;
use app\models\exceptions\ServerConfiguration;
use yii\helpers\Html;

class AmendmentSubmitted extends Base implements IEmailAdmin
{
    public function __construct(
        protected Amendment $amendment
    ) {
        $this->consultation = $amendment->getMyConsultation();

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

        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->amendment->getTitle(), $amendmentLink, $this->amendment->getInitiatorsStr()],
            $this->amendment->getMyMotionType()->getConsultationTextWithFallback('amend', 'submitted_adminnoti_body')
        );
    }

    public function getEmailAdminSubject(): string
    {
        return $this->amendment->getMyMotionType()->getConsultationTextWithFallback('amend', 'submitted_adminnoti_title');
    }

    public function sendInitiatorConfirmation(): void
    {
        if ($this->amendment->status === Amendment::STATUS_SUBMITTED_SCREENED) {
            // The user will receive an "AmendmentPublished" notification through the "published_first"-handler
            return;
        }
        if (!$this->consultation->getSettings()->initiatorConfirmEmails) {
            return;
        }
        if (count($this->amendment->getInitiators()) === 0) {
            return;
        }

        $motionType = $this->amendment->getMyMotionType();
        $initiator  = $this->amendment->getInitiators()[0];
        if (!$initiator->getContactOrUserEmail() || $initiator->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, false)) {
            return;
        }

        if ($this->amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
            $emailText  = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email');
            $min        = $motionType->getAmendmentSupportTypeClass()->getSettingsObj()->minSupporters;
            $emailText  = str_replace('%MIN%', (string)$min, $emailText);
            $emailTitle = $motionType->getConsultationTextWithFallback('motion', 'submitted_supp_phase_email_subject');
        } else {
            $emailText  = $motionType->getConsultationTextWithFallback('amend', 'submitted_screening_email');
            $emailTitle = $motionType->getConsultationTextWithFallback('amend', 'submitted_screening_email_subject');
        }
        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
        $plain         = $emailText;
        $amendmentHtml = '<h1>' . Html::encode(\Yii::t('amend', 'amendment')) . '</h1>';

        $sections = $this->amendment->getSortedSections(true);
        foreach ($sections as $section) {
            $amendmentHtml .= '<div>';
            $amendmentHtml .= $section->getSectionType()->getAmendmentPlainHtml();
            $amendmentHtml .= '</div>';
        }

        if (str_contains($emailText, '<br>') || str_contains($emailText, '<p>')) {
            $html = $plain . '<br><br>' . $amendmentHtml;
        } else {
            $html  = nl2br(Html::encode($plain)) . '<br><br>' . $amendmentHtml;
        }

        $plain .= "\n\n" . HTMLTools::toPlainText($amendmentHtml);

        $plain = str_replace('%LINK%', $amendmentLink, $plain);
        $html  = str_replace('%LINK%', Html::a(Html::encode($amendmentLink), $amendmentLink), $html);

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
        } catch (MailNotSent | ServerConfiguration) {
        }
    }
}
