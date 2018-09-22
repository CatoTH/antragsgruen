<?php

namespace app\models\layoutHooks;

use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\db\Site;

class Layout
{
    /** @var Hooks[] */
    private static $hooks = [];

    /**
     * @param Hooks $hook
     */
    public static function addHook(Hooks $hook)
    {
        if (!in_array($hook, static::$hooks)) {
            static::$hooks[] = $hook;
        }
    }

    /**
     * @param string $name
     * @param mixed[] $args
     * @param mixed $initValue
     * @return mixed
     */
    private static function callHook($name, $args = [], $initValue = '')
    {
        $out = $initValue;
        foreach (static::$hooks as $hook) {
            $callArgs = array_merge([$out], $args);
            $out      = call_user_func_array([$hook, $name], $callArgs);
        }
        return $out;
    }

    /**
     * @return string
     */
    public static function beforePage()
    {
        return static::callHook('beforePage');
    }

    /**
     * @return string
     */
    public static function beginPage()
    {
        return static::callHook('beginPage');
    }

    /**
     * @return string
     */
    public static function favicons()
    {
        return static::callHook('favicons');
    }

    /**
     * @return string
     */
    public static function logoRow()
    {
        return static::callHook('logoRow');
    }

    /**
     * @return string
     */
    public static function beforeContent()
    {
        return static::callHook('beforeContent');
    }

    /**
     * @return string
     */
    public static function afterContent()
    {
        return static::callHook('afterContent');
    }

    /**
     * @return string
     */
    public static function beginContent()
    {
        return static::callHook('beginContent');
    }

    /**
     * @return string
     */
    public static function endPage()
    {
        return static::callHook('endPage');
    }

    /**
     * @return string
     */
    public static function renderSidebar()
    {
        return static::callHook('renderSidebar');
    }

    /**
     * @return string
     */
    public static function getSearchForm()
    {
        return static::callHook('getSearchForm');
    }

    /**
     * @return string
     */
    public static function getAntragsgruenAd()
    {
        return static::callHook('getAntragsgruenAd');
    }

    /**
     * @param ConsultationMotionType[] $motionTypes
     * @return string
     */
    public static function setSidebarCreateMotionButton($motionTypes)
    {
        return static::callHook('setSidebarCreateMotionButton', [$motionTypes]);
    }

    /**
     * @return string
     */
    public static function getStdNavbarHeader()
    {
        return static::callHook('getStdNavbarHeader');
    }

    /**
     * @return string
     */
    public static function footerLine()
    {
        return static::callHook('footerLine');
    }

    /**
     * @return string
     */
    public static function breadcrumbs()
    {
        return static::callHook('breadcrumbs');
    }

    /**
     * @param Motion $motion
     * @return string
     */
    public static function beforeMotionView(Motion $motion)
    {
        return static::callHook('beforeMotionView', [$motion]);
    }

    /**
     * @param Motion $motion
     * @return string
     */
    public static function afterMotionView(Motion $motion)
    {
        return static::callHook('afterMotionView', [$motion]);
    }

    /**
     * @param array $motionData
     * @param Motion $motion
     * @return array
     */
    public static function getMotionViewData($motionData, Motion $motion)
    {
        return static::callHook('getMotionViewData', [$motion], $motionData);
    }

    /**
     * @param string $origStatus
     * @param Motion $motion
     * @return string
     */
    public static function getFormattedMotionStatus($origStatus, Motion $motion)
    {
        return static::callHook('getFormattedMotionStatus', [$motion], $origStatus);
    }

    /**
     * @param string $origStatus
     * @param Amendment $amendment
     * @return string
     */
    public static function getFormattedAmendmentStatus($origStatus, Amendment $amendment)
    {
        return static::callHook('getFormattedAmendmentStatus', [$amendment], $origStatus);
    }

    /**
     * @param string $origLine
     * @param Motion $motion
     * @return string
     */
    public static function getConsultationMotionLineContent($origLine, Motion $motion)
    {
        return static::callHook('getConsultationMotionLineContent', [$motion], $origLine);
    }

    /**
     * @param string $origLine
     * @param Amendment $amendment
     * @return string
     */
    public static function getConsultationAmendmentLineContent($origLine, Amendment $amendment)
    {
        return static::callHook('getConsultationAmendmentLineContent', [$amendment], $origLine);
    }

    /**
     * @param Consultation $consultation
     * @return string
     */
    public static function getAdminIndexHint(Consultation $consultation)
    {
        return static::callHook('getAdminIndexHint', [$consultation]);
    }

    /**
     * @param Site $site
     * @return string[]
     */
    public static function getSitewidePublicWarnings(Site $site)
    {
        return static::callHook('getSitewidePublicWarnings', [$site], []);
    }
}
