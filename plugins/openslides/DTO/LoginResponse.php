<?php

declare(strict_types=1);

namespace app\plugins\openslides\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class LoginResponse
{
    /**
     * @var int
     * @SerializedName("user_id")
     */
    private $userId;

    /**
     * @var bool
     * @SerializedName("guest_enabled")
     */
    private $guestEnabled;

    /**
     * @var User
     * @SerializedName("user")
     */
    private $user;

    /**
     * @var string
     * @SerializedName("auth_type")
     */
    private $authType;

    /**
     * @var array
     * @SerializedName("permissions")
     */
    private $permissions;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function isGuestEnabled(): bool
    {
        return $this->guestEnabled;
    }

    public function setGuestEnabled(bool $guestEnabled): void
    {
        $this->guestEnabled = $guestEnabled;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function setAuthType(string $authType): void
    {
        $this->authType = $authType;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }
}
