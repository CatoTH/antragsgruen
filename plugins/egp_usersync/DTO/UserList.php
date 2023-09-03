<?php

declare(strict_types=1);

namespace app\plugins\egp_usersync\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class UserList
{
    /**
     * @SerializedName("listName")
     */
    private string $listName;

    /** @var User[] */
    private array $users = [];

    public function getListName(): string
    {
        return $this->listName;
    }

    public function setListName(string $listName): self
    {
        $this->listName = $listName;

        return $this;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        $this->users[] = $user;
    }

    public function removeUser(User $user): void
    {
    }
}
