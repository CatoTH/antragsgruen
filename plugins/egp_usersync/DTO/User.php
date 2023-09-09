<?php

declare(strict_types=1);

namespace app\plugins\egp_usersync\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class User
{
    private string $name;

    /**
     * @SerializedName("listName")
     */
    private string $lastName;

    private ?string $party = null;

    private string $email;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getParty(): ?string
    {
        return $this->party;
    }

    public function setParty(?string $party): self
    {
        $this->party = $party;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
