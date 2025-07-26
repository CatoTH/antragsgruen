<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\notifications\AmendmentProposedProcedure;
use app\models\SectionedParagraph;
use app\models\settings\AntragsgruenApp;
use app\components\{diff\AmendmentRewriter, diff\ArrayMatcher, diff\Diff, diff\DiffRenderer, HTMLTools, LineSplitter};
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use yii\db\ActiveRecord;

/**
 * @property int $motionId
 */
class MotionProposal extends IProposal
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionProposal';
    }

    public function rules(): array
    {
        return [
            [['motionId'], 'required'],
            [['motionId'], 'number'],
        ];
    }
    public function getCachedConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current) {
            if ($this->motionId === null) {
                return $current;
            }
            $mot = $current->getMotion($this->motionId);
            if ($mot) {
                return $current;
            }
        }
        $mot = Motion::findOne($this->motionId);

        return $mot?->getMyConsultation();
    }

    public function getMotion(): ?Motion
    {
        if ($this->motionId === null) {
            return null;
        }
        return $this->getCachedConsultation()->getMotion($this->motionId);
    }

    public static function createNew(Motion $motion, int $version): MotionProposal
    {
        $proposal = new MotionProposal();
        $proposal->version = $version;
        $proposal->motionId = $motion->id;
        $proposal->publicToken = \Yii::$app->getSecurity()->generateRandomString(32);

        return $proposal;
    }

    function getMyIMotion(): IMotion
    {
        return $this->getMotion();
    }

    public function hasAlternativeProposaltext(bool $includeOtherAmendments = false, int $internalNestingLevel = 0): bool
    {
        return in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) &&
            $this->proposalReferenceId && $this->getMyConsultation()->getAmendment($this->proposalReferenceId);
    }

    /**
     * @return array{motion: Motion, modification: Amendment}|null
     */
    public function getAlternativeProposaltextReference(): ?array
    {
        // This motion has a direct modification proposal
        if (in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) && $this->getMyProposalReference() && $this->getMotion()) {
            return [
                'motion' => $this->getMotion(),
                'modification' => $this->getMyProposalReference(),
            ];
        }

        return null;
    }
}
