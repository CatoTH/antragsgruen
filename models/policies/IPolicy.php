<?php

namespace app\models\policies;

use app\components\UrlHelper;
use app\models\settings\Privileges;
use app\models\db\{Consultation, ConsultationMotionType, IHasPolicies, User};
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

abstract class IPolicy
{
    public const POLICY_NOBODY = 0;
    public const POLICY_ALL = 1;
    public const POLICY_LOGGED_IN = 2;
    public const POLICY_USER_GROUPS = 6;
    public const POLICY_ADMINS = 3;
    public const POLICY_GRUENES_NETZ = 4;
    public const POLICY_ORGANIZATION = 5;

    /**
     * @return IPolicy[]|string[]
     */
    public static function getPolicies(): array
    {
        $policies = [
            self::POLICY_ADMINS => Admins::class,
            self::POLICY_ALL => All::class,
            self::POLICY_LOGGED_IN => LoggedIn::class,
            self::POLICY_USER_GROUPS => UserGroups::class,
            self::POLICY_NOBODY => Nobody::class,
        ];

        if (AntragsgruenApp::getInstance()->isSamlActive()) {
            $policies[self::POLICY_GRUENES_NETZ] = GruenesNetz::class;
        }

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            foreach ($plugin::getCustomPolicies() as $policy) {
                $policies[$policy::getPolicyID()] = $policy;
            }
        }

        return $policies;
    }

    /**
     * @return string[]
     */
    public static function getPolicyNames(): array
    {
        $names = [];
        foreach (self::getPolicies() as $key => $pol) {
            $names[$key] = $pol::getPolicyName();
        }
        return $names;
    }

    protected array $data;

    public function __construct(protected Consultation $consultation, protected IHasPolicies $baseObject, ?array $data)
    {
        $this->data = $data ?: [];
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

    abstract public function checkUser(?User $user, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool;

    public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        return $this->checkUser(User::getCurrentUser(), $allowAdmins, $assumeLoggedIn);
    }

    public function getApiObject(): array
    {
        return [
            'id' => static::getPolicyID(),
            'description' => static::getPolicyName(),
        ];
    }

    protected function checkCurrUserWithDeadline(string $deadlineType, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (!$this->baseObject->isInDeadline($deadlineType)) {
            if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_ANY, null) || !$allowAdmins) {
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

    /**
     * @return EligibilityByGroup[]|null
     */
    public function getEligibilityByGroup(): ?array
    {
        return null;
    }

    abstract public function getPermissionDeniedMotionMsg(): string;

    abstract public function getPermissionDeniedAmendmentMsg(): string;

    abstract public function getPermissionDeniedCommentMsg(): string;

    abstract public function getPermissionDeniedSupportMsg(): string;

    /**
     * Hint: $policyData might either be a pure integer (saved as a string),
     * or a JSON string with an "id" field
     */
    public static function getInstanceFromDb(?string $policyData, Consultation $consultation, IHasPolicies $baseObject): IPolicy
    {
        if ($policyData === null || trim($policyData) === '') {
            return new Nobody($consultation, $baseObject, null);
        }
        if (is_numeric($policyData)) {
            $policyId = intval($policyData);
            $policyDataObj = null;
        } else {
            $policyDataObj = json_decode($policyData, true);
            if (isset($policyDataObj['id'])) {
                $policyId = $policyDataObj['id'];
            } else {
                throw new Internal('Could not read policy');
            }
        }

        foreach (self::getPolicies() as $polId => $polClass) {
            if ($polId === $policyId) {
                /** @var IPolicy $policy */
                $policy = new $polClass($consultation, $baseObject, $policyDataObj);

                return $policy;
            }
        }
        throw new Internal('Unknown Policy: ' . $policyId);
    }

    // Will be overridden in some sub-classes
    public function serializeInstanceForDb(): string
    {
        return (string)static::getPolicyID();
    }
}
