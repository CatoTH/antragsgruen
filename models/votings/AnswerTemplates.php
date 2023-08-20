<?php

declare(strict_types=1);

namespace app\models\votings;

use app\models\db\IMotion;

final class AnswerTemplates
{
    public const TEMPLATE_YES_NO_ABSTENTION = 0;
    public const TEMPLATE_YES_NO = 1;
    public const TEMPLATE_PRESENT = 2;
    public const TEMPLATE_YES = 3;

    public const VOTE_ABSTENTION = 0;
    public const VOTE_YES = 1;
    public const VOTE_NO = -1;
    public const VOTE_PRESENT = 2;

    /**
     * @return Answer[]
     */
    public static function fromVotingBlockData(int $templateId): array
    {
        return match ($templateId) {
            self::TEMPLATE_PRESENT => self::getCollectionPresent(),
            self::TEMPLATE_YES_NO => self::getCollectionYesNo(),
            self::TEMPLATE_YES => self::getCollectionYes(),
            default => self::getCollectionYesNoAbstention(),
        };
    }

    /**
     * @return Answer[]
     */
    private static function getCollectionYesNoAbstention(): array
    {
        return [
            self::getYes(),
            self::getNo(),
            self::getAbstention(),
        ];
    }

    /**
     * @return Answer[]
     */
    private static function getCollectionYesNo(): array
    {
        return [
            self::getYes(),
            self::getNo(),
        ];
    }

    /**
     * @return Answer[]
     */
    private static function getCollectionYes(): array
    {
        return [
            self::getYes(),
        ];
    }

    /**
     * @return Answer[]
     */
    private static function getCollectionPresent(): array
    {
        return [
            self::getPresent()
        ];
    }

    public static function getYes(): Answer
    {
        $answer = new Answer();
        $answer->dbId = self::VOTE_YES;
        $answer->apiId = 'yes';
        $answer->title = \Yii::t('voting', 'vote_yes');
        $answer->statusId = IMotion::STATUS_ACCEPTED;
        return $answer;
    }

    public static function getNo(): Answer
    {
        $answer = new Answer();
        $answer->dbId = self::VOTE_NO;
        $answer->apiId = 'no';
        $answer->title = \Yii::t('voting', 'vote_no');
        $answer->statusId = IMotion::STATUS_REJECTED;
        return $answer;
    }

    public static function getAbstention(): Answer
    {
        $answer = new Answer();
        $answer->dbId = self::VOTE_ABSTENTION;
        $answer->apiId = 'abstention';
        $answer->title = \Yii::t('voting', 'vote_abstention');
        $answer->statusId = null;
        return $answer;
    }

    public static function getPresent(): Answer {
        $answer = new Answer();
        $answer->dbId = self::VOTE_PRESENT;
        $answer->apiId = 'present';
        $answer->title = \Yii::t('voting', 'vote_present');
        $answer->statusId = null;

        return $answer;
    }
}
