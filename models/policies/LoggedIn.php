<?php

namespace app\models\policies;

class LoggedIn extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 2;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return "Eingeloggte";
    }

    /**
     * @static
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurUserHeuristically($allowAdmins = true)
    {
        return true;
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return 'Eingeloggte';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        return 'Du musst dich einloggen, um Anträge stellen zu können.';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        return 'Du musst dich einloggen, um Änderungsanträge stellen zu können.';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedSupportMsg()
    {
        return 'Du musst dich einloggen, um Anträge unterstützen zu können.';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedCommentMsg()
    {
        return 'Du musst dich einloggen, um Kommentare schreiben zu können.';
    }


    /**
     * @param bool $allowAdmins
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkMotionSubmit($allowAdmins = true)
    {
        return (!\Yii::$app->user->isGuest);
    }
}
