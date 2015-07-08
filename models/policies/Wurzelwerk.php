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
     * @return bool
     */
    public function checkMotionSubmit()
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->isWurzelwerkUser();
    }
}
