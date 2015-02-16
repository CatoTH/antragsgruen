<?php

namespace app\models\policies;

use app\models\db\Consultation;
use app\models\exceptions\Internal;
use app\models\wording\Wording;

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
        // @TODO
        return [
            'admins'   => Admins::class,
            'all'      => All::class,
            'loggedin' => LoggedIn::class,
            'nobody'   => Nobody::class,
        ];
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
     * @param Wording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(Wording $wording)
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
     * @param Wording $wording
     * @return string
     */
    abstract public function getPermissionDeniedMsg(Wording $wording);


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
