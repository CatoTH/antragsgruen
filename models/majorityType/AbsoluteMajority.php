<?php

declare(strict_types=1);

namespace app\models\majorityType;

use app\models\db\{IMotion, Vote};

class AbsoluteMajority extends IMajorityType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'majority_absolute');
    }

    public static function getDescription(): string
    {
        return \Yii::t('voting', 'majority_absolute_h');
    }

    public static function getID(): int
    {
        return IMajorityType::MAJORITY_TYPE_ABSOLUTE;
    }

    public function calculateResult(array $votes): int
    {
        $yes = 0;
        $no = 0;
        $abstentions = 0;
        foreach ($votes as $vote) {
            if ($vote->vote === Vote::VOTE_YES) {
                $yes++;
            }
            if ($vote->vote === Vote::VOTE_NO) {
                $no++;
            }
            if ($vote->vote === Vote::VOTE_ABSTENTION) {
                $abstentions++;
            }
        }

        if ($yes > ($no + $abstentions)) {
            return IMotion::STATUS_ACCEPTED;
        } else {
            return IMotion::STATUS_REJECTED;
        }
    }
}
