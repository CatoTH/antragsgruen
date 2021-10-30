<?php

declare(strict_types=1);

namespace app\models\majorityType;

use app\models\db\{IMotion, Vote};

class SimpleMajority extends IMajorityType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'majority_simple');
    }

    public static function getDescription(): string
    {
        return \Yii::t('voting', 'majority_simple_h');
    }

    public static function getID(): int
    {
        return IMajorityType::MAJORITY_TYPE_SIMPLE;
    }

    public function calculateResult(array $votes): int
    {
        $yes = 0;
        $no = 0;
        foreach ($votes as $vote) {
            if ($vote->vote === Vote::VOTE_YES) {
                $yes++;
            }
            if ($vote->vote === Vote::VOTE_NO) {
                $no++;
            }
        }

        if ($yes > $no) {
            return IMotion::STATUS_ACCEPTED;
        } else {
            return IMotion::STATUS_REJECTED;
        }
    }
}
