<?php

namespace app\models\policies;

use app\models\db\User;

class Wurzelwerk extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 4;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return 'Wurzelwerk-NutzerInnen';
    }

    /**
     * @static
     * @param bool $allowAdmins
     * @return bool
     */
    public function checkCurUserHeuristically($allowAdmins = true)
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return true;
        }
        if ($user->isWurzelwerkUser()) {
            return true;
        }
        if ($user->hasPrivilege($this->motionType->consultation, User::PRIVILEGE_ANY)) {
            return $allowAdmins;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return 'Wurzelwerk-NutzerInnen';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        return 'Nur Wurzelwerk-NutzerInnen dürfen Anträge anlegen.';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        return 'Nur Wurzelwerk-NutzerInnen dürfen Änderungsanträge anlegen.';
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
        return 'Nur Wurzelwerk-NutzerInnen dürfen kommentieren';
    }

    /**
     * @param bool $allowAdmins
     * @return bool
     */
    public function checkMotionSubmit($allowAdmins = true)
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        if ($allowAdmins && $user->hasPrivilege($this->motionType->consultation, User::PRIVILEGE_ANY)) {
            return true;
        }
        return $user->isWurzelwerkUser();
    }
}
