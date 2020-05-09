<?php

namespace app\plugins\drupal_civicrm;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\models\db\User;
use app\models\exceptions\{Internal, Login, LoginInvalidPassword, LoginInvalidUser};

class PasswordAuthenticator implements ExternalPasswordAuthenticatorInterface
{
    /** @var PasswordAuthenticatorConfiguration */
    private $config;

    /** @var null|\PDO */
    private $pdo = null;

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
        $sql  = 'SELECT user.uid, user.name, user.pass AS password, user.mail AS email, ' .
                'contact.display_name, GROUP_CONCAT(usergroup.name SEPARATOR ",") groups ' .
                'FROM users AS user ' .
                'JOIN civicrm_uf_match ON user.uid = civicrm_uf_match.uf_id AND civicrm_uf_match.domain_id = :domain ' .
                'JOIN civicrm_contact contact ON civicrm_uf_match.contact_id = contact.id ' .
                'JOIN civicrm_group_contact ON civicrm_uf_match.contact_id = civicrm_group_contact.contact_id ' .
                'JOIN civicrm_group usergroup ON usergroup.id = civicrm_group_contact.group_id ' .

                'WHERE user.name = :username AND user.status = 1 ' .
                ' GROUP BY user.uid';
        $user = $this->querySingleRow($sql, [
            ':username' => $username,
            ':domain'   => $this->config->domainId,
        ]);
        if ($user) {
            $user['groups'] = explode(',', $user['groups']);
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

    public function resetPasswordAlternativeLink(): ?string
    {
        return ($this->config->resetAlternativeLink ? $this->config->resetAlternativeLink : null);
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
        $userObj = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if (!$userObj) {
            $userObj                  = new User();
            $userObj->auth            = $auth;
            $userObj->emailConfirmed  = 1;
            $userObj->pwdEnc          = '';
            $userObj->organizationIds = '';
            $userObj->status          = User::STATUS_CONFIRMED;
        }

        // Set this with every login
        $userObj->name      = $user['display_name'];
        $userObj->email     = $user['email'];
        $userObj->fixedData = 1;
        $userObj->save();
        if (!$userObj) {
            var_dump($userObj->getErrors());
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
