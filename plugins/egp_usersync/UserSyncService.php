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
     * @return array<string, array<string, array{invited: integer, added: integer, removed: integer, unchanged: integer}>>
     */
    public function syncLists(array $userLists): array
    {
        $results = [];
        foreach ($userLists as $userList) {
            $results[$userList->getListName()] = $this->syncUserList($userList);
        }
        return $results;
    }

    /**
     * @return string[]
     */
    private function getConsultationUrlsForList(string $listName): array
    {
        return Module::$consultationListMapping[$listName] ?? [$listName];
    }

    /**
     * All consultations are resolved before any changes are made, so a misconfigured URL does not lead to a partial sync.
     *
     * @return array<string, ConsultationUserGroup> consultation URL => participant group
     */
    private function getUserGroupsForList(string $listName): array
    {
        $consultations = UrlHelper::getCurrentSite()->consultations;
        $knownConsultationUrls = array_map(fn(Consultation $consultation) => $consultation->urlPath, $consultations);

        $userGroups = [];
        foreach ($this->getConsultationUrlsForList($listName) as $consultationUrl) {
            $foundConsultation = null;
            foreach ($consultations as $consultation) {
                if (mb_strtolower($consultation->urlPath) === mb_strtolower($consultationUrl)) {
                    $foundConsultation = $consultation;
                }
            }

            if (!$foundConsultation) {
                throw new ApiResponseException('consultation not found: ' . $consultationUrl . '. only known values: ' . implode(', ', $knownConsultationUrls), 400);
            }

            $userGroups[$foundConsultation->urlPath] = $this->getParticipantGroup($foundConsultation);
        }

        return $userGroups;
    }

    private function getParticipantGroup(Consultation $consultation): ConsultationUserGroup
    {
        foreach ($consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->templateId === ConsultationUserGroup::TEMPLATE_PARTICIPANT) {
                return $userGroup;
            }
        }

        throw new ApiResponseException('consultation ' . $consultation->urlPath . ' found, but no participant group', 500);
    }

    /**
     * @return array<string, array{invited: integer, added: integer, removed: integer, unchanged: integer}>
     */
    private function syncUserList(UserList $userList): array
    {
        $userGroups = $this->getUserGroupsForList($userList->getListName());

        // Users receive at most one e-mail per list, even if they are added to multiple consultations
        $notifiedUserIds = [];

        $results = [];
        foreach ($userGroups as $consultationUrl => $userGroup) {
            $results[$consultationUrl] = $this->syncUserListIntoGroup($userList, $userGroup, $notifiedUserIds);
        }
        return $results;
    }

    /**
     * @param int[] $notifiedUserIds
     *
     * @return array{invited: integer, added: integer, removed: integer, unchanged: integer}
     */
    private function syncUserListIntoGroup(UserList $userList, ConsultationUserGroup $userGroup, array &$notifiedUserIds): array
    {
        $invited = 0;
        $added = 0;
        $removed = 0;
        $unchanged = 0;

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
                        (in_array($dbUser->id, $notifiedUserIds) ? null : \Yii::t('admin', 'siteacc_email_text_pre'))
                    );
                    $notifiedUserIds[] = $dbUser->id;
                    $added++;
                }
            } else {
                $dbUser = $this->getUserGroupAdminMethods($userGroup->consultation)->createSingleDetailedUser(
                    User::AUTH_EMAIL,
                    $user->getEmail(),
                    null,
                    false,
                    $user->getName(),
                    $user->getLastName(),
                    $user->getParty(),
                    [$userGroup],
                    \Yii::t('admin', 'siteacc_email_text_pre')
                );

                $dbUser->fixedData = User::FIXED_NAME | User::FIXED_ORGA;
                $dbUser->save();
                $notifiedUserIds[] = $dbUser->id;

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
