<?php

namespace app\plugins\gruen_gender;

use app\models\db\{Amendment, ISupporter, Motion};
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
            if ($supporter->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER) === 'female') {
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

    public function getConsultationMotionLineContent(string $before, Motion $motion): string
    {
        $motionType = $motion->getMyMotionType();
        $collectionPhase = $motionType->getMotionSupportTypeClass()->collectSupportersBeforePublication();
        if (!$motion->isInitiatedByOrganization() && $collectionPhase) {
            $quota = $motion->getCacheItem('supporters.womanQuota');
            if ($quota === null) {
                $persons = array_merge($motion->getInitiators(), $motion->getSupporters());
                $quota   = $this->getWomensQuota($persons);
                $motion->setCacheItem('supporters.womanQuota', $quota);
            }

            $before  = $this->formatWomenQuotaCol($quota, $before);
        }

        return $before;
    }

    public function getConsultationAmendmentLineContent(string $before, Amendment $amendment): string
    {
        $collectionPhase = $amendment->getMyMotionType()->getAmendmentSupportTypeClass()
            ->collectSupportersBeforePublication();
        if (!$amendment->isInitiatedByOrganization() && $collectionPhase) {
            $quota = $amendment->getCacheItem('supporters.womanQuota');
            if ($quota === null) {
                $persons = array_merge($amendment->getInitiators(), $amendment->getSupporters());
                $quota   = $this->getWomensQuota($persons);
                $amendment->setCacheItem('supporters.womanQuota', $quota);
            }

            $before  = $this->formatWomenQuotaCol($quota, $before);
        }

        return $before;
    }

    public function getMotionDetailsInitiatorName(string $before, ISupporter $supporter): string
    {
        $imotion         = $supporter->getIMotion();
        if (is_a($imotion, Amendment::class)) {
            $collectionPhase = $imotion->getMyMotionType()->getAmendmentSupportTypeClass()->collectSupportersBeforePublication();
        } else {
            $collectionPhase = $imotion->getMyMotionType()->getMotionSupportTypeClass()->collectSupportersBeforePublication();
        }
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
