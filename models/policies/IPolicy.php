<?php

namespace app\models\policies;

use app\components\UrlHelper;
use app\models\db\{ConsultationMotionType, User};
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

abstract class IPolicy
{
    const POLICY_NOBODY       = 0;
    const POLICY_ALL          = 1;
    const POLICY_LOGGED_IN    = 2;
    const POLICY_ADMINS       = 3;
    const POLICY_GRUENES_NETZ = 4;
    const POLICY_ORGANIZATION = 5;

    /**
     * @return IPolicy[]
     */
    public static function getPolicies(): array
    {
        $policies = [
            static::POLICY_ADMINS    => Admins::class,
            static::POLICY_ALL       => All::class,
            static::POLICY_LOGGED_IN => LoggedIn::class,
            static::POLICY_NOBODY    => Nobody::class,
        ];

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->isSamlActive()) {
            $policies[static::POLICY_GRUENES_NETZ] = GruenesNetz::class;
        }

        $site = UrlHelper::getCurrentSite();
        if ($site) {
            foreach ($site->getBehaviorClass()->getCustomPolicies() as $policy) {
                $policies[$policy::getPolicyID()] = $policy;
            }
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

    public function __construct(ConsultationMotionType $motionType)
    {
        $this->motionType = $motionType;
    }


    public static function getPolicyID(): int
    {
        return -1;
    }

    public static function getPolicyName(): string
    {
        return '';
    }

    abstract public function getOnCreateDescription(): string;

    abstract public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool;

    protected function checkCurrUserWithDeadline(string $deadlineType, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (!$this->motionType->isInDeadline($deadlineType)) {
            $consultation = $this->motionType->getConsultation();
            if (!User::havePrivilege($consultation, User::PRIVILEGE_ANY) || !$allowAdmins) {
                return false;
            }
        }
        return $this->checkCurrUser($allowAdmins, $assumeLoggedIn);
    }

    public function checkCurrUserMotion(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        $deadlineType = ConsultationMotionType::DEADLINE_MOTIONS;
        return $this->checkCurrUserWithDeadline($deadlineType, $allowAdmins, $assumeLoggedIn);
    }

    public function checkCurrUserAmendment(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        $deadlineType = ConsultationMotionType::DEADLINE_AMENDMENTS;
        return $this->checkCurrUserWithDeadline($deadlineType, $allowAdmins, $assumeLoggedIn);
    }

    public function checkCurrUserComment(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        return $this->checkCurrUserWithDeadline($deadlineType, $allowAdmins, $assumeLoggedIn);
    }

    abstract public function getPermissionDeniedMotionMsg(): string;

    abstract public function getPermissionDeniedAmendmentMsg(): string;

    abstract public function getPermissionDeniedCommentMsg(): string;

    abstract public function getPermissionDeniedSupportMsg(): string;


    public static function getInstanceByID(int $policyId, ConsultationMotionType $motionType): IPolicy
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
