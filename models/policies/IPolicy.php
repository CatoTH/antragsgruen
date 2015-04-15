<?php

namespace app\models\policies;

use app\models\db\Consultation;
use app\models\exceptions\Internal;

abstract class IPolicy
{
    const POLICY_ADMINS    = "admins";
    const POLICY_ALL       = "all";
    const POLICY_LOGGED_IN = "loggedin";
    const POLICY_NOBODY    = "nobody";

    /**
     * @return IPolicy[]
     */
    public static function getPolicies()
    {
        return [
            static::POLICY_ADMINS    => Admins::class,
            static::POLICY_ALL       => All::class,
            static::POLICY_LOGGED_IN => LoggedIn::class,
            static::POLICY_NOBODY    => Nobody::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function getPolicyNames()
    {
        $names = [];
        foreach (static::getPolicies() as $key => $pol) {
            $names[$key] = $pol::getPolicyName();
        }
        return $names;
    }

    /** @var Consultation */
    protected $consultation;

    /**
     * @param Consultation $consultation
     */
    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }


    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getPolicyID()
    {
        return "";
    }

    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getPolicyName()
    {
        return "";
    }

    /**
     * @static
     * @abstract
     * @return bool
     */
    abstract public function checkCurUserHeuristically();

    /**
     * @abstract
     * @return string
     */
    abstract public function getOnCreateDescription();

    /**
     * @return bool
     */
    public function checkHeuristicallyAssumeLoggedIn()
    {
        return $this->checkCurUserHeuristically();
    }


    /**
     * @return bool
     */
    abstract public function checkMotionSubmit();

    /**
     * @return bool
     */
    public function checkAmendmentSubmit()
    {
        return $this->checkMotionSubmit();
    }

    /**
     * @return bool
     */
    public function checkCommentSubmit()
    {
        return $this->checkMotionSubmit();
    }

    /**
     * @return bool
     */
    public function checkSupportSubmit()
    {
        return $this->checkMotionSubmit();
    }

    /**
     * @abstract
     * @return string
     */
    abstract public function getPermissionDeniedMotionMsg();

    /**
     * @abstract
     * @return string
     */
    abstract public function getPermissionDeniedAmendmentMsg();

    /**
     * @abstract
     * @return string
     */
    abstract public function getPermissionDeniedCommentMsg();

    /**
     * @abstract
     * @return string
     */
    abstract public function getPermissionDeniedSupportMsg();


    /**
     * @static
     * @param string $policyId
     * @param Consultation $consultation
     * @throws Internal
     * @return IPolicy
     */
    public static function getInstanceByID($policyId, Consultation $consultation)
    {
        /** @var IPolicy $polClass */
        foreach (static::getPolicies() as $polId => $polClass) {
            if ($polId == $policyId) {
                return new $polClass($consultation);
            }
        }
        throw new Internal("Unbekannte Policy: " . $policyId);
    }
}
