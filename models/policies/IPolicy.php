<?php

namespace app\models\policies;

use app\models\db\ConsultationMotionType;
use app\models\exceptions\Internal;

abstract class IPolicy
{
    const POLICY_NOBODY    = 0;
    const POLICY_ALL       = 1;
    const POLICY_LOGGED_IN = 2;
    const POLICY_ADMINS    = 3;

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

    /** @var ConsultationMotionType */
    protected $motionType;

    /**
     * @param ConsultationMotionType $motionType
     */
    public function __construct(ConsultationMotionType $motionType)
    {
        $this->motionType = $motionType;
    }


    /**
     * @static
     * @abstract
     * @return int
     */
    public static function getPolicyID()
    {
        return -1;
    }

    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getPolicyName()
    {
        return '';
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
     * @param ConsultationMotionType $motionType
     * @throws Internal
     * @return IPolicy
     */
    public static function getInstanceByID($policyId, ConsultationMotionType $motionType)
    {
        /** @var IPolicy $polClass */
        foreach (static::getPolicies() as $polId => $polClass) {
            if ($polId == $policyId) {
                return new $polClass($motionType);
            }
        }
        throw new Internal('Unknown Policy: ' . $policyId);
    }
}
