<?php

declare(strict_types=1);

namespace app\models\supportTypes;

use app\controllers\Base;
use app\models\db\{Amendment, AmendmentSupporter, Consultation, ConsultationMotionType, ISupporter, Motion, MotionSupporter, User};
use app\models\api\imotion\{IMotionUpdateInitiator, IMotionUpdateSupporter, SupporterType};
use app\models\settings\{PrivilegeQueryContext, Privileges, InitiatorForm};
use app\models\exceptions\{FormError, Internal};
use app\models\forms\{AmendmentEditForm, MotionEditForm};
use yii\web\View;

abstract class SupportBase
{
    // Also defined in JavaScript
    public const ONLY_INITIATOR        = 0;
    public const GIVEN_BY_INITIATOR    = 1;
    public const COLLECTING_SUPPORTERS = 2;
    public const NO_INITIATOR          = 3;

    public const LIKEDISLIKE_LIKE    = 1;
    public const LIKEDISLIKE_DISLIKE = 2;
    public const LIKEDISLIKE_SUPPORT = 4;

    protected bool $adminMode = false;

    public function __construct(
        protected readonly ConsultationMotionType $motionType,
        protected InitiatorForm $settingsObject,
    ) {
        $this->fixSettings();
    }

    /**
     * @return SupportBase[]|string[]
     */
    public static function getImplementations(): array
    {
        return [
            static::ONLY_INITIATOR        => OnlyInitiator::class,
            static::GIVEN_BY_INITIATOR    => GivenByInitiator::class,
            static::COLLECTING_SUPPORTERS => CollectBeforePublish::class,
            static::NO_INITIATOR          => NoInitiator::class,
        ];
    }

    /**
     * @throws Internal
     */
    public static function getImplementation(InitiatorForm $settings, ConsultationMotionType $motionType): SupportBase
    {
        return match ($settings->type) {
            static::ONLY_INITIATOR => new OnlyInitiator($motionType, $settings),
            static::GIVEN_BY_INITIATOR => new GivenByInitiator($motionType, $settings),
            static::COLLECTING_SUPPORTERS => new CollectBeforePublish($motionType, $settings),
            static::NO_INITIATOR => new NoInitiator($motionType, $settings),
            default => throw new Internal('Supporter form type not found'),
        };
    }

    /**
     * @return string[]
     */
    public static function getGenderSelection(): array
    {
        return [
            'female'  => \Yii::t('structure', 'gender_female'),
            'male'    => \Yii::t('structure', 'gender_male'),
            'diverse' => \Yii::t('structure', 'gender_diverse'),
            'na'      => \Yii::t('structure', 'gender_na'),
        ];
    }

    public function getSettingsObj(): InitiatorForm
    {
        return $this->settingsObject;
    }

    public function setSettingsObj(InitiatorForm $settings): void
    {
        $this->settingsObject = $settings;
        $this->fixSettings();
    }

    protected function fixSettings(): void
    {
    }

    public static function getTitle(): string
    {
        return '';
    }

    public function setAdminMode(bool $set): void
    {
        $this->adminMode = $set;
    }

    public static function collectSupportersBeforePublication(): bool
    {
        return false;
    }

    public function isValidName(string $name): bool
    {
        return (trim($name) !== '');
    }

    public static function hasInitiatorGivenSupporters(): bool
    {
        return false;
    }

    /**
     * Whether at least one initiator must be present when creating or editing a motion/amendment.
     * Only NoInitiator opts out.
     */
    public function requiresInitiator(): bool
    {
        return true;
    }

    public function hasFullTextSupporterField(): bool
    {
        return false;
    }

