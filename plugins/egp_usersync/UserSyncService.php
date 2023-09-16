<?php

declare(strict_types=1);

namespace app\plugins\egp_usersync;

use app\components\{RequestContext, UrlHelper, UserGroupAdminMethods};
use app\models\db\{Consultation, ConsultationUserGroup, User};
use app\models\exceptions\ApiResponseException;
use app\plugins\egp_usersync\DTO\UserList;

class UserSyncService
{
    /**
     * @param UserList[] $userLists
     *
     * @return array<string, array{added: integer, removed: integer, unchanged: integer}>
     */
    public function syncLists(array $userLists): array
    {
        $results = [];
        foreach ($userLists as $userList) {
            $results[$userList->getListName()] = $this->syncUserList($userList);
        }
        return $results;
    }

    private function getUserGroupForList(string $listName): ConsultationUserGroup
    {
        if (isset(Module::$consultationListMapping[$listName])) {
            $consultationUrl = Module::$consultationListMapping[$listName];
        } else {
            $consultationUrl = $listName;
        }

        $foundConsultation = null;
        $knownConsultationUrls = [];
        foreach (UrlHelper::getCurrentSite()->consultations as $consultation) {
            $knownConsultationUrls[] = $consultation->urlPath;
            if (mb_strtolower($consultation->urlPath) === mb_strtolower($consultationUrl)) {
                $foundConsultation = $consultation;
            }
        }

        if (!$foundConsultation) {
            throw new ApiResponseException('consultation not found. only known values: ' . implode(', ', $knownConsultationUrls), 400);
        }

        foreach ($foundConsultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->templateId === ConsultationUserGroup::TEMPLATE_PARTICIPANT) {
                return $userGroup;
            }
        }

        throw new ApiResponseException('consultation found, but no participant group', 500);
    }

    /**
     * @return array{invited: integer, added: integer, removed: integer, unchanged: integer}
     */
    private function syncUserList(UserList $userList): array
    {
        $invited = 0;
        $added = 0;
        $removed = 0;
        $unchanged = 0;

        $userGroup = $this->getUserGroupForList($userList->getListName());
        $currentUserIdsInGroup = $userGroup->getUserIds();

        $newUserIds = [];
        foreach ($userList->getUsers() as $user) {
            $dbUser = User::findByAuthTypeAndName(User::AUTH_EMAIL, $user->getEmail());
            if ($dbUser) {
                if (in_array($dbUser->id, $currentUserIdsInGroup)) {
                    if ($dbUser->nameFamily !== $user->getLastName() || $dbUser->nameGiven !== $user->getName() || $dbUser->organization !== $user->getParty()) {
                        $dbUser->nameFamily = $user->getLastName();
                        $dbUser->nameGiven = $user->getName();
                        $dbUser->name = $user->getName() . ' ' . $user->getLastName();
                        $dbUser->organization = $user->getParty();
                        $dbUser->fixedData = User::FIXED_NAME | User::FIXED_ORGA;
                        $dbUser->save();
                    }
                    $unchanged++;
                } else {
                    $this->getUserGroupAdminMethods($userGroup->consultation)->addSingleDetailedUser(
                        $dbUser,
                        [$userGroup],
                        \Yii::t('admin', 'siteacc_email_text_pre')
                    );
                    $added++;
                }
            } else {
                $dbUser = $this->getUserGroupAdminMethods($userGroup->consultation)->createSingleDetailedUser(
                    User::AUTH_EMAIL,
                    $user->getEmail(),
                    null,
                    $user->getName(),
                    $user->getLastName(),
                    $user->getParty(),
                    [$userGroup],
                    \Yii::t('admin', 'siteacc_email_text_pre')
                );

                $dbUser->fixedData = User::FIXED_NAME | User::FIXED_ORGA;
                $dbUser->save();

                $invited++;
            }
            $newUserIds[] = $dbUser->id;
        }

        foreach ($currentUserIdsInGroup as $currId) {
            if (!in_array($currId, $newUserIds)) {
                $dbUser = User::findOne($currId);
                foreach ($dbUser->userGroups as $userGroupIt) {
                    if ($userGroupIt->id === $userGroup->id) {
                        $dbUser->unlink('userGroups', $userGroupIt, true);
                    }
                }
                $removed++;
            }
        }

        return [
            'invited' => $invited,
            'added' => $added,
            'removed' => $removed,
            'unchanged' => $unchanged,
        ];
    }

    private function getUserGroupAdminMethods(Consultation $consultation): UserGroupAdminMethods
    {
        $methods = new UserGroupAdminMethods();
        $methods->setRequestData($consultation, RequestContext::getWebRequest(), RequestContext::getSession());
        return $methods;
    }
}
