<?php

declare(strict_types=1);

namespace app\plugins\openslides\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class User
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     * @SerializedName("default_password")
     */
    private $defaultPassword;

    /**
     * @var float
     * @SerializedName("vote_weight")
     */
    private $voteWeight;

    /**
     * @var int[]
     * @SerializedName("vote_delegated_from_users_id")
     */
    private $voteDelegatedFromUsersId;

    /**
     * @var int|null
     * @SerializedName("vote_delegated_to_id")
     */
    private $voteDelegatedToId;

    /**
     * @var bool
     * @SerializedName("is_active")
     */
    private $isActive;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string|null
     * @SerializedName("last_email_send")
     */
    private $lastEmailSend;

    /**
     * @var bool
     * @SerializedName("is_committee")
     */
    private $isCommittee;

    /**
     * @var bool
     * @SerializedName("is_present")
     */
    private $isPresent;

    /**
     * @var string
     * @SerializedName("first_name")
     */
    private $firstName;

    /**
     * @var string
     * @SerializedName("last_name")
     */
    private $lastName;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $email;

    /**
     * @var int[]
     * @SerializedName("groups_id")
     */
    private $groupsId;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var string
     * @SerializedName("about_me")
     */
    private $aboutMe;

    /**
     * @var string
     * @SerializedName("structure_level")
     */
    private $structureLevel;

    /**
     * @var string
     * @SerializedName("auth_level")
     */
    private $authType;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getDefaultPassword(): string
    {
        return $this->defaultPassword;
    }

    public function setDefaultPassword(string $defaultPassword): void
    {
        $this->defaultPassword = $defaultPassword;
    }

    public function getVoteWeight(): float
    {
        return $this->voteWeight;
    }

    public function setVoteWeight(float $voteWeight): void
    {
        $this->voteWeight = $voteWeight;
    }

    public function getVoteDelegatedFromUsersId(): array
    {
        return $this->voteDelegatedFromUsersId;
    }

    public function setVoteDelegatedFromUsersId(array $voteDelegatedFromUsersId): void
    {
        $this->voteDelegatedFromUsersId = $voteDelegatedFromUsersId;
    }

    public function getVoteDelegatedToId(): ?int
    {
        return $this->voteDelegatedToId;
    }

    public function setVoteDelegatedToId(?int $voteDelegatedToId): void
    {
        $this->voteDelegatedToId = $voteDelegatedToId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getLastEmailSend(): ?string
    {
        return $this->lastEmailSend;
    }

    public function setLastEmailSend(?string $lastEmailSend): void
    {
        $this->lastEmailSend = $lastEmailSend;
    }

    public function isCommittee(): bool
    {
        return $this->isCommittee;
    }

    public function setIsCommittee(bool $isCommittee): void
    {
        $this->isCommittee = $isCommittee;
    }

    public function isPresent(): bool
    {
        return $this->isPresent;
    }

    public function setIsPresent(bool $isPresent): void
    {
        $this->isPresent = $isPresent;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getGroupsId(): array
    {
        return $this->groupsId;
    }

    public function setGroupsId(array $groupsId): void
    {
        $this->groupsId = $groupsId;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getAboutMe(): string
    {
        return $this->aboutMe;
    }

    public function setAboutMe(string $aboutMe): void
    {
        $this->aboutMe = $aboutMe;
    }

    public function getStructureLevel(): string
    {
        return $this->structureLevel;
    }

    public function setStructureLevel(string $structureLevel): void
    {
        $this->structureLevel = $structureLevel;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function setAuthType(string $authType): void
    {
        $this->authType = $authType;
    }
}
