<?php

namespace app\models\policies;

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
     * @return string
     */
    public static function getPolicyName()
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
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedSupportMsg()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedCommentMsg()
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
