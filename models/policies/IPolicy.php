<?php

namespace app\models\policies;

use app\models\db\Consultation;
use app\models\exceptions\Internal;
use app\models\wording\IWording;

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
     * @param IWording $wording
     * @return string[]
     */
    public static function getPolicyNames($wording)
    {
        $names = [];
        foreach (static::getPolicies() as $key => $pol) {
            $names[$key] = $pol::getPolicyName($wording);
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
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(IWording $wording)
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
    abstract public function checkAmendmentSubmit();

    /**
     * @abstract
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    abstract public function getPermissionDeniedMsg(IWording $wording);

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
