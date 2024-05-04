<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property int|null $votingStatus
 * @property int|null $votingBlockId
 * @property int $consultationId
 * @property string|null $votingData
 * @property VotingBlock|null $votingBlock
 * @property Vote[] $votes
 */
class VotingQuestion extends ActiveRecord implements IVotingItem
{
    use VotingItemTrait;

    private const TITLE_GENERAL_ABSTENTION = '{GENERAL ABSTENTION}';

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'votingQuestion';
    }

    public function getMyConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getVotingQuestion($this->id)) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    public function getVotingBlock(): ActiveQuery
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    public function getVotes(): ActiveQuery
    {
        return $this->hasMany(Vote::class, ['questionId' => 'id']);
    }

    public function getAgendaApiBaseObject(): array
    {
        return [
            'type' => 'question',
            'id' => $this->id,
            'prefix' => '',
            'title_with_prefix' => $this->title,
            'url_json' => null,
            'url_html' => null,
            'initiators_html' => null,
            'procedure' => null,
            'item_group_same_vote' => $this->getVotingData()->itemGroupSameVote,
            'item_group_name' => $this->getVotingData()->itemGroupName,
            'voting_status' => $this->votingStatus,
        ];
    }

    public function setVotingResult(int $votingResult): void
    {
        $this->votingStatus = $votingResult;
        if ($votingResult === IMotion::STATUS_ACCEPTED) {
            ConsultationLog::log($this->getMyConsultation(), null, ConsultationLog::VOTING_QUESTION_ACCEPTED, $this->id);
        }
        if ($votingResult === IMotion::STATUS_REJECTED) {
            ConsultationLog::log($this->getMyConsultation(), null, ConsultationLog::VOTING_QUESTION_REJECTED, $this->id);
        }
    }

    public static function createGeneralAbstentionItem(VotingBlock $votingBlock): VotingQuestion
    {
        $question = new VotingQuestion();
        $question->title = self::TITLE_GENERAL_ABSTENTION;
        $question->consultationId = $votingBlock->consultationId;
        $question->votingBlockId = $votingBlock->id;
        $question->save();
        return $question;
    }

    public function isGeneralAbstention(): bool
    {
        return $this->title === self::TITLE_GENERAL_ABSTENTION;
    }
}
