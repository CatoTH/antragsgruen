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
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurUserHeuristically($allowAdmins = true)
    {
        return (User::currentUserHasPrivilege($this->motionType->consultation, User::PRIVILEGE_ANY));
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
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkMotionSubmit($allowAdmins = true)
    {
        return User::currentUserHasPrivilege($this->motionType->consultation, User::PRIVILEGE_SCREENING);
    }
}