    /**
     * @template T of ISupporter
     * @param class-string<T> $supporterClass
     * @param IMotionUpdateInitiator[] $initiatorDtos
     * @return T[]
     */
    private function buildInitiatorsFromDto(string $supporterClass, bool $othersPrivilege, array $initiatorDtos): array
    {
        $currentUserId = User::getCurrentUser()?->id;

        $initiators = [];
        $position = 0;
        foreach ($initiatorDtos as $initiator) {
            $supporter = new $supporterClass();
            $supporter->role = ISupporter::ROLE_INITIATOR;
            $supporter->position = $position;
            $supporter->personType = ($initiator->personType === SupporterType::ORGANIZATION)
                ? ISupporter::PERSON_ORGANIZATION
                : ISupporter::PERSON_NATURAL;

            // Attributing an initiator to another user account requires PRIVILEGE_MOTION_INITIATORS.
            // Without it, the primary initiator is always the submitting user; additional initiators have no user account.
            $userId = $initiator->userId;
            if (!$othersPrivilege) {
                if ($userId !== null && $userId !== $currentUserId) {
                    $userId = $currentUserId;
                }
                if ($position === 0 && $userId === null) {
                    $userId = $currentUserId;
                }
            }
            $supporter->userId = $userId;
            if ($userId === null && $othersPrivilege) {
                $supporter->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, true);
            }
            if ($userId > 0) {
                $user = User::findOne($userId);
            } else {
                $user = null;
            }

            $position++;
            $supporter->dateCreation = date('Y-m-d H:i:s');

            if ($initiator->id) {
                $supporter->id = $initiator->id; // Hint: the actual validation that this is a valid ID is done in saveMotion/saveAmendment
            }
            if ($supporter->personType === ISupporter::PERSON_NATURAL) {
                $supporter->name = $initiator->name;
                $supporter->organization = $initiator->organization ?? '';
                if ($user && ($user->fixedData & User::FIXED_NAME) > 0) {
                    $supporter->name = $user->name;
                }
                if ($user && ($user->fixedData & User::FIXED_ORGA) > 0) {
                    $supporter->organization = $user->organization;
                }
            } else {
                $supporter->organization = $initiator->name;
            }
            $supporter->contactName = $initiator->contactName ?? '';
            $supporter->contactEmail = $initiator->contactEmail ?? '';
            $supporter->contactPhone = $initiator->contactPhone ?? '';
            if ($initiator->gender !== null) {
                $supporter->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER, $initiator->gender);
            }
            if ($initiator->resolutionDate !== null) {
                $supporter->resolutionDate = $initiator->resolutionDate;
            }

