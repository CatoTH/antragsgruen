<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property int|null $votingStatus
 * @property int $votingBlockId
 * @property string|null $votingData
 * @property VotingBlock|null $votingBlock
 * @property Vote[] $votes
 */
class VotingQuestion extends ActiveRecord
{
    use VotingItemTrait;

    /**
     * @return string
     */
    public static function tableName()
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'votingQuestion';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotingBlock()
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotes()
    {
        return $this->hasMany(Vote::class, ['questionId' => 'id']);
    }
}
