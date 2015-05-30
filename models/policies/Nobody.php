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
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return false;
    }
}
