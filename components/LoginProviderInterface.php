<?php

namespace app\components;

use app\models\db\ConsultationUserGroup;
use app\models\db\User;

interface LoginProviderInterface
{
    public function getId(): string;
    public function getName(): string;
    public function renderLoginForm(string $backUrl, bool $active): string;
    public function performLoginAndReturnUser(): User;
    public function userWasLoggedInWithProvider(?User $user): bool;
    public function usernameToAuth(string $username): string;

    /**
     * @return ConsultationUserGroup[]|null
     */
    public function getSelectableUserOrganizations(User $user): ?array;

    /**
     * @throws \Exception
     */
    public function logoutCurrentUserIfRelevant(string $backUrl): ?string;

    public function renderAddMultipleUsersForm(): ?string;
}
