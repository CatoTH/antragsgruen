<?php

namespace app\models\policies;

use app\models\wording\IWording;

class Nobody extends IPolicy
{
    /**
     * @static
     * @return string
     */
    public static function getPolicyID()
    {
        return 'nobody';
    }

    /**
     * @static
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(IWording $wording)
    {
        return 'Niemand';
    }

    /**
     * @static
     * @return bool
     */
    public function checkCurUserHeuristically()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return 'Niemand';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMotionMsg(IWording $wording)
    {
        return 'Momentan kann niemand Anträge stellen.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedAmendmentMsg(IWording $wording)
    {
        return 'Momentan kann niemand Änderungsanträge stellen.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedSupportMsg(IWording $wording)
    {
        return 'Momentan kann niemand Anträge unterstützen.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedCommentMsg(IWording $wording)
    {
        return 'Momentan kann niemand Kommentare schreiben.';
    }

    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return false;
    }
}
