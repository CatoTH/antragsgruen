<?php

namespace app\controllers\admin;

use app\components\{mail\Tools as MailTools, UrlHelper, UserGroupAdminMethods};
use app\models\exceptions\FormError;
use app\models\db\{Consultation, ConsultationUserGroup, EMailLog, User};
use app\models\exceptions\UserEditFailed;
use app\models\settings\AntragsgruenApp;
use yii\base\ExitException;
use yii\web\Response;

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

        if (!User::havePrivilege($consultation, ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            throw new ExitException();
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

    private function getUserForCreating(?string $type, ?string $username): ?User
    {
        if (!$type || !$username) {
            throw new FormError('type and email has to be provided');
        }
        if ($type === 'gruenesnetz') {
            $type = \app\models\settings\Site::LOGIN_GRUENES_NETZ;
        } else {
            $type = \app\models\settings\Site::LOGIN_STD;
        }

        return User::findByAuthTypeAndName($type, $username);
    }

    public function actionAddSingleInit(): string
    {
        $this->getConsultationAndCheckAdminPermission();

        $user = $this->getUserForCreating($this->getPostValue('type'), $this->getPostValue('username'));
        if ($user) {
            $thisConRoles = $user->getConsultationUserGroupIds($this->consultation);
            $response = ['exists' => true, 'already_member' => (count($thisConRoles) > 0)];
        } else {
            $response = ['exists' => false];
        }

        return $this->returnRestResponse(200, json_encode($response, JSON_THROW_ON_ERROR));
    }

    public function actionAddSingle(): string
    {
        $this->getConsultationAndCheckAdminPermission();

        $user = $this->getUserForCreating($this->getPostValue('authType'), $this->getPostValue('authUsername'));
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
                $toAssignGroups[] = $userGroup;
            }
        }
        if (count($toAssignGroups) === 0) {
            $this->getHttpSession()->setFlash('error', 'You need to provide at least one user group');
            return $this->redirect(UrlHelper::createUrl('/admin/users/index'));
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
            $this->userGroupAdminMethods->createSingleDetailedUser(
                $this->getPostValue('authType'),
                $this->getPostValue('authUsername'),
                $password,
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

        return $this->redirect(UrlHelper::createUrl('/admin/users/index'));
    }

    public function actionAddMultipleWw(): string
    {
        $this->getConsultationAndCheckAdminPermission();

        if (trim($this->getPostValue('samlWW', '')) !== '' && AntragsgruenApp::getInstance()->isSamlActive()) {
            $this->userGroupAdminMethods->addUsersBySamlWw();
        }

        return $this->redirect(UrlHelper::createUrl('/admin/users/index'));
    }

    public function actionAddMultipleEmail(): string
    {
        $this->getConsultationAndCheckAdminPermission();

        if (trim($this->getPostValue('emailAddresses', '')) !== '') {
            $this->userGroupAdminMethods->addUsersByEmail();
        }

        return $this->redirect(UrlHelper::createUrl('/admin/users/index'));
    }

    public function actionSearchGroups(string $query): string
    {
        $this->handleRestHeaders(['GET'], true);

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

        return json_encode(array_map(function (ConsultationUserGroup $group): array {
            return [
                'id' => $group->id,
                'label' => $group->title,
            ];
        }, ConsultationUserGroup::findBySearchQuery($this->consultation, $query)), JSON_THROW_ON_ERROR);
    }

    public function actionIndex(): string
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

        return $this->render('index', [
            'widgetData' => $this->getUsersWidgetData($consultation),
            'screening' => $consultation->screeningUsers,
        ]);
    }

    public function actionSave(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['POST'], true);

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

        $additionalData = [
            'msg_success' => null,
            'msg_error' => null,
        ];
        try {
            switch ($this->getHttpRequest()->post('op')) {
                case 'save-user-groups':
                    $this->userGroupAdminMethods->setUserGroupsToUser(
                        intval($this->getPostValue('userId')),
                        array_map('intval', $this->getPostValue('groups', []))
                    );
                    break;
                case 'remove-user':
                    $this->userGroupAdminMethods->removeUser(intval($this->getPostValue('userId')));
                    break;
                case 'create-user-group':
                    $this->userGroupAdminMethods->createUserGroup($this->getPostValue('groupName'));
                    break;
                case 'remove-group':
                    $this->userGroupAdminMethods->removeUserGroup(intval($this->getPostValue('groupId')));
                    break;
            }
        } catch (UserEditFailed $failed) {
            $additionalData['msg_error'] = $failed->getMessage();
        }

        return $this->returnRestResponse(200, json_encode(array_merge(
            $this->getUsersWidgetData($consultation),
            $additionalData
        ), JSON_THROW_ON_ERROR));
    }

    public function actionPoll(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['GET'], true);

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

        $responseData = $this->getUsersWidgetData($consultation);
        return $this->returnRestResponse(200, json_encode($responseData, JSON_THROW_ON_ERROR));
    }
}
