<?php

namespace app\models\db;

use app\models\supportTypes\SupportBase;
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int $id
 * @property int $position
 * @property int|null $userId
 * @property string $role
 * @property string|null $comment
 * @property int|null $personType
 * @property string|null $name
 * @property string|null $organization
 * @property string|null $resolutionDate
 * @property string|null $contactName
 * @property string|null $contactEmail
 * @property string|null $contactPhone
 * @property string $dateCreation
 * @property string|null $extraData
 *
 * @property User|null $user
 */
abstract class ISupporter extends ActiveRecord
{
    public const ROLE_INITIATOR = 'initiates';
    public const ROLE_SUPPORTER = 'supports';
    public const ROLE_LIKE      = 'likes';
    public const ROLE_DISLIKE   = 'dislikes';

    public const PERSON_NATURAL      = 0;
    public const PERSON_ORGANIZATION = 1;

    public const EXTRA_DATA_FIELD_GENDER = 'gender';
    public const EXTRA_DATA_FIELD_CREATED_BY_ADMIN = 'createdByAdmin';
    public const EXTRA_DATA_FIELD_NON_PUBLIC = 'nonPublic';

    /**
     * @return string[]
     */
    public static function getRoles(): array
    {
        return [
            static::ROLE_INITIATOR => \Yii::t('structure', 'role_initiator'),
            static::ROLE_SUPPORTER => \Yii::t('structure', 'role_supporter'),
            static::ROLE_LIKE      => \Yii::t('structure', 'role_likes'),
            static::ROLE_DISLIKE   => \Yii::t('structure', 'role_dislikes'),
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    public function getMyUser(): ?User
    {
        if ($this->userId) {
            return User::getCachedUser($this->userId);
        } else {
            return null;
        }
    }

    public function getNameWithOrga(): string
    {
        return \app\models\layoutHooks\Layout::getSupporterNameWithOrga($this);
    }

    public function getNameWithResolutionDate(bool $html = true): string
    {
        return \app\models\layoutHooks\Layout::getSupporterNameWithResolutionDate($this, $html);
    }

    public function getGivenNameOrFull(): string
    {
        if ($this->getMyUser() && $this->personType === static::PERSON_NATURAL || $this->personType === null) {
            if ($this->getMyUser()->nameGiven) {
                return $this->getMyUser()->nameGiven;
            } else {
                return $this->name ?? '';
            }
        } else {
            return $this->name ?? '';
        }
    }

    public function getContactOrUserEmail(): ?string
    {
        if ($this->contactEmail) {
            return $this->contactEmail;
        }
        if ($this->user && $this->user->email && $this->user->emailConfirmed) {
            return $this->user->email;
        }
        return null;
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        parent::setAttributes($values, $safeOnly);
        if (!isset($values['extraData'])) {
            $this->setExtraDataEntry(static::EXTRA_DATA_FIELD_GENDER, $values['gender'] ?? null);
        }
        $this->contactEmail = ($this->contactEmail === null ? null : trim($this->contactEmail));
        $this->contactPhone = ($this->contactPhone === null ? null : trim($this->contactPhone));
        $this->contactName = ($this->contactName === null ? null : trim($this->contactName));
        $this->personType = intval($this->personType);
        $this->position   = intval($this->position);
        $this->userId     = ($this->userId === null ? null : intval($this->userId));
    }

    /**
     * @param null|mixed $default
     * @return mixed
     */
    public function getExtraDataEntry(string $name, $default = null)
    {
        $arr = $this->extraData ? json_decode($this->extraData, true) : [];
        if ($arr && isset($arr[$name])) {
            return $arr[$name];
        } else {
            return $default;
        }
    }

    /**
     * @param mixed $value
     */
    public function setExtraDataEntry(string $name, $value): void
    {
        $arr = $this->extraData ? json_decode($this->extraData, true) : [];
        if (!$arr) {
            $arr = [];
        }
        if ($value !== null) {
            $arr[$name] = $value;
        } else {
            unset($arr[$name]);
        }
        $this->extraData = json_encode($arr, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function isNonPublic(): bool
    {
        return $this->getExtraDataEntry(static::EXTRA_DATA_FIELD_NON_PUBLIC, false);
    }

    abstract public function getIMotion(): IMotion;

    public static function createInitiator(Consultation $consultation, SupportBase $supportType, bool $iAmAdmin): static {
        /** @phpstan-ignore-next-line - "unsafe usage of new static()" is intended here, static refers to the class implementing ISupporter */
        $supporter = new static();
        $supporter->role = static::ROLE_INITIATOR;
        $supporter->dateCreation = date('Y-m-d H:i:s');
        if (User::getCurrentUser() && !$iAmAdmin) {
            $settings = $supportType->getSettingsObj();
            $user = User::getCurrentUser();

            $supporter->userId = $user->id;
            $supporter->name = trim($user->name);
            $supporter->organization = $user->organization !== null ? trim($user->organization) : null;
            $supporter->contactEmail = $user->email !== null ? trim($user->email) : null;
            if ($settings->initiatorCanBePerson && $settings->canInitiateAsPerson($consultation)) {
                $supporter->personType = static::PERSON_NATURAL;
            } elseif ($settings->initiatorCanBeOrganization && $settings->canInitiateAsOrganization($consultation)) {
                $supporter->personType = static::PERSON_ORGANIZATION;
                $supporter->contactName = $user->name;
            } else {
                // This is likely a misconfiguration by the admin, setting the restrictions too tight.
                $supporter->personType = static::PERSON_NATURAL;
            }
        }
        return $supporter;
    }
}
