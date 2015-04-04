<?php

namespace app\models\policies;

use app\models\wording\IWording;

class LoggedIn extends IPolicy
{
    /**
     * @static
     * @return string
     */
    public static function getPolicyID()
    {
        return "loggedin";
    }

    /**
     * @static
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(IWording $wording)
    {
        return "Eingeloggte";
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
        return 'Eingeloggte';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMotionMsg(IWording $wording)
    {
        return 'Du musst dich einloggen, um Anträge stellen zu können.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedAmendmentMsg(IWording $wording)
    {
        return 'Du musst dich einloggen, um Änderungsanträge stellen zu können.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedSupportMsg(IWording $wording)
    {
        return 'Du musst dich einloggen, um Anträge unterstützen zu können.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedCommentMsg(IWording $wording)
    {
        return 'Du musst dich einloggen, um Kommentare schreiben zu können.';
    }


    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return (!\Yii::$app->user->isGuest);
    }
}
