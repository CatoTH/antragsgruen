<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\{AntragsgruenApp, DebateItem as DebateItemSettings};
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * One debate episode: while dateStopped is null, the referenced motion, amendment or agenda item
 * is the consultation's currently debated item. Rows with dateStopped set form the debate history.
 * Exactly one of motionId / amendmentId / agendaItemId is set.
 *
 * @property int $id
 * @property int $consultationId
 * @property int|null $motionId
 * @property int|null $amendmentId
 * @property int|null $agendaItemId
 * @property string|null $freeText
 * @property int|null $votingBlockId
 * @property string $dateStarted
 * @property string|null $dateStopped
 * @property string|null $settings
 *
 * @property Consultation $consultation
 * @property Motion|null $motion
 * @property Amendment|null $amendment
 * @property ConsultationAgendaItem|null $agendaItem
 * @property VotingBlock|null $votingBlock
 */
class DebateItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'debateItem';
    }

    /**
     * @return ActiveQuery<Consultation>
     */
    public function getConsultation(): ActiveQuery
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getMyConsultation(): Consultation
    {
        if (Consultation::getCurrent() && Consultation::getCurrent()->id === $this->consultationId) {
            return Consultation::getCurrent();
        } else {
            return $this->consultation;
        }
    }

    /**
     * @return ActiveQuery<Motion>
     */
    public function getMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    /**
     * @return ActiveQuery<Amendment>
     */
    public function getAmendment(): ActiveQuery
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    /**
     * @return ActiveQuery<ConsultationAgendaItem>
     */
    public function getAgendaItem(): ActiveQuery
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'agendaItemId']);
    }

    /**
     * @return ActiveQuery<VotingBlock>
     */
    public function getVotingBlock(): ActiveQuery
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId']);
    }

    public function rules(): array
    {
        return [
            [['consultationId', 'dateStarted'], 'required'],
            [['id', 'consultationId', 'motionId', 'amendmentId', 'agendaItemId', 'votingBlockId'], 'number'],
            [['freeText'], 'string'],
        ];
    }

    private ?DebateItemSettings $settingsObject = null;

    public function getSettings(): DebateItemSettings
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new DebateItemSettings($this->settings);
        }

        return $this->settingsObject;
    }

    public function setSettings(?DebateItemSettings $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public static function getCurrentForConsultation(Consultation $consultation): ?DebateItem
    {
        return self::find()
            ->where(['consultationId' => $consultation->id, 'dateStopped' => null])
            ->one();
    }

    /**
     * The debated motion, amendment or agenda item - or null for a free-text debate (and for a debate
     * whose target does not exist anymore at all).
     *
     * The item is returned regardless of whether anyone is allowed to see it: the relations are used
     * as a fallback because Consultation::getMotion() / getAmendment() hide deleted items, and the
     * history of what has been debated has to remain describable after a deletion. Everything that
     * shows the debated item to someone therefore has to consult isTargetVisible() first.
     */
    public function getDebateTarget(): Motion|Amendment|ConsultationAgendaItem|null
    {
        $consultation = $this->getMyConsultation();
        if ($this->motionId) {
            return $consultation->getMotion($this->motionId) ?? $this->motion;
        } elseif ($this->amendmentId) {
            return $consultation->getAmendment($this->amendmentId) ?? $this->amendment;
        } elseif ($this->agendaItemId) {
            return $consultation->getAgendaItem($this->agendaItemId) ?? $this->agendaItem;
        } else {
            return null;
        }
    }

    /**
     * Whether the debated item may be shown to everyone. A motion or amendment can become invisible
     * while it is being debated - by being deleted, unpublished, or moved back to screening - and
     * what nobody may see cannot be presented as the currently debated item either. The debate is
     * normally ended when that happens (IMotion::afterSave()); this is the check that guarantees
     * nothing is leaked even if the item became invisible in some other way.
     */
    public function isTargetVisible(): bool
    {
        if ($this->freeText !== null) {
            return true;
        }

        $target = $this->getDebateTarget();
        if ($target === null) {
            return false;
        }
        if ($target instanceof ConsultationAgendaItem) {
            return true;
        }

        return $target->isVisible() && !$target->isDeleted();
    }
}
