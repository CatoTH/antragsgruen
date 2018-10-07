<?php

namespace app\plugins\gruen_gender;

use app\models\db\Amendment;
use app\models\db\ISupporter;
use app\models\db\Motion;
use app\models\layoutHooks\Hooks;

/**
 * HINT: Requires the gruen_ci-plugin for styling
 *
 * Class LayoutHooks
 * @package app\plugins\gruen_gender
 */
class LayoutHooks extends Hooks
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
     * @param string $before
     * @return string
     */
    private function formatWomenQuotaCol($quota, $before)
    {
        $women = '<span class="womenQuota">(Frauenanteil: ';
        /*
        $women .= '<span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="top" ' .
            'title="Frauenanteil"></span>';
        */
        $women .= round($quota * 100) . '%';
        $women .= ')</span>';

        $before = preg_replace_callback(
            '/(?<pre>class=[\'"]info[\'"].*)(?<post><\/(p|span)>)/siuU',
            function ($matches) use ($women) {
                return $matches['pre'] . $women . $matches['post'];
            },
            $before
        );

        return $before;
    }

    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     */
    public function getConsultationMotionLineContent($before, Motion $motion)
    {
        $collectionPhase = $motion->motionType->getMotionSupportTypeClass()->collectSupportersBeforePublication();
        if (!$motion->isInitiatedByOrganization() && $collectionPhase) {
            $persons = array_merge($motion->getInitiators(), $motion->getSupporters());
            $quota   = $this->getWomensQuota($persons);
            $before  = $this->formatWomenQuotaCol($quota, $before);
        }

        return $before;
    }

    /**
     * @param string $before
     * @param Amendment $amendment
     * @return string
     */
    public function getConsultationAmendmentLineContent($before, Amendment $amendment)
    {
        $collectionPhase = $amendment->getMyMotionType()->getAmendmentSupportTypeClass()
            ->collectSupportersBeforePublication();
        if (!$amendment->isInitiatedByOrganization() && $collectionPhase) {
            $persons = array_merge($amendment->getInitiators(), $amendment->getSupporters());
            $quota   = $this->getWomensQuota($persons);
            $before  = $this->formatWomenQuotaCol($quota, $before);
        }

        return $before;
    }

    /**
     * @param string $before
     * @param ISupporter $supporter
     * @return string
     */
    public function getMotionDetailsInitiatorName($before, ISupporter $supporter)
    {
        $imotion         = $supporter->getIMotion();
        $collectionPhase = $imotion->getMyMotionType()->getAmendmentSupportTypeClass()
            ->collectSupportersBeforePublication();
        if (!$imotion->isInitiatedByOrganization() && $collectionPhase) {
            $persons = array_merge($imotion->getInitiators(), $imotion->getSupporters());
            $quota   = $this->getWomensQuota($persons);
            $before .= '<div class="moreSupporters">';

            $num = count($imotion->getSupporters());
            $before .= '<a href="#supporters">';
            if ($num === 1) {
                $before .= 'und 1 weitere Antragsteller*in';
            } else {
                $before .= 'und ' . $num . ' weitere Antragsteller*innen';
            }
            $before .= '</a> ';

            $before .= '<span class="womenQuota">(Frauenanteil: ';
            $before .= round($quota * 100) . '%';
            $before .= ')</span></div>';

            $before = $this->formatWomenQuotaCol($quota, $before);
        }
        return $before;
    }
}
