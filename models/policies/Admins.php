<?php

namespace app\models\policies;

use app\models\wording\Wording;

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
     * @param Wording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(Wording $wording)
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
     * @param Wording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMsg(Wording $wording)
    {
        return "Nur Admins dürfen.";
    }
}
