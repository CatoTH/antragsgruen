<?php

declare(strict_types=1);

namespace app\models\majorityType;

use app\models\db\{IMotion, Vote};

class TwoThirdsMajority extends IMajorityType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'majority_twothirds');
    }

    public static function getDescription(): string
    {
        return \Yii::t('voting', 'majority_twothirds_h');
    }

    public static function getID(): int
    {
        return SimpleMajority::MAJORITY_TYPE_TWO_THIRD;
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

        $absoluteNumer = $yes + $no;

        if ($yes >= ($absoluteNumer * 2 / 3)) {
            return IMotion::STATUS_ACCEPTED;
        } else {
            return IMotion::STATUS_REJECTED;
        }
    }
}
