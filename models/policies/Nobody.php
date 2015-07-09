<?php

namespace app\models\policies;

class Nobody extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 0;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return 'Niemand';
    }

    /**
     * @static
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurUserHeuristically($allowAdmins = true)
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
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        return 'Momentan kann niemand Anträge stellen.';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        return 'Momentan kann niemand Änderungsanträge stellen.';
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
        return 'Momentan kann niemand Kommentare schreiben.';
    }

    /**
     * @param bool $allowAdmin
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkMotionSubmit($allowAdmin = true)
    {
        return false;
    }
}
