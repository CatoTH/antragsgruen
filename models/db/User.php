<?php

namespace app\models\db;

use app\models\layoutHooks\Layout;
use app\components\{ExternalPasswordAuthenticatorInterface, Tools, UrlHelper, GruenesNetzSamlClient, mail\Tools as MailTools};
use app\models\events\UserEvent;
use app\models\exceptions\{FormError, MailNotSent, ServerConfiguration};
use app\models\settings\AntragsgruenApp;
use yii\db\{ActiveQuery, ActiveRecord, Expression};
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $name
 * @property string|null $nameGiven
 * @property string|null $nameFamily
 * @property string|null $organization
 * @property string|null $organizationIds
 * @property string|null $email
 * @property int $fixedData
 * @property int $emailConfirmed
 * @property string|null $auth
 * @property string $dateCreation
 * @property string|null $dateLastLogin
 * @property int $status
 * @property string|null $pwdEnc
 * @property string|null $authKey
 * @property string|null $recoveryToken
 * @property string|null $recoveryAt
 * @property string|null $emailChange
 * @property string|null $emailChangeAt
 * @property string|null $settings
 *
 * @property null|AmendmentComment[] $amendmentComments
 * @property null|AmendmentSupporter[] $amendmentSupports
 * @property null|MotionComment[] $motionComments
 * @property null|MotionSupporter[] $motionSupports
 * @property Site[] $adminSites
 * @property ConsultationUserGroup[] $userGroups
 * @property ConsultationUserPrivilege[] $consultationPrivileges
 * @property ConsultationLog[] $logEntries
 * @property UserNotification[] $notifications
 * @property Vote[] $votes
 */
class User extends ActiveRecord implements IdentityInterface
{
    public const EVENT_ACCOUNT_CONFIRMED = 'account_confirmed';
    public const EVENT_DELETED           = 'deleted';

    public const STATUS_UNCONFIRMED = 1;
    public const STATUS_CONFIRMED   = 0;
    public const STATUS_DELETED     = -1;

    /**
     * @return string[]
     */
    public static function getStatuses(): array
    {
        return [
            1  => \Yii::t('structure', 'user_status_1'),
            0  => \Yii::t('structure', 'user_status_0'),
            -1 => \Yii::t('structure', 'user_status_-1'),
        ];
    }


    public static function getCurrentUser(): ?User
    {
        try {
            if (\Yii::$app->user->getIsGuest()) {
                return null;
            } else {
                /** @var User $user */
                $user = \Yii::$app->user->identity;
                return $user;
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (\yii\base\UnknownPropertyException $e) {
            // Can happen with console commands
            return null;
        }
    }

    private static $userCache = [];
    public static function getCachedUser(int $userId): ?User
    {
        // Hint: also cache "null" entries
        if (!in_array($userId, array_keys(self::$userCache))) {
            self::$userCache[$userId] = static::find()->where(['id' => $userId])->andWhere('status != ' . User::STATUS_DELETED)->one();
        }
        return self::$userCache[$userId];
    }

    public static function isCurrentUser(?User $user): bool
    {
        $currentUser = static::getCurrentUser();
        if (!$user || !$currentUser) {
            return false;
        }
        return $user->id === $currentUser->id;
    }

    public static function findByAuthTypeAndName(int $authType, ?string $authName): ?User {
        if ($authName === null) {
            return null;
        }
        switch ($authType) {
            case \app\models\settings\Site::LOGIN_STD:
                return User::findOne(['auth' => 'email:' . $authName]);
            case \app\models\settings\Site::LOGIN_GRUENES_NETZ:
                return User::findOne(['auth' => static::gruenesNetzId2Auth($authName)]);
            default:
                return null;
        }
    }

    public static function havePrivilege(?Consultation $consultation, int $privilege): bool
    {
        $user = static::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($consultation, $privilege);
    }

    public static function haveOneOfPrivileges(?Consultation $consultation, array $privileges): bool
    {
        $user = static::getCurrentUser();
        if (!$user) {
            return false;
        }
        foreach ($privileges as $privilege) {
            if ($user->hasPrivilege($consultation, $privilege)) {
                return true;
            }
        }
        return false;
    }

    public static function currentUserIsSuperuser(): bool
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return in_array($user->id, AntragsgruenApp::getInstance()->adminUserIds);
    }


    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'user';
    }

    public function getMotionComments(): ActiveQuery
    {
        return $this->hasMany(MotionComment::class, ['userId' => 'id']);
    }

    public function getMotionSupports(): ActiveQuery
    {
        return $this->hasMany(MotionSupporter::class, ['userId' => 'id']);
    }

