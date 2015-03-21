<?php

namespace app\models\policies;

use app\models\wording\IWording;

class Admins extends IPolicy
{
    /**
     * @static
     * @return string
     */
    public static function getPolicyID()
    {
        return "admins";
    }

    /**
     * @static
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(IWording $wording)
    {
        return "Admins";
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
        return "Admins";
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMsg(IWording $wording)
    {
        return "Nur Admins dürfen.";
    }

    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return ($this->consultation->isAdminCurUser());
    }

    /**
     * @return bool
     */
    public function checkAmendmentSubmit()
    {
        return ($this->consultation->isAdminCurUser());
    }
}
