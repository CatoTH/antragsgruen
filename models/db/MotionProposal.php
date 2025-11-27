<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\proposedProcedure\Agenda;
use app\models\settings\AntragsgruenApp;
use app\models\exceptions\Internal;
use yii\behaviors\AttributeTypecastBehavior;

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

    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                ],
                'typecastAfterValidate' => true,
                'typecastBeforeSave' => true,
                'typecastAfterFind' => true,
            ],
        ];
    }

    public function flushViewCaches(): void
    {
        $motion = $this->getMotion();
        if ($motion) {
            $motion->flushViewCache();
            Agenda::getProposedAmendmentProcedureCache($motion, $this)->flushCache();
        }
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     *
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $result = parent::save($runValidation, $attributeNames);
        $this->flushViewCaches();
        return $result;
    }

    public function getCachedConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current) {
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
        return $this->getCachedConsultation()->getMotion($this->motionId);
    }

    public static function createNew(Motion $motion, int $version): MotionProposal
    {
        $motion->id !== null ?: throw new Internal("Motion not initialized");
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

    public function setPublished(): void
    {
        if ($this->visibleFrom) {
            return;
        }
        $this->visibleFrom = date('Y-m-d H:i:s');
        $this->save();

        $consultation = $this->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_PUBLISH_PROPOSAL, $this->id);
    }
}
