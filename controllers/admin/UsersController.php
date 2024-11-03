<?php

namespace app\controllers\admin;

use app\models\exceptions\{ResponseException, UserEditFailed};
use app\models\settings\{AntragsgruenApp, ConsultationUserOrganisation, Privileges};
use app\models\http\{HtmlErrorResponse, HtmlResponse, JsonResponse, RedirectResponse, RestApiResponse};
use app\components\{mail\Tools as MailTools, UrlHelper, UserGroupAdminMethods};
use app\models\db\{Consultation, ConsultationUserGroup, EMailLog, User};

class UsersController extends AdminBase
{
    private UserGroupAdminMethods $userGroupAdminMethods;

    public function beforeAction($action): bool
    {
        $result = parent::beforeAction($action);

        if ($result) {
            $this->userGroupAdminMethods = new UserGroupAdminMethods();
            $this->userGroupAdminMethods->setRequestData($this->consultation, $this->getHttpRequest(), $this->getHttpSession());
        }

        return $result;
    }

    private function getConsultationAndCheckAdminPermission(): Consultation
    {
        $consultation = $this->consultation;

        if (!User::havePrivilege($consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            throw new ResponseException(new HtmlErrorResponse(403, \Yii::t('admin', 'no_access')));
        }

        return $consultation;
    }

    private function getUsersWidgetData(Consultation $consultation): array
    {
        $usersArr = array_map(function (User $user) use ($consultation): array {
            return $user->getUserAdminApiObject($consultation);
        }, $consultation->getUsersInAnyGroup());
        $groupsArr = array_map(function (ConsultationUserGroup $group): array {
            return $group->getUserAdminApiObject();
        }, $consultation->getAllAvailableUserGroups());

        return [
            'users' => $usersArr,
            'groups' => $groupsArr,
        ];
    }

    public function actionAddSingleInit(): RestApiResponse
    {
        $this->getConsultationAndCheckAdminPermission();

        $user = User::findByAuthTypeAndName($this->getPostValue('type'), $this->getPostValue('username'));
        if ($user) {
            $thisConRoles = $user->getConsultationUserGroupIds($this->consultation);
            $response = [
                'exists' => true,
                'alreadyMember' => (count($thisConRoles) > 0),
                'organization' => $user->organization,
            ];
        } else {
            $response = ['exists' => false];
        }

        return new RestApiResponse(200, $response);
    }

    public function actionAddSingle(): RedirectResponse
    {
        $this->getConsultationAndCheckAdminPermission();

        $user = User::findByAuthTypeAndName($this->getPostValue('authType'), $this->getPostValue('authUsername'));
        if ($user) {
            $thisConRoles = $user->getConsultationUserGroupIds($this->consultation);
            if (count($thisConRoles) > 0) {
                $this->getHttpSession()->setFlash('error', 'This user already has permissions for this consultation');
            }
        }

        $toAssignGroupIds = array_map('intval', $this->getPostValue('userGroups', []));
        $toAssignGroups = [];
        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if (in_array($userGroup->id, $toAssignGroupIds)) {
                $this->userGroupAdminMethods->preventInvalidSiteAdminEdit($this->consultation, $userGroup);
                $toAssignGroups[] = $userGroup;
            }
        }
        if (count($toAssignGroups) === 0) {
            $this->getHttpSession()->setFlash('error', 'You need to provide at least one user group');
            return new RedirectResponse(UrlHelper::createUrl('/admin/users/index'));
        }

        if ($this->isPostSet('sendEmail')) {
            $emailText = $this->getPostValue('emailText');
        } else {
            $emailText = null;
        }

        if ($user) {
            $this->userGroupAdminMethods->addSingleDetailedUser($user, $toAssignGroups, $emailText);
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'siteacc_user_added_single'));
        } else {
            if ($this->isPostSet('generatePassword')) {
                $password = null;
            } else {
                $password = $this->getPostValue('password');
            }
            $user = $this->userGroupAdminMethods->createSingleDetailedUser(
                $this->getPostValue('authType'),
                $this->getPostValue('authUsername'),
                $password,
                $this->isPostSet('forcePasswordChange'),
                $this->getPostValue('nameGiven'),
                $this->getPostValue('nameFamily'),
                $this->getPostValue('organization'),
                $toAssignGroups,
                $emailText
            );
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'siteacc_user_created_single'));
        }

        foreach ($this->consultation->screeningUsers as $screeningUser) {
            if ($screeningUser->userId === $user->id) {
                $screeningUser->delete();
            }
        }

        return new RedirectResponse(UrlHelper::createUrl('/admin/users/index'));
    }

    public function actionAddMultipleWw(): RedirectResponse
    {
        $this->getConsultationAndCheckAdminPermission();

        if (trim($this->getPostValue('samlWW', '')) !== '' && AntragsgruenApp::getInstance()->isSamlActive()) {
            $this->userGroupAdminMethods->addUsersBySamlWw();
        }

        return new RedirectResponse(UrlHelper::createUrl('/admin/users/index'));
    }

    public function actionAddMultipleEmail(): RedirectResponse
    {
        $this->getConsultationAndCheckAdminPermission();

        if (trim($this->getPostValue('emailAddresses', '')) !== '') {
            $this->userGroupAdminMethods->addUsersByEmail();
        }

        return new RedirectResponse(UrlHelper::createUrl('/admin/users/index'));
    }

    public function actionSearchGroups(string $query): JsonResponse
    {
        $this->handleRestHeaders(['GET'], true);

        return new JsonResponse(array_map(function (ConsultationUserGroup $group): array {
            return [
                'id' => $group->id,
                'label' => $group->title,
            ];
        }, ConsultationUserGroup::findBySearchQuery($this->consultation, $query)));
    }

    public function actionIndex(): HtmlResponse
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        if ($this->isPostSet('grantAccess')) {
            $userIds = array_map('intval', $this->getPostValue('userId', []));
            $defaultGroup = $this->userGroupAdminMethods->getDefaultUserGroup();
            foreach ($this->consultation->screeningUsers as $screeningUser) {
                if (!in_array($screeningUser->userId, $userIds)) {
                    continue;
                }
                $user = $screeningUser->user;
                $user->link('userGroups', $defaultGroup);
                /** @noinspection PhpUnhandledExceptionInspection */
                $screeningUser->delete();

                $consUrl = UrlHelper::absolutizeLink(UrlHelper::createUrl('/consultation/index'));
                $emailText = str_replace('%LINK%', $consUrl, \Yii::t('user', 'access_granted_email'));

                MailTools::sendWithLog(
                    EMailLog::TYPE_ACCESS_GRANTED,
                    $this->consultation,
                    $user->email,
                    $user->id,
                    \Yii::t('user', 'acc_grant_email_title'),
                    $emailText
                );
            }
            $this->consultation->refresh();
        }

        if ($this->isPostSet('noAccess')) {
            $userIds = array_map('intval', $this->getPostValue('userId', []));
            foreach ($this->consultation->screeningUsers as $screeningUser) {
                if (in_array($screeningUser->userId, $userIds)) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $screeningUser->delete();
                }
            }
            $this->consultation->refresh();
        }

        if ($this->isPostSet('saveOrganisations')) {
            $settings = $consultation->getSettings();
            $settings->organisations = ConsultationUserOrganisation::fromHtmlForm(
                $consultation,
                $this->getPostValue('organisation', []),
                $this->getPostValue('autoUserGroups', [])
            );
            $consultation->setSettings($settings);
            $consultation->save();

            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'siteacc_organs_saved'));
        }

        return new HtmlResponse($this->render('index', [
            'widgetData' => $this->getUsersWidgetData($consultation),
            'screening' => $consultation->screeningUsers,
            'globalUserAdmin' => User::havePrivilege($consultation, Privileges::PRIVILEGE_GLOBAL_USER_ADMIN, null),
        ]));
    }

    public function actionSave(): RestApiResponse
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['POST'], true);

        $additionalData = [
            'msg_success' => null,
            'msg_error' => null,
        ];
        try {
            switch ($this->getHttpRequest()->post('op')) {
                case 'save-user':
                    if (User::havePrivilege($consultation, Privileges::PRIVILEGE_GLOBAL_USER_ADMIN, null)) {
                        $this->userGroupAdminMethods->setUserData(
                            intval($this->getPostValue('userId')),
                            $this->getPostValue('nameGiven', ''),
                            $this->getPostValue('nameFamily', ''),
                            $this->getPostValue('organization', ''),
                            $this->getPostValue('ppReplyTo', ''),
                            $this->getPostValue('newPassword'),
                            $this->getPostValue('newAuth'),
                            $this->getPostValue('remove2Fa'),
                            $this->getPostValue('force2Fa'),
                            $this->getPostValue('preventPasswordChange'),
                            $this->getPostValue('forcePasswordChange')
                        );
                    }
                    if ($this->getPostValue('voteWeight') !== null) {
                        $this->userGroupAdminMethods->setUserVoteWeight(
                            intval($this->getPostValue('userId')),
                            intval($this->getPostValue('voteWeight'))
                        );
                    }
                    $this->userGroupAdminMethods->setUserGroupsToUser(
                        intval($this->getPostValue('userId')),
                        array_map('intval', $this->getPostValue('groups', []))
                    );
                    break;
                case 'remove-user':
                    $this->userGroupAdminMethods->removeUser(intval($this->getPostValue('userId')));
                    break;
                case 'delete-user':
                    if (User::havePrivilege($consultation, Privileges::PRIVILEGE_GLOBAL_USER_ADMIN, null)) {
                        $this->userGroupAdminMethods->deleteUser(intval($this->getPostValue('userId')));
                    }
                    break;
                case 'create-user-group':
                    $this->userGroupAdminMethods->createUserGroup($this->getPostValue('groupName'));
                    break;
                case 'save-group':
                    $this->userGroupAdminMethods->saveUserGroup(
                        intval($this->getPostValue('groupId')),
                        $this->getPostValue('groupTitle'),
                        $this->getPostValue('privilegeList', [])
                    );
                    break;
                case 'remove-group':
                    $this->userGroupAdminMethods->removeUserGroup(intval($this->getPostValue('groupId')));
                    break;
            }
        } catch (UserEditFailed $failed) {
            $additionalData['msg_error'] = $failed->getMessage();
        }

        return new RestApiResponse(200, array_merge(
            $this->getUsersWidgetData($consultation),
            $additionalData
        ));
    }

    public function actionPoll(): RestApiResponse
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['GET'], true);

        return new RestApiResponse(200, $this->getUsersWidgetData($consultation));
    }
}
