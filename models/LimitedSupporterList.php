<?php

namespace app\models;

use app\models\db\IMotion;
use app\models\db\ISupporter;

class LimitedSupporterList
{
    /** @var ISupporter[] */
    public $supporters;

    /** @var int */
    public $truncatedNum;

    public static function createFromIMotion(IMotion $iMotion)
    {
        $obj   = new LimitedSupporterList();
        $limit = $iMotion->getMyMotionType()->getMotionSupportTypeClass()->getSettingsObj()->maxPdfSupporters;
        if ($limit === null) {
            $obj->supporters   = $iMotion->getSupporters();
            $obj->truncatedNum = 0;
        } else {
            $obj->supporters = [];
            $supporters      = $iMotion->getSupporters();
            for ($i = 0; $i < $limit && $i < count($supporters); $i++) {
                $obj->supporters[] = $supporters[$i];
            }
            if (count($supporters) > $limit) {
                $obj->truncatedNum = count($supporters) - $limit;
            } else {
                $obj->truncatedNum = 0;
            }
        }

        return $obj;
    }

    /**
     * @param string $limiter
     *
     * @return string
     */
    public function truncatedToString($limiter = '')
    {
        if ($this->truncatedNum === 0) {
            return '';
        } elseif ($this->truncatedNum === 1) {
            return trim($limiter) . ' ' . \Yii::t('export', 'truncated_supp_1');
        } else {
            return trim($limiter) . ' ' . str_replace('%NUM%', $this->truncatedNum, \Yii::t('export', 'truncated_supp_x'));
        }
    }
}
