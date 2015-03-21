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
        return "Alle";
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMsg(IWording $wording)
    {
        return "[kommt nicht vor]";
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
    public function checkAmendmentSubmit()
    {
        return true;
    }
}
