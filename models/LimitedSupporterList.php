<?php

namespace app\models;

use app\models\db\{IMotion, ISupporter};

class LimitedSupporterList
{
    /** @var ISupporter[] */
    public array $supporters = [];

    public int $truncatedNum = 0;
    public int $nonPublicNum = 0;

    public static function createFromIMotion(IMotion $iMotion): self
    {
        $obj   = new LimitedSupporterList();
        $limit = $iMotion->getMyMotionType()->getMotionSupportTypeClass()->getSettingsObj()->maxPdfSupporters;
        foreach ($iMotion->getSupporters(true) as $supporter) {
            if ($supporter->isNonPublic()) {
                $obj->nonPublicNum++;
            } elseif ($limit && count($obj->supporters) >= $limit) {
                $obj->truncatedNum++;
            } else {
                $obj->supporters[] = $supporter;
            }
        }

        return $obj;
    }

    public function truncatedToString(string $limiter = ''): string
    {
        $skipped = $this->truncatedNum + $this->nonPublicNum;
        if ($skipped === 0) {
            return '';
        } elseif ($skipped === 1) {
            return trim($limiter) . ' ' . \Yii::t('export', 'truncated_supp_1');
        } else {
            return trim($limiter) . ' ' . str_replace('%NUM%', (string)$skipped, \Yii::t('export', 'truncated_supp_x'));
        }
    }
}
