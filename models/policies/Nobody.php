<?php

namespace app\models\policies;

use app\models\wording\Wording;

class Nobody extends IPolicy
{
    /**
     * @static
     * @return string
     */
    public static function getPolicyID()
    {
        return "nobody";
    }

    /**
     * @static
     * @param Wording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(Wording $wording)
    {
        return "Niemand";
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
        return "Niemand";
    }

    /**
     * @param Wording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMsg(Wording $wording)
    {
        return "Das Anlegen ist nicht erlaubt.";
    }

    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function checkAmendmentSubmit()
    {
        return false;
    }
}