    public function getAmendmentComments(): ActiveQuery
    {
        return $this->hasMany(AmendmentComment::class, ['userId' => 'id']);
    }

    public function getAmendmentSupports(): ActiveQuery
    {
        return $this->hasMany(AmendmentSupporter::class, ['userId' => 'id']);
    }

    public function getEmailLogs(): ActiveQuery
    {
        return $this->hasMany(EMailLog::class, ['userId' => 'id']);
    }

    public function getLogEntries(): ActiveQuery
    {
        return $this->hasMany(ConsultationLog::class, ['userId' => 'id']);
    }

    public function getAdminSites(): ActiveQuery
    {
        return $this->hasMany(Site::class, ['id' => 'siteId'])->viaTable('siteAdmin', ['userId' => 'id']);
    }

    public function getConsultationPrivileges(): ActiveQuery
    {
        return $this->hasMany(ConsultationUserPrivilege::class, ['userId' => 'id']);
    }

    public function getUserGroups(): ActiveQuery
    {
        return $this->hasMany(ConsultationUserGroup::class, ['id' => 'groupId'])->viaTable('userGroup', ['userId' => 'id']);
    }

    /**
     * @return int[]
     */
    public function getConsultationUserGroupIds(Consultation $consultation): array
    {
        $ids = [];
        foreach ($consultation->getAllAvailableUserGroups() as $userGroup) {
            foreach ($userGroup->getUserIds() as $userId) {
                if ($userId === $this->id) {
                    $ids[] = $userGroup->id;
                }
            }
        }
        return $ids;
    }

    public function getUserGroupsForConsultation(Consultation $consultation): array
    {
        return array_filter((array)$this->userGroups, function (ConsultationUserGroup $group) use ($consultation): bool {
            return $group->isRelevantForConsultation($consultation);
        });
    }

    public function getNotifications(): ActiveQuery
    {
        return $this->hasMany(UserNotification::class, ['userId' => 'id']);
    }

    public function getVotes(): ActiveQuery
    {
        return $this->hasMany(Vote::class, ['userId' => 'id']);
    }

    public function rules(): array
    {
        return [
            [['auth', 'status'], 'required'],
            [['id', 'emailConfirmed'], 'number'],
        ];
    }


