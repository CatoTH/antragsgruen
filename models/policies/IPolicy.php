<?php

namespace app\models\policies;

use app\components\UrlHelper;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationUserGroup, IHasPolicies, User};
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

abstract class IPolicy
{
    const POLICY_NOBODY       = 0;
    const POLICY_ALL          = 1;
    const POLICY_LOGGED_IN    = 2;
    const POLICY_USER_GROUPS  = 6;
    const POLICY_ADMINS       = 3;
    const POLICY_GRUENES_NETZ = 4;
    const POLICY_ORGANIZATION = 5;

    /**
     * @return IPolicy[]
     */
    public static function getPolicies(): array
    {
        $policies = [
            static::POLICY_ADMINS => Admins::class,
            static::POLICY_ALL => All::class,
            static::POLICY_LOGGED_IN => LoggedIn::class,
            static::POLICY_USER_GROUPS => UserGroups::class,
            static::POLICY_NOBODY => Nobody::class,
        ];

        if (AntragsgruenApp::getInstance()->isSamlActive()) {
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
    public static function getPolicyNames(): array
    {
        $names = [];
        foreach (static::getPolicies() as $key => $pol) {
            $names[$key] = $pol::getPolicyName();
        }
        return $names;
    }

    /** @var Consultation */
    protected $consultation;

    /** @var IHasPolicies */
    protected $baseObject;

    /** @var array */
    protected $data;

    public function __construct(Consultation $consultation, IHasPolicies $baseObject, ?array $data)
    {
        $this->consultation = $consultation;
        $this->baseObject = $baseObject;
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

    abstract public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool;

    public function getApiObject(): array
    {
        return [
            'id' => static::getPolicyID(),
        ];
    }

    protected function checkCurrUserWithDeadline(string $deadlineType, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (!$this->baseObject->isInDeadline($deadlineType)) {
            if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_ANY) || !$allowAdmins) {
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

        foreach (static::getPolicies() as $polId => $polClass) {
            if ($polId === $policyId) {
                return new $polClass($consultation, $baseObject, $policyDataObj);
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
