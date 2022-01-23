<?php

declare(strict_types=1);

namespace app\plugins\openslides\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class AutoupdateChangedData
{
    /**
     * @var null|User[]
     * @SerializedName("users/user")
     */
    private $usersUsers;

    /**
     * @var null|Usergroup[]
     * @SerializedName("users/group")
     */
    private $usersGroups = [];


    // User groups - most methods are necessary for PropertyAccessor

    public function addUsersGroup(Usergroup $group): void {
        $this->usersGroups[] = $group;
    }

    public function removeUsersGroup(Usergroup $group): void {
        die("remove not supported"); // @TODO
    }

    public function hasUsersGroups(Usergroup $group): bool {
        return count($this->usersGroups) > 0; // @TODO
    }

    /**
     * @param Usergroup[]|null $usersGroups
     */
    public function setUsersGroups(?array $usersGroups): void
    {
        $this->usersGroups = $usersGroups;
    }

    /**
     * @return Usergroup[]|null
     */
    public function getUsersGroups(): ?array
    {
        return $this->usersGroups;
    }

    // Users - most methods are necessary for PropertyAccessor

    public function addUsersUsers(User $user): void {
        $this->usersUsers[] = $user;
    }

    public function removeUsersuser(User $user): void {
        die("remove not supported"); // @TODO
    }

    public function hasUsersUser(User $user): bool {
        return count($this->usersUsers) > 0; // @TODO
    }

    /**
     * @param User[]|null $usersUsers
     */
    public function setUsersUsers(?array $usersUsers): void
    {
        $this->usersUsers = $usersUsers;
    }

    /**
     * @return User[]|null
     */
    public function getUsersUsers(): ?array
    {
        return $this->usersUsers;
    }
}
