<?php

declare(strict_types=1);

namespace app\models\db;

use app\components\{Tools, UrlHelper};
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\views\consultation\LayoutHelper;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * @property int|null $id
 * @property int $version
 * @property int|null $proposalStatus
 * @property int|null $proposalReferenceId
 * @property string|null $comment
 * @property string|null $visibleFrom
 * @property string|null $notifiedAt
 * @property string|null $notifiedText
 * @property int|null $userStatus
 * @property string|null $explanation ??
 * @property string $publicToken
 */
abstract class IProposal extends ActiveRecord
{
    abstract function getMyIMotion(): IMotion;

    public function getMyConsultation(): Consultation
    {
        return $this->getMyIMotion()->getMyConsultation();
    }

    public function getMyProposalReference(): ?Amendment
    {
        if ($this->proposalReferenceId) {
            return $this->getMyConsultation()->getAmendment($this->proposalReferenceId);
        } else {
            return null;
        }
    }

    public function proposalAllowsUserFeedback(): bool
    {
        if ($this->proposalStatus === null) {
            return false;
        } else {
            return true;
        }
    }

    public function proposalFeedbackHasBeenRequested(): bool
    {
        return ($this->proposalAllowsUserFeedback() && $this->notifiedAt !== null);
    }

    public function isProposalPublic(): bool
    {
        if (!$this->visibleFrom) {
            return false;
        }
        $visibleFromTs = Tools::dateSql2timestamp($this->visibleFrom);

        return ($visibleFromTs <= time());
    }

    public function hasVisibleAlternativeProposaltext(): bool
    {
        $imotion = $this->getMyIMotion();

        return ($this->hasAlternativeProposaltext(true) && (
                $this->isProposalPublic() ||
                User::havePrivilege($imotion->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::imotion($imotion)) ||
                $this->proposalFeedbackHasBeenRequested())
            );
    }

    abstract public function hasAlternativeProposaltext(bool $includeOtherAmendments = false, int $internalNestingLevel = 0): bool;

    abstract public function canSeeProposedProcedure(?string $procedureToken): bool;

    /**
     * Hint: "Limited" refers to functionality that comes after setting the actual proposed procedure,
     * i.e., internal comments, voting blocks and communication with the proposer
     */
    public function canEditLimitedProposedProcedure(): bool
    {
        $imotion = $this->getMyIMotion();

        return User::havePrivilege($imotion->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::imotion($imotion)) ||
            User::havePrivilege($imotion->getMyConsultation(), Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null);
    }

    public function canEditProposedProcedure(): bool
    {
        if (!$this->canEditLimitedProposedProcedure()) {
            return false;
        }

        $imotion = $this->getMyIMotion();

        if ($this->isProposalPublic()) {
            return $imotion->getMyConsultation()->getSettings()->ppEditableAfterPublication ||
                User::havePrivilege($imotion->getMyConsultation(), Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null);
        } else {
            return true;
        }
    }

    public function getFormattedProposalStatus(bool $includeExplanation = false): string
    {
        $imotion = $this->getMyIMotion();

        if ($imotion->status === IMotion::STATUS_WITHDRAWN) {
            return '<span class="withdrawn">' . \Yii::t('structure', 'STATUS_WITHDRAWN') . '</span>';
        }
        if ($imotion->status === IMotion::STATUS_MOVED && is_a($imotion, Motion::class)) {
            return '<span class="moved">' . LayoutHelper::getMotionMovedStatusHtml($imotion) . '</span>';
        }
        if ($imotion->status === IMotion::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION && is_a($imotion, Amendment::class)) {
            // @TODO backlink once we have a link from the moved amendment to the original, not just the other way round
            return \Yii::t('structure', 'STATUS_STATUS_PROPOSED_MOVE_TO_OTHER_MOTION');
        }
        $explStr = '';
        if ($includeExplanation && !$this->isProposalPublic()) {
            $explStr .= ' <span class="notVisible">' . \Yii::t('con', 'proposal_invisible') . '</span>';
        }
        if ($includeExplanation && $this->explanation) {
            $explStr .= '<blockquote class="explanation">' . \Yii::t('con', 'proposal_explanation') . ': ';
            if (str_contains($this->explanation, "\n")) {
                $explStr .= "<br>" . nl2br(Html::encode($this->explanation));
            } else {
                $explStr .= Html::encode($this->explanation);
            }
            $explStr .= '</blockquote>';
        }
        if ($this->proposalStatus === null || $this->proposalStatus == 0) {
            return $explStr;
        }

        /** @var Consultation $consultation */
        $consultation = $imotion->getMyConsultation();

        switch ($this->proposalStatus) {
            case IMotion::STATUS_ACCEPTED:
                return '<span class="accepted">' . Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(IMotion::STATUS_ACCEPTED)) . '</span>' . $explStr;
            case IMotion::STATUS_REJECTED:
                return '<span class="rejected">' . Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(IMotion::STATUS_REJECTED)) . '</span>' . $explStr;
            case IMotion::STATUS_MODIFIED_ACCEPTED:
                return '<span class="modifiedAccepted">' . Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(IMotion::STATUS_MODIFIED_ACCEPTED)) . '</span>' . $explStr;
            case IMotion::STATUS_REFERRED:
                return \Yii::t('amend', 'refer_to') . ': ' . Html::encode($this->comment) . $explStr;
            case IMotion::STATUS_OBSOLETED_BY_AMENDMENT:
                $refAmend = $consultation->getAmendment(intval($this->comment));
                if ($refAmend) {
                    $refAmendStr = Html::a($refAmend->getShortTitle(), UrlHelper::createAmendmentUrl($refAmend));

                    return \Yii::t('amend', 'obsoleted_by') . ': ' . $refAmendStr . $explStr;
                } else {
                    return Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(IMotion::STATUS_OBSOLETED_BY_AMENDMENT)) . $explStr;
                }
            case IMotion::STATUS_OBSOLETED_BY_MOTION:
                $refMot = $consultation->getMotion(intval($this->comment));
                if ($refMot) {
                    $refMotStr = Html::a($refMot->getTitleWithPrefix(), UrlHelper::createMotionUrl($refMot));

                    return \Yii::t('amend', 'obsoleted_by') . ': ' . $refMotStr . $explStr;
                } else {
                    return Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(IMotion::STATUS_OBSOLETED_BY_MOTION)) . $explStr;
                }
            case IMotion::STATUS_CUSTOM_STRING:
                return Html::encode($this->comment) . $explStr;
            case IMotion::STATUS_VOTE:
                $str = Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(IMotion::STATUS_VOTE));
                if ($this->getMyProposalReference()) {
                    $str .= ' (' . \Yii::t('structure', 'PROPOSED_MODIFIED_ACCEPTED') . ')';
                }
                if ($imotion->votingStatus === IMotion::STATUS_ACCEPTED) {
                    $str .= ' (' . \Yii::t('structure', 'STATUS_ACCEPTED') . ')';
                }
                if ($imotion->votingStatus === IMotion::STATUS_REJECTED) {
                    $str .= ' (' . \Yii::t('structure', 'STATUS_REJECTED') . ')';
                }
                $str .= $explStr;

                return $str;
            default:

                $name = Html::encode($consultation->getStatuses()->getProposedProcedureStatusName($this->proposalStatus) ?? (string) $this->proposalStatus);

                return $name . $explStr;
        }
    }
}
