<?php

declare(strict_types=1);

namespace app\models\votings;

use app\models\db\IMotion;

class AnswerTemplates
{
    const TEMPLATE_YES_NO_ABSTENTION = 0;
    const TEMPLATE_YES_NO = 1;
    const TEMPLATE_PRESENT = 2;

    /**
     * @return Answer[]
     */
    public static function fromVotingBlockData(?string $specification): array
    {
        if (empty($specification)) {
            return static::getCollectionYesNoAbstention();
        }
        $spec = json_decode($specification, true);
        if (!isset($spec['template'])) {
            return static::getCollectionYesNoAbstention();
        }
        switch ($spec['template']) {
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
        $answer = new Answer();
        $answer->id = 'present';
        $answer->title = \Yii::t('voting', 'vote_present');
        $answer->statusId = null;

        return [$answer];
    }

    private static function getYes(): Answer
    {
        $answer = new Answer();
        $answer->id = 'yes';
        $answer->title = \Yii::t('voting', 'vote_yes');
        $answer->statusId = IMotion::STATUS_ACCEPTED;
        return $answer;
    }

    private static function getNo(): Answer
    {
        $answer = new Answer();
        $answer->id = 'no';
        $answer->title = \Yii::t('voting', 'vote_no');
        $answer->statusId = IMotion::STATUS_REJECTED;
        return $answer;
    }

    private static function getAbstention(): Answer
    {
        $answer = new Answer();
        $answer->id = 'abstention';
        $answer->title = \Yii::t('voting', 'vote_abstention');
        $answer->statusId = null;
        return $answer;
    }
}
