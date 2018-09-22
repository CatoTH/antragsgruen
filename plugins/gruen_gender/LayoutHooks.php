<?php

namespace app\plugins\gruen_gender;

use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\layoutHooks\HooksAdapter;

class LayoutHooks extends HooksAdapter
{
    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConsultationMotionLineContent($before, Motion $motion)
    {
        return $before;
    }

    /**
     * @param string $before
     * @param Amendment $amendment
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConsultationAmendmentLineContent($before, Amendment $amendment)
    {
        return $before;
    }
}