    public static function getExternalAuthenticator(): ?ExternalPasswordAuthenticatorInterface
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $authenticator = $pluginClass::getExternalPasswordAuthenticator();
            if ($authenticator) {
                return $authenticator;
            }
        }
        return null;
    }


    /**
     * Finds an identity by the given ID.
     * @param string|integer $userId the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($userId)
    {
        return static::findOne($userId);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\Yii\filters\auth\HttpBearerAuth]] will set this parameter to be
     * `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['authKey' => $token]);
    }

    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return string|integer an ID that uniquely identifies a user identity.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the IDs of the organizations the user is enlisted in.
     * This has to be provided by and updated by the authentication mechanism (only SAML supports this at this point).
     *
     * @return string[]
     */
    public function getMyOrganizationIds()
    {
        if ($this->organizationIds) {
            $organizationIds = json_decode($this->organizationIds, true);
            if ($this->isGruenesNetzUser()) {
                $organizationIds = GruenesNetzSamlClient::resolveOrganizationIds($organizationIds);
            }
            return $organizationIds;
        } else {
            return [];
        }
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * Validates the given auth key.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @param string $authKey the given auth key
     * @return boolean whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey): bool
    {
        return hash_equals($this->authKey, $authKey);
    }

    /**
     * @param bool $insert
     * @throws \Yii\base\Exception
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->authKey      = \Yii::$app->getSecurity()->generateRandomString();
                $this->dateCreation = new Expression("NOW()");
            }
            return true;
        }
        return false;
    }

    /** @var null|\app\models\settings\User */
    private $settingsObject = null;

    public function getSettingsObj(): \app\models\settings\User
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new \app\models\settings\User($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(\app\models\settings\User $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * @throws \Yii\base\Exception
     */
    public static function createPassword(): string
    {
        return \Yii::$app->getSecurity()->generateRandomString(8);
    }

    public function createEmailConfirmationCode(string $date = ''): string
    {
        if (YII_ENV == 'test') {
            return 'testCode';
        }

        if ($date == '') {
            $date = date('Ymd');
        }
        $binaryCode = md5($this->id . $date . AntragsgruenApp::getInstance()->randomSeed, true);
        return substr(base64_encode($binaryCode), 0, 10);
    }

    public function checkEmailConfirmationCode(string $code): bool
    {
        if (hash_equals($code, $this->createEmailConfirmationCode())) {
            return true;
        }
        if (hash_equals($code, $this->createEmailConfirmationCode(date('Ymd', time() - 24 * 3600)))) {
            return true;
        }
        if (hash_equals($code, $this->createEmailConfirmationCode(date('Ymd', time() - 2 * 24 * 3600)))) {
            return true;
        }
        return false;
    }

    public function getGruenesNetzName(): ?string
    {
        if (preg_match("/https:\/\/([a-z0-9_-]+)\.netzbegruener\.in\//siu", $this->auth, $matches)) {
            return $matches[1];
        }
        if (preg_match("/https:\/\/service\.gruene.de\/openid\/([a-z0-9_-]+)/siu", $this->auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function isGruenesNetzUser(): bool
    {
        if (preg_match("/https:\/\/[a-z0-9_-]+\.netzbegruener\.in\//siu", $this->auth)) {
            return true;
        }
        if (preg_match("/https:\/\/service\.gruene.de\/openid\/[a-z0-9_-]+/siu", $this->auth)) {
            return true;
        }
        return false;
    }

    public static function gruenesNetzId2Auth(string $username): string
    {
        return 'openid:https://service.gruene.de/openid/' . $username;
    }

    public function isEmailAuthUser(): bool
    {
        $authParts = explode(':', $this->auth);
        return ($authParts[0] === 'email');
    }

    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->pwdEnc);
    }

    public function changePassword(string $newPassword): void
    {
        $this->pwdEnc        = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->recoveryToken = null;
        $this->recoveryAt    = null;
        $this->save();
    }

    /**
     * @return MotionSupporter[]
     */
    public function getMySupportedMotionsByConsultation(Consultation $consultation)
    {
        $query     = MotionSupporter::find();
        $query->innerJoin(
            'motion',
            'motionSupporter.motionId = motion.id'
        );
        $query->where('motion.status != ' . IntVal(Motion::STATUS_DELETED));
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->andWhere('motionSupporter.userId = ' . IntVal($this->id));
        $query->orderBy('(motionSupporter.role = "initiates") DESC, motion.dateCreation DESC');

        return $query->all();
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getMySupportedAmendmentsByConsultation(Consultation $consultation)
    {
        $query     = AmendmentSupporter::find();
        $query->innerJoin(
            'amendment',
            'amendmentSupporter.amendmentId = amendment.id'
        );
        $query->innerJoin('motion', 'motion.id = amendment.motionId');
        $query->where('motion.status != ' . IntVal(Motion::STATUS_DELETED));
        $query->andWhere('amendment.status != ' . IntVal(Motion::STATUS_DELETED));
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->andWhere('amendmentSupporter.userId = ' . IntVal($this->id));
        $query->orderBy('(amendmentSupporter.role = "initiates") DESC, amendment.dateCreation DESC');

        return $query->all();
    }


    public function getNotificationUnsubscribeCode(): string
    {
        return $this->id . '-' . substr(md5($this->id . 'unsubscribe' . AntragsgruenApp::getInstance()->randomSeed), 0, 8);
    }

    public static function getUserByUnsubscribeCode(string $code): ?User
    {
        $parts = explode('-', $code);
        /** @var User $user */
        $user = User::findOne($parts[0]);
        if (!$user) {
            return null;
        }
        if (hash_equals($user->getNotificationUnsubscribeCode(), $code)) {
            return $user;
        } else {
            return null;
        }
    }

    /**
     * @param string $subject
     * @param string $text
     * @param int $mailType
     */
    public function notificationEmail(Consultation $consultation, $subject, $text, $mailType): void
    {
        if ($this->email == '' || !$this->emailConfirmed) {
            return;
        }
        $code         = $this->getNotificationUnsubscribeCode();
        $blocklistUrl = UrlHelper::createUrl(['user/emailblocklist', 'code' => $code]);
        $blocklistUrl = UrlHelper::absolutizeLink($blocklistUrl);
        $salutation   = str_replace('%NAME%', $this->name, \Yii::t('user', 'noti_greeting') . "\n\n");
        $sig          = "\n\n" . \Yii::t('user', 'noti_bye') . "\n" . $blocklistUrl;
        $text         = $salutation . $text . $sig;
        try {
            MailTools::sendWithLog($mailType, $consultation, $this->email, $this->id, $subject, $text);
        } catch (MailNotSent | ServerConfiguration $e) {
            \Yii::$app->session->setFlash('error', \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage());
        }
    }


    /**
     * Checks if this user has the given privilege for the given consultation
     */
    public function hasPrivilege(?Consultation $consultation, int $privilege): bool
    {
        if (!$consultation) {
            return false;
        }

        if (in_array($this->id, AntragsgruenApp::getInstance()->adminUserIds)) {
            return true;
        }

        foreach ($this->getUserGroupsForConsultation($consultation) as $userGroup) {
            if ($userGroup->containsPrivilege($privilege)) {
                return true;
            }
        }

        return false;
    }

    public function getAuthName(): string
    {
        $authparts = explode(':', $this->auth);

        $externalAuthenticator = static::getExternalAuthenticator();
        if ($externalAuthenticator && $authparts[0] === $externalAuthenticator->getAuthPrefix()) {
            return $externalAuthenticator->formatUsername($this);
        }

        switch ($authparts[0]) {
            case 'email':
                return 'E-Mail: ' . $authparts[1];
            case 'openid':
                if ($this->isGruenesNetzUser()) {
                    return 'Grünes Netz: ' . $this->getGruenesNetzName();
                } else {
                    return $this->auth;
                }
            default:
                return $this->auth;
        }
    }

    public function getAuthUsername(): string
    {
        $authparts = explode(':', $this->auth);
        switch ($authparts[0]) {
            case 'email':
                $username = $authparts[1] ?? '';
                break;
            case 'openid':
                if ($this->isGruenesNetzUser()) {
                    $username = $this->getGruenesNetzName();
                } else {
                    $username = $this->auth;
                }
                break;
            default:
                $username = $this->auth;
        }

        return Layout::getFormattedUsername($username, $this);
    }

    public function getAuthType(): int
    {
        if ($this->isGruenesNetzUser()) {
            return \app\models\settings\Site::LOGIN_GRUENES_NETZ;
        }
        $authparts = explode(':', $this->auth);
        if (preg_match('/^openslides\-/siu', $authparts[0])) {
            return \app\models\settings\Site::LOGIN_OPENSLIDES;
        }
        switch ($authparts[0]) {
            case 'email':
                return \app\models\settings\Site::LOGIN_STD;
            default:
                return \app\models\settings\Site::LOGIN_EXTERNAL;
        }
    }

    /**
     * @throws MailNotSent
     * @throws FormError
     * @throws \app\models\exceptions\ServerConfiguration
     */
    public function sendRecoveryMail()
    {
        if ($this->recoveryAt) {
            $recTs = Tools::dateSql2timestamp($this->recoveryAt);
            if (time() - $recTs < 24 * 3600) {
                throw new FormError(\Yii::t('user', 'err_recover_mail_sent'));
            }
        }
        if ($this->email === null) {
            return;
        }

        $recoveryToken = \Yii::$app->getSecurity()->generateRandomString(10);
        $this->recoveryAt = date('Y-m-d H:i:s');
        $this->recoveryToken = password_hash($recoveryToken, PASSWORD_DEFAULT);
        $this->save();

        $type     = EMailLog::TYPE_PASSWORD_RECOVERY;
        $subject  = \Yii::t('user', 'recover_mail_title');
        $url      = UrlHelper::createUrl(['user/recovery', 'email' => $this->email, 'code' => $recoveryToken]);
        $url      = UrlHelper::absolutizeLink($url);
        $text     = \Yii::t('user', 'recover_mail_body');
        $replaces = ['%URL%' => $url, '%CODE%' => $recoveryToken];
        MailTools::sendWithLog($type, null, $this->email, $this->id, $subject, $text, '', $replaces);
    }

    /**
     * @param string $token
     * @throws FormError
     */
    public function checkRecoveryToken($token): bool
    {
        if ($this->recoveryAt) {
            $recTs = Tools::dateSql2timestamp($this->recoveryAt);
        } else {
            $recTs = 0;
        }
        if (time() - $recTs > 24 * 3600) {
            throw new FormError(\Yii::t('user', 'err_no_recovery'));
        }
        if (!password_verify($token, $this->recoveryToken)) {
            throw new FormError(\Yii::t('user', 'err_code_wrong'));
        }
        return true;
    }

    /**
     * @param string $newEmail
     * @param int $timestamp
     * @return string
     */
    public function createEmailChangeToken($newEmail, $timestamp): string
    {
        if (YII_ENV == 'test' && mb_strpos($newEmail, '@example.org') !== false) {
            return 'testCode';
        }

        $key = $newEmail . $timestamp . $this->id . $this->authKey;
        return substr(sha1($key), 0, 10);
    }

    /**
     * @param string $newEmail
     * @param string $code
     * @throws FormError
     */
    public function checkEmailChangeToken($newEmail, $code)
    {
        if ($this->emailChange != $newEmail || $this->emailChange === null) {
            throw new FormError(\Yii::t('user', 'err_emailchange_notfound'));
        }
        $timestamp = Tools::dateSql2timestamp($this->emailChangeAt);
        if ($timestamp < time() - 24 * 3600) {
            throw new FormError(\Yii::t('user', 'err_change_toolong'));
        }
        if ($code != $this->createEmailChangeToken($newEmail, $timestamp)) {
            throw new FormError(\Yii::t('user', 'err_code_wrong'));
        }
    }

    /**
     * @throws MailNotSent
     * @throws ServerConfiguration
     */
    public function sendEmailChangeMail(string $newEmail)
    {
        $changeTs            = time();
        $this->emailChange   = $newEmail;
        $this->emailChangeAt = date('Y-m-d H:i:s', $changeTs);
        $changeKey           = $this->createEmailChangeToken($newEmail, $changeTs);

        $type     = EMailLog::TYPE_EMAIL_CHANGE;
        $subject  = \Yii::t('user', 'emailchange_mail_title');
        $url      = UrlHelper::createUrl(['user/emailchange', 'email' => $newEmail, 'code' => $changeKey]);
        $url      = UrlHelper::absolutizeLink($url);
        $text     = \Yii::t('user', 'emailchange_mail_body');
        $replaces = ['%URL%' => $url];
        MailTools::sendWithLog($type, null, $newEmail, $this->id, $subject, $text, '', $replaces);

        $this->save();
    }

    /**
     * @throws FormError
     */
    public function changeEmailAddress(string $newEmail, string $code)
    {
        if (AntragsgruenApp::getInstance()->confirmEmailAddresses) {
            $this->checkEmailChangeToken($newEmail, $code);
        }

        $this->email          = $newEmail;
        $this->emailConfirmed = 1;
        $this->emailChange    = null;
        $this->emailChangeAt  = null;
        $this->save();
    }

    public function getChangeRequestedEmailAddress(): ?string
    {
        if (!$this->emailChangeAt) {
            return null;
        }
        $recTs = Tools::dateSql2timestamp($this->emailChangeAt);
        if (time() - 24 * 3600 > $recTs) {
            return null;
        }
        return $this->emailChange;
    }

    public function getUserAdminApiObject(?Consultation $consultation): array
    {
        $data = $this->getUserdataExportObject();
        $data['id'] = $this->id;

        $groups = $this->userGroups;
        if ($consultation) {
            $groups = array_values(array_filter($groups, function (ConsultationUserGroup $group) use ($consultation): bool {
                return $group->isRelevantForConsultation($consultation);
            }));
        }
        $data['groups'] = array_map(function (ConsultationUserGroup $group): int {
            return $group->id;
        }, $groups);

        return $data;
    }

    public function getUserdataExportObject(): array
    {
        switch ($this->status) {
            case static::STATUS_CONFIRMED:
                $status = 'confirmed';
                break;
            case static::STATUS_UNCONFIRMED:
                $status = 'unconfirmed';
                break;
            case static::STATUS_DELETED:
                $status = 'deleted';
                break;
            default:
                $status = '';
        }
        return [
            'name'             => $this->name,
            'name_given'       => $this->nameGiven,
            'name_family'      => $this->nameFamily,
            'organization'     => $this->organization,
            'organization_ids' => $this->getMyOrganizationIds(),
            'email'            => $this->email,
            'email_confirmed'  => ($this->emailConfirmed == 1),
            'auth'             => $this->auth,
            'date_creation'    => $this->dateCreation,
            'status'           => $status,
            'auth_type'        => $this->getAuthType(),
        ];
    }

    public function deleteAccount2(): void
    {
        $this->name            = '';
        $this->nameGiven       = '';
        $this->nameFamily      = '';
        $this->organization    = '';
        $this->organizationIds = '';
        $this->fixedData       = 0;
        $this->email           = '';
        $this->emailConfirmed  = 0;
        $this->auth            = null;
        $this->status          = static::STATUS_DELETED;
        $this->pwdEnc          = null;
        $this->authKey         = '';
        $this->recoveryToken   = null;
        $this->recoveryAt      = null;
        $this->save(false);

        foreach ($this->userGroups as $userGroup) {
            $this->unlink('userGroups', $userGroup, true);
        }

        ConsultationUserPrivilege::deleteAll(['userId' => $this->id]);

        $this->trigger(User::EVENT_DELETED, new UserEvent($this));
    }
}
