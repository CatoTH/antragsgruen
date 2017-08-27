<?php

namespace app\models\notifications;

use app\components\mail\Tools as MailTools;
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\EMailLog;
use app\models\exceptions\MailNotSent;
use yii\helpers\Html;

class AmendmentProposedProcedure
{
    /**
     * MotionInitiallySubmitted constructor.
     *
     * This notification is sent to the contact e-mail-address entered when creating the amendment,
     * regardless if this amendment was created by a registered user or not
     *
     * @param Amendment $amendment
     * @throws MailNotSent
     */
    public function __construct(Amendment $amendment)
    {
        $initiator = $amendment->getInitiators();
        if (count($initiator) == 0 || $initiator[0]->contactEmail == '') {
            return;
        }

        switch ($amendment->proposalStatus) {
            case Amendment::STATUS_ACCEPTED:
                $body = \Yii::t('amend', 'proposal_email_accepted');
                break;
            case Amendment::STATUS_MODIFIED_ACCEPTED:
                $body = \Yii::t('amend', 'proposal_email_modified');
                break;
            default:
                $body = \Yii::t('amend', 'proposal_email_other');
                break;
        }

        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
        $plain         = str_replace(
            ['%LINK%', '%NAME%', '%NAME_GIVEN%'],
            [$amendmentLink, $amendment->getShortTitle(), $initiator[0]->getGivenNameOrFull()],
            $body
        );

        $amendmentHtml = '<h2>' . Html::encode(\Yii::t('amend', 'amendment')) . '</h2>';

        $sections = $amendment->getSortedSections(true);
        foreach ($sections as $section) {
            $amendmentHtml .= '<div>';
            $amendmentHtml .= $section->getSectionType()->getAmendmentPlainHtml();
            $amendmentHtml .= '</div>';
        }

        $html  = nl2br(Html::encode($plain)) . '<br><br>' . $amendmentHtml;
        $plain .= HTMLTools::toPlainText($html);

        MailTools::sendWithLog(
            EMailLog::TYPE_AMENDMENT_PROPOSED_PROCEDURE,
            $amendment->getMyConsultation()->site,
            trim($initiator[0]->contactEmail),
            null,
            str_replace('%PREFIX%', $amendment->getShortTitle(), \Yii::t('amend', 'proposal_email_title')),
            $plain,
            $html
        );
    }
}
