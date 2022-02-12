<?php

declare(strict_types=1);

namespace app\models\votings;

use app\models\db\IMotion;

final class AnswerTemplates
{
    const TEMPLATE_YES_NO_ABSTENTION = 0;
    const TEMPLATE_YES_NO = 1;
    const TEMPLATE_PRESENT = 2;

    const VOTE_ABSTENTION = 0;
    const VOTE_YES = 1;
    const VOTE_NO = -1;
    const VOTE_PRESENT = 2;

    /**
     * @return Answer[]
     */
    public static function fromVotingBlockData(int $templateId): array
    {
        switch ($templateId) {
            case static::TEMPLATE_PRESENT:
                return static::getCollectionPresent();
            case static::TEMPLATE_YES_NO:
                return static::getCollectionYesNo();
            case static::TEMPLATE_YES_NO_ABSTENTION:
            default:
                return static::getCollectionYesNoAbstention();
        }
    }

    /**
     * @return Answer[]
     */
    private static function getCollectionYesNoAbstention(): array
    {
        return [
            static::getYes(),
            static::getNo(),
            static::getAbstention(),
        ];
    }

    /**
     * @return Answer[]
     */
    private static function getCollectionYesNo(): array
    {
        return [
            static::getYes(),
            static::getNo(),
        ];
    }

    /**
     * @return Answer[]
     */
    private static function getCollectionPresent(): array
    {
        return [
            static::getPresent()
        ];
    }

    public static function getYes(): Answer
    {
        $answer = new Answer();
        $answer->dbId = static::VOTE_YES;
        $answer->apiId = 'yes';
        $answer->title = \Yii::t('voting', 'vote_yes');
        $answer->statusId = IMotion::STATUS_ACCEPTED;
        return $answer;
    }

    public static function getNo(): Answer
    {
        $answer = new Answer();
        $answer->dbId = static::VOTE_NO;
        $answer->apiId = 'no';
        $answer->title = \Yii::t('voting', 'vote_no');
        $answer->statusId = IMotion::STATUS_REJECTED;
        return $answer;
    }

    public static function getAbstention(): Answer
    {
        $answer = new Answer();
        $answer->dbId = static::VOTE_ABSTENTION;
        $answer->apiId = 'abstention';
        $answer->title = \Yii::t('voting', 'vote_abstention');
        $answer->statusId = null;
        return $answer;
    }

    public static function getPresent(): Answer {
        $answer = new Answer();
        $answer->dbId = static::VOTE_PRESENT;
        $answer->apiId = 'present';
        $answer->title = \Yii::t('voting', 'vote_present');
        $answer->statusId = null;

        return $answer;
    }
}