            $initiators[] = $supporter;
        }

        return $initiators;
    }

    /**
     * @template T of ISupporter
     * @param class-string<T> $supporterClass
     * @param IMotionUpdateSupporter[] $supporterDtos
     * @return T[]
     */
    private function buildSupportersFromDto(string $supporterClass, array $supporterDtos): array
    {
        $return = [];
        foreach ($supporterDtos as $i => $supporterDto) {
            if (!$this->isValidName($supporterDto->name)) {
                continue;
            }
            $sup = new $supporterClass();
            $sup->name = trim($supporterDto->name);
            $sup->role = ISupporter::ROLE_SUPPORTER;
            $sup->userId = null;
            $sup->personType = ISupporter::PERSON_NATURAL;
            $sup->position = $i;
            $sup->dateCreation = date('Y-m-d H:i:s');
            if ($supporterDto->id) {
                $sup->id = $supporterDto->id;
            }
            if ($supporterDto->organization) {
                $sup->organization = trim($supporterDto->organization);
            }
            if ($supporterDto->gender !== null) {
                $sup->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER, $supporterDto->gender);
            }
            $return[] = $sup;
        }
        return $return;
    }

    /**
     * @param IMotionUpdateInitiator[] $initiatorDtos
     * @return MotionSupporter[]
     */
    public function getMotionInitiatorsFromDto(ConsultationMotionType $motionType, array $initiatorDtos): array
    {
        $othersPrivilege = User::havePrivilege($motionType->getConsultation(), Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motionType($motionType->id));
        return $this->buildInitiatorsFromDto(MotionSupporter::class, $othersPrivilege, $initiatorDtos);
    }

    /**
     * @param IMotionUpdateSupporter[] $supporterDtos
     * @return MotionSupporter[]
     */
    public function getMotionSupportersFromDto(ConsultationMotionType $motionType, array $supporterDtos): array
    {
        if (!$this->hasInitiatorGivenSupporters()) {
            return [];
        }
        return $this->buildSupportersFromDto(MotionSupporter::class, $supporterDtos);
    }

    /**
     * @param IMotionUpdateInitiator[] $initiatorDtos
     * @return AmendmentSupporter[]
     */
    public function getAmendmentInitiatorsFromDto(Amendment $amendment, array $initiatorDtos): array
    {
        $othersPrivilege = User::havePrivilege($this->motionType->getConsultation(), Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::amendment($amendment));
        return $this->buildInitiatorsFromDto(AmendmentSupporter::class, $othersPrivilege, $initiatorDtos);
    }

    /**
     * @param IMotionUpdateSupporter[] $supporterDtos
     * @return AmendmentSupporter[]
     */
    public function getAmendmentSupportersFromDto(Amendment $amendment, array $supporterDtos): array
    {
        if (!$this->hasInitiatorGivenSupporters()) {
            return [];
        }
        $supporters = $this->buildSupportersFromDto(AmendmentSupporter::class, $supporterDtos);
        foreach ($supporters as $sup) {
            $sup->amendmentId = $amendment->id;
        }
        return $supporters;
    }

    /**
     * @param ISupporter[] $supporters
     * @throws FormError
     */
    public function validateMotion(ISupporter $initiator, array $supporters): void
    {
        if ($this->adminMode) {
            return;
        }

        $settings  = $this->getSettingsObj();

        $errors = [];

        $nameToValidate = ($initiator->personType === ISupporter::PERSON_ORGANIZATION ? $initiator->organization : $initiator->name);
        if (!$this->isValidName($nameToValidate ?? '')) {
            $errors[] = \Yii::t('motion', 'err_invalid_name');
        }

        $emailSet   = (isset($initiator->contactEmail) && trim($initiator->contactEmail) !== '');
        $checkEmail = ($settings->contactEmail === InitiatorForm::CONTACT_REQUIRED || $emailSet);
        if ($checkEmail && !filter_var(trim($initiator->contactEmail ?? ''), FILTER_VALIDATE_EMAIL)) {
            $errors[] = \Yii::t('motion', 'err_invalid_email');
        }

        $phoneSet   = (isset($initiator->contactPhone) && trim($initiator->contactPhone) !== '');
        $checkPhone = ($settings->contactPhone === InitiatorForm::CONTACT_REQUIRED || $phoneSet);
        if ($checkPhone && empty($initiator->contactPhone)) {
            $errors[] = \Yii::t('motion', 'err_invalid_phone');
        }

        if (!isset($initiator->personType)) {
            $errors[] = \Yii::t('motion', 'err_invalid_person_type');
            $personType = null;
        } else {
            $personType = intval($initiator['personType']);
        }
        if ($personType === ISupporter::PERSON_NATURAL && !$settings->canInitiateAsPerson($this->motionType->getConsultation())) {
            $errors[] = \Yii::t('motion', 'err_invalid_person_type');
        }
        if ($personType === ISupporter::PERSON_ORGANIZATION && !$settings->canInitiateAsOrganization($this->motionType->getConsultation())) {
            $errors[] = \Yii::t('motion', 'err_invalid_person_type');
        }
        if ($personType === ISupporter::PERSON_ORGANIZATION &&
            $settings->hasResolutionDate === InitiatorForm::CONTACT_REQUIRED &&
            empty($initiator->resolutionDate)) {
            $errors[] = \Yii::t('motion', 'err_no_resolution_date');
        }
        if ($personType === ISupporter::PERSON_NATURAL) {
            $validGenderValues = array_keys(static::getGenderSelection());
            $setGender = $initiator->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER);
            if ($settings->contactGender === InitiatorForm::CONTACT_REQUIRED) {
                if (!isset($initiator->resolutionDate) || !in_array($setGender, $validGenderValues)) {
                    $errors[] = \Yii::t('motion', 'err_invalid_gender');
                }
            }
            if ($settings->contactGender === InitiatorForm::CONTACT_OPTIONAL) {
                $validGenderValues[] = '';
                if (isset($setGender) && !in_array($setGender, $validGenderValues)) {
                    $errors[] = \Yii::t('motion', 'err_invalid_gender');
                }
            }
        }

        if ($this->hasInitiatorGivenSupporters()) {
            $num        = count($supporters);
            if ($personType !== ISupporter::PERSON_ORGANIZATION) {
                if ($num < $settings->minSupporters) {
                    $errors[] = \Yii::t('motion', 'err_not_enough_supporters');
                }
                if (!$settings->allowMoreSupporters && $num > $settings->minSupporters) {
                    $errors[] = \Yii::t('motion', 'err_too_many_supporters');
                }
            }
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @param ISupporter[] $supporters
     * @throws FormError
     */
    public function validateAmendment(ISupporter $initiator, array $supporters): void
    {
        $this->validateMotion($initiator, $supporters);
    }

    /**
     * @param MotionSupporter[] $supporters
     * @throws \Throwable
     */
    public function submitMotion(Motion $motion, array $supporters): void
    {
        $affectedRoles = [MotionSupporter::ROLE_INITIATOR];
        if ($this->hasInitiatorGivenSupporters() && !$this->adminMode) {
            $affectedRoles[] = MotionSupporter::ROLE_SUPPORTER;
        }

        $preCreatedByAdmin = [];
        $preNonPublic = [];
        $previousById = [];
        foreach ($motion->motionSupporters as $supp) {
            if (in_array($supp->role, $affectedRoles)) {
                $previousById[$supp->id] = $supp;
                if ($supp->userId) {
                    $preCreatedByAdmin[$supp->userId] = $supp->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, false);
                    $preNonPublic[$supp->userId] = $supp->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC, false);
                }
                $supp->delete();
            }
        }

        foreach ($supporters as $sup) {
            if (in_array($sup->role, $affectedRoles)) {
                if (isset($sup->id) && $sup->id > 0 && isset($previousById[$sup->id])) {
                    $previousById[$sup->id]->setAttributes($sup->getAttributes(), false);
                    $sup = $previousById[$sup->id];
                    unset($previousById[$sup->id]);
                } else {
                    $sup->id = null;
                }
                $sup->motionId = $motion->id;
                if (isset($sup->userId) && isset($preCreatedByAdmin[$sup->userId])) {
                    $sup->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, $preCreatedByAdmin[$sup->userId]);
                }
                if (isset($sup->userId) && isset($preNonPublic[$sup->userId])) {
                    $sup->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC, $preNonPublic[$sup->userId]);
                }
                $sup->save();
            }
        }

        foreach ($previousById as $sup) {
            $sup->delete();
        }
    }


    /**
     * @param AmendmentSupporter[] $supporters
     * @throws \Throwable
     */
    public function submitAmendment(Amendment $amendment, array $supporters): void
    {
        $affectedRoles = [MotionSupporter::ROLE_INITIATOR];
        if ($this->hasInitiatorGivenSupporters() && !$this->adminMode) {
            $affectedRoles[] = MotionSupporter::ROLE_SUPPORTER;
        }

        $preCreatedByAdmin = [];
        $preNonPublic = [];
        foreach ($amendment->amendmentSupporters as $supp) {
            if (in_array($supp->role, $affectedRoles)) {
                if ($supp->userId) {
                    $preCreatedByAdmin[$supp->userId] = $supp->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, false);
                    $preNonPublic[$supp->userId] = $supp->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC, false);
                }
                $supp->delete();
            }
        }

        $initiators = [];
        foreach ($supporters as $sup) {
            if (in_array($sup->role, $affectedRoles)) {
                $sup->amendmentId = $amendment->id;
                if ($sup->userId !== null && isset($preCreatedByAdmin[$sup->userId])) {
                    $sup->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_CREATED_BY_ADMIN, $preCreatedByAdmin[$sup->userId]);
                }
                if ($sup->userId !== null && isset($preNonPublic[$sup->userId])) {
                    $sup->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC, $preNonPublic[$sup->userId]);
                }
                $sup->save();
            }
            if ($sup->role === ISupporter::ROLE_INITIATOR) {
                $initiators[] = $sup;
            }
        }
        $amendment->refresh();

        $initiatorsFormattedPre = $amendment->getInitiatorsStr();
        $initiatorsFormattedPost = $amendment->getInitiatorsStrFromArray($initiators);
        if ($initiatorsFormattedPre !== $initiatorsFormattedPost) {
            $amendment->getMyMotion()->flushViewCache();
        }
    }

    /**
     * @param ISupporter[] $supporters
     */
    private function getEditableInitiator(array $supporters, Consultation $consultation): ?ISupporter
    {
        $user = User::getCurrentUser();
        $initiatorAdmin = User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, null);

        if ($initiatorAdmin || $user === null) {
            foreach ($supporters as $supp) {
                if ($supp->position === 0 && $supp->role === ISupporter::ROLE_INITIATOR) {
                    return $supp;
                }
            }

            return null;
        }

        // Logged in, non-admin user -> edit themselves
        foreach ($supporters as $supp) {
            if ($supp->role === ISupporter::ROLE_INITIATOR && $supp->userId === $user->id) {
                return $supp;
            }
        }
        return null;
    }

    /**
     * @throws \Exception
     */
    public function getMotionForm(ConsultationMotionType $motionType, MotionEditForm $editForm, Base $controller): string
    {
        $initiatorAdmin = User::havePrivilege($motionType->getConsultation(), Privileges::PRIVILEGE_MOTION_INITIATORS, null);

        $view           = new View();
        $initiator      = $this->getEditableInitiator($editForm->supporters, $motionType->getConsultation());
        $moreInitiators = [];
        $supporters     = [];
        foreach ($editForm->supporters as $supporter) {
            if ($supporter->role === MotionSupporter::ROLE_INITIATOR && $initiatorAdmin && $supporter->id !== $initiator?->id &&
                ($supporter->userId === null || $supporter->userId !== $initiator?->userId)) {
                $moreInitiators[] = $supporter;
            }
            if ($supporter->role === MotionSupporter::ROLE_SUPPORTER) {
                $supporters[] = $supporter;
            }
        }
        if (!$initiator) {
            $initiator               = new MotionSupporter();
            $initiator->dateCreation = date('Y-m-d H:i:s');
            $initiator->role         = MotionSupporter::ROLE_INITIATOR;
        }
        $isForOther      = false;
        if ($initiatorAdmin) {
            $isForOther = (!User::getCurrentUser() || User::getCurrentUser()->id != $initiator->userId);
        }
        return $view->render(
            '@app/views/shared/create_initiator',
            [
                'initiator'         => $initiator,
                'moreInitiators'    => $moreInitiators,
                'supporters'        => $supporters,
                'allowOther'        => $initiatorAdmin,
                'isForOther'        => $isForOther,
                'settings'          => $this->getSettingsObj(),
                'hasSupporters'     => $this->hasInitiatorGivenSupporters(),
                'supporterFulltext' => $this->hasFullTextSupporterField(),
                'adminMode'         => $this->adminMode,
                'isAmendment'       => false,
                'motionType'        => $motionType,
            ],
            $controller
        );
    }

    /**
     * @throws \Exception
     */
    public function getAmendmentForm(ConsultationMotionType $motionType, AmendmentEditForm $editForm, Base $controller): string
    {
        $initiatorAdmin = User::havePrivilege($motionType->getConsultation(), Privileges::PRIVILEGE_MOTION_INITIATORS, null);

        $view           = new View();
        $initiator      = $this->getEditableInitiator($editForm->supporters, $motionType->getConsultation());
        $supporters     = [];
        $moreInitiators = [];
        foreach ($editForm->supporters as $supporter) {
            if ($supporter->role === MotionSupporter::ROLE_INITIATOR && $initiatorAdmin && $supporter->id !== $initiator?->id &&
                ($supporter->userId === null || $supporter->userId !== $initiator?->userId)) {
                $moreInitiators[] = $supporter;
            }
            if ($supporter->role === AmendmentSupporter::ROLE_SUPPORTER) {
                $supporters[] = $supporter;
            }
        }
        if (!$initiator) {
            $initiator               = new AmendmentSupporter();
            $initiator->dateCreation = date('Y-m-d H:i:s');
            $initiator->role         = AmendmentSupporter::ROLE_INITIATOR;
        }
        $isForOther         = false;
        if ($initiatorAdmin) {
            $isForOther = (!User::getCurrentUser() || User::getCurrentUser()->id != $initiator->userId);
        }
        return $view->render(
            '@app/views/shared/create_initiator',
            [
                'initiator'         => $initiator,
                'moreInitiators'    => $moreInitiators,
                'supporters'        => $supporters,
                'allowOther'        => $initiatorAdmin,
                'isForOther'        => $isForOther,
                'settings'          => $this->getSettingsObj(),
                'hasSupporters'     => $this->hasInitiatorGivenSupporters(),
                'supporterFulltext' => $this->hasFullTextSupporterField(),
                'adminMode'         => $this->adminMode,
                'isAmendment'       => true,
                'motionType'        => $motionType,
            ],
            $controller
        );
    }
}
