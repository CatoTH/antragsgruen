<?php

namespace app\plugins\drupal_civicrm;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\models\db\User;
use app\models\exceptions\{Internal, Login, LoginInvalidPassword, LoginInvalidUser};

class PasswordAuthenticator implements ExternalPasswordAuthenticatorInterface
{
    private PasswordAuthenticatorConfiguration $config;
    private ?\PDO $pdo = null;

    public function __construct(PasswordAuthenticatorConfiguration $config)
    {
        $this->config = $config;
    }

    private function getPdo(): \PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new \PDO($this->config->pdoDsn, $this->config->pdoUsername, $this->config->pdoPassword);
            $this->pdo->exec("SET time_zone = '+00:00'");
        }

        return $this->pdo;
    }

    private function querySingleRow(string $sql, ?array $params): ?array
    {
        $statement = $this->getPdo()->prepare($sql);
        $statement->execute($params);
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return $row;
        }

        return null;
    }

    private function getUserByName(string $username): ?array
    {
        $sql  = 'SELECT user.uid, contact.first_name, contact.last_name, user.name, user.pass AS password, user.mail AS email, ' .
                'contact.display_name, GROUP_CONCAT(usergroup.name SEPARATOR ",") AS groups, contact_organization.organization_name AS delegate_of ' .
                'FROM users AS user ' .
                'JOIN civicrm_uf_match ON user.uid = civicrm_uf_match.uf_id AND civicrm_uf_match.domain_id = :domain ' .
                'JOIN civicrm_contact AS contact ON civicrm_uf_match.contact_id = contact.id ' .
                'JOIN civicrm_group_contact ON civicrm_uf_match.contact_id = civicrm_group_contact.contact_id ' .
                'JOIN civicrm_group AS usergroup ON usergroup.id = civicrm_group_contact.group_id ' .
                'LEFT JOIN civicrm_value_copenhagen_congress_data_30 ON contact.id = civicrm_value_copenhagen_congress_data_30.entity_id ' .
                'LEFT JOIN civicrm_contact AS contact_organization ON civicrm_value_copenhagen_congress_data_30.party__organisation__eu__292 = contact_organization.id ' .
                'WHERE user.name = :username AND user.status = 1 ' .
                'GROUP BY user.uid';
        $user = $this->querySingleRow($sql, [
            ':username' => $username,
            ':domain'   => $this->config->domainId,
        ]);
        if ($user) {
            $user['groups'] = explode(',', $user['groups']);
            if ($user['delegate_of'] === null) {
                $user['delegate_of'] = '';
            }
        }

        return $user;
    }

    public function getAuthPrefix(): string
    {
        return 'civicrm';
    }

    public function supportsChangingPassword(): bool
    {
        return false;
    }

    public function supportsResetPassword(): bool
    {
        return false;
    }

    public function supportsCreatingAccounts(): bool
    {
        return false;
    }

    public function replacesLocalUserAccounts(): bool
    {
        return true;
    }

    public function resetPasswordAlternativeLink(): ?string
    {
        return $this->config->resetAlternativeLink ?: null;
    }

    public function performLogin(string $username, string $password): User
    {
        $user = $this->getUserByName($username);
        if (!$user) {
            throw new LoginInvalidUser('Sorry, but we don\'t know this e-mail-address. Please contact us if you think this is an error.');
        }

        if (!DrupalPasswordHashing::userCheckPassword($password, $user['password'])) {
            throw new LoginInvalidPassword('Sorry, but the entered password is not correct');
        }

        if ($this->config->userGroup) {
            if (!in_array($this->config->userGroup, $user['groups'])) {
                throw new Login('Sorry, but according to our database you are not eligible to register. Please contact us if you think this is a mistake.');
            }
        }

        $auth    = $this->getAuthPrefix() . ':' . $user['uid'];
        /** @var User|null $userObj */
        $userObj = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if (!$userObj) {
            $userObj                  = new User();
            $userObj->auth            = $auth;
            $userObj->emailConfirmed  = 1;
            $userObj->pwdEnc          = null;
            $userObj->organizationIds = '';
            $userObj->status          = User::STATUS_CONFIRMED;
        }

        // Set this with every login
        $userObj->name         = $user['display_name'];
        $userObj->nameFamily   = $user['last_name'];
        $userObj->nameGiven    = $user['first_name'];
        $userObj->organization = $user['delegate_of'];
        $userObj->email        = $user['email'];
        $userObj->fixedData    = User::FIXED_NAME | User::FIXED_ORGA;
        if (!$userObj->save()) {
            var_dump($userObj->getErrors());
            die();
        }

        return $userObj;
    }

    public function performRegistration(string $username, string $password): User
    {
        throw new Internal('Registration is not supported');
    }

    public function formatUsername(User $user): string
    {
        return $user->name;
    }
}
