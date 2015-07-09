<?php

namespace app\models\policies;

class All extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 1;
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
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurUserHeuristically($allowAdmins = true)
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
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkMotionSubmit($allowAdmins = true)
    {
        return true;
    }

    /**
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkSupportSubmit($allowAdmins = true)
    {
        return false; // Only logged in users can support motions
    }
}
