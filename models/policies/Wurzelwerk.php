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
     * @param bool $assumeLoggedIn
     * @return bool
     */
    public function checkCurrUser($allowAdmins = true, $assumeLoggedIn = false)
    {
        $user = User::getCurrentUser();
        if (!$user) {
            if ($assumeLoggedIn) {
                return true;
            } else {
                return false;
            }
        }
        if ($allowAdmins && $user->hasPrivilege($this->motionType->consultation, User::PRIVILEGE_ANY)) {
            return true;
        }
        return $user->isWurzelwerkUser();
    }
}
