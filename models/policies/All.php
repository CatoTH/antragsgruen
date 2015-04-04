<?php

namespace app\models\policies;

use app\models\wording\IWording;

class All extends IPolicy
{
    /**
     * @static
     * @return string
     */
    public static function getPolicyID()
    {
        return "all";
    }

    /**
     * @static
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(IWording $wording)
    {
        return "Alle";
    }

    /**
     * @static
     * @return bool
     */
    public function checkCurUserHeuristically()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return 'Alle';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMotionMsg(IWording $wording)
    {
        return '';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedAmendmentMsg(IWording $wording)
    {
        return '';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedSupportMsg(IWording $wording)
    {
        return '';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedCommentMsg(IWording $wording)
    {
        return '';
    }

    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function checkSupportSubmit()
    {
        return false; // Only logged in users can support motions
    }
}
