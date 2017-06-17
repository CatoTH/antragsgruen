<?php

namespace app\models\policies;

use app\models\db\ConsultationMotionType;
use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

abstract class IPolicy
{
    const POLICY_NOBODY     = 0;
    const POLICY_ALL        = 1;
    const POLICY_LOGGED_IN  = 2;
    const POLICY_ADMINS     = 3;
    const POLICY_WURZELWERK = 4;

    /**
     * @return IPolicy[]
     */
    public static function getPolicies()
    {
        $policies = [
            static::POLICY_ADMINS    => Admins::class,
            static::POLICY_ALL       => All::class,
            static::POLICY_LOGGED_IN => LoggedIn::class,
            static::POLICY_NOBODY    => Nobody::class,
        ];
        /** @var AntragsgruenApp $params */
        $params = \yii::$app->params;
        if ($params->hasWurzelwerk || $params->isSamlActive()) {
            $policies[static::POLICY_WURZELWERK] = Wurzelwerk::class;
        }
        return $policies;
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
     * @abstract
     * @return string
     */
    abstract public function getOnCreateDescription();

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     */
    abstract public function checkCurrUser($allowAdmins = true, $assumeLoggedIn = false);

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     */
    public function checkCurrUserMotion($allowAdmins = true, $assumeLoggedIn = false)
    {

        if ($this->motionType->motionDeadlineIsOver()) {
            $consultation = $this->motionType->getConsultation();
            if (!User::currentUserHasPrivilege($consultation, User::PRIVILEGE_ANY) || !$allowAdmins) {
                return false;
            }
        }
        return $this->checkCurrUser($allowAdmins, $assumeLoggedIn);
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
