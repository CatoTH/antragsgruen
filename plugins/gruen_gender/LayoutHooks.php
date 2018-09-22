<?php

namespace app\plugins\gruen_gender;

use app\models\db\Amendment;
use app\models\db\ISupporter;
use app\models\db\Motion;
use app\models\layoutHooks\HooksAdapter;

/**
 * HINT: Requires the gruen_ci-plugin for styling
 *
 * Class LayoutHooks
 * @package app\plugins\gruen_gender
 */
class LayoutHooks extends HooksAdapter
{
    /**
     * @param ISupporter[] $supporters
     * @return float
     */
    private function getWomensQuota($supporters)
    {
        if (count($supporters) === 0) {
            return 0;
        }

        $women = 0;
        foreach ($supporters as $supporter) {
            if ($supporter->getExtraDataEntry('gender') === 'female') {
                $women++;
            }
        }

        return ($women / count($supporters));
    }

    /**
     * @param float $quota
     * @return string
     */
    private function formatWomenQuotaCol($quota)
    {
        $women = '<p class="womenQuota">';
        $women .= '<span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="top" ' .
            'title="Frauenanteil"></span>';
        $women .= round($quota * 100) . '%';
        $women .= '</p>';
        return $women;
    }

    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConsultationMotionLineContent($before, Motion $motion)
    {
        if (!$motion->isInitiatedByOrganization()) {
            $persons = array_merge($motion->getInitiators(), $motion->getSupporters());
            $quota   = $this->getWomensQuota($persons);
            $before  = $before . $this->formatWomenQuotaCol($quota);
        }

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
        if (!$amendment->isInitiatedByOrganization()) {
            $persons = array_merge($amendment->getInitiators(), $amendment->getSupporters());
            $quota   = $this->getWomensQuota($persons);
            $before  = $before . $this->formatWomenQuotaCol($quota);
        }

        return $before;
    }
}
