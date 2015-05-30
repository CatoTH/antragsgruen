<?php

namespace app\models\policies;

use app\models\db\User;

class Admins extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 3;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
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
        return 'Admins';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        return 'Nur Admins dürfen Anträge anlegen.';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        return 'Nur Admins dürfen Änderungsanträge anlegen.';
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
        return 'Nur Admins dürfen kommentieren';
    }

    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return User::currentUserHasPrivilege($this->motionType->consultation, User::PRIVILEGE_SCREENING);
    }
}
