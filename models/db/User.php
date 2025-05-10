<?php

namespace app\models\db;

use app\models\layoutHooks\Layout;
use app\models\settings\PrivilegeQueryContext;
use app\components\{ExternalPasswordAuthenticatorInterface, MotionNumbering, RequestContext, Tools, UrlHelper, mail\Tools as MailTools};
use app\models\events\UserEvent;
use app\models\exceptions\{ExceptionBase, FormError, MailNotSent, ServerConfiguration};
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
 * @property string $authKey
 * @property string|null $secretKey
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
 * @property ConsultationUserGroup[] $userGroups
 * @property UserConsultationScreening[] $consultationScreenings
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

    // Hint: compared binary, i.e. values are 1, 2, 4, ...
    public const FIXED_NAME = 1; // When submitting as a natural person, this fixes name + orga of the person
    public const FIXED_ORGA = 2; // Only affects when submitting as the organization
    public const FIXED_EMAIL = 4;

    public const AUTH_EMAIL = 'email';

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
        return RequestContext::getDbUser();
    }

    private static array $userCache = [];
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

    public static function findByAuthTypeAndName(?string $authType, ?string $authName): ?User {
        if ($authName === null) {
            return null;
        }
        if ($authType === self::AUTH_EMAIL) {
            return User::findOne(['auth' => 'email:' . $authName]);
        }

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($loginProvider = $plugin::getDedicatedLoginProvider()) {
                if ($authType === $loginProvider->getId()) {
                    return User::findOne(['auth' => $loginProvider->usernameToAuth($authName)]);
                }
            }
        }

        return null;
    }

    public static function havePrivilege(?Consultation $consultation, int $privilege, ?PrivilegeQueryContext $context): bool
    {
        $user = static::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($consultation, $privilege, $context);
    }

    public static function haveOneOfPrivileges(?Consultation $consultation, array $privileges, ?PrivilegeQueryContext $context): bool
    {
        $user = static::getCurrentUser();
        if (!$user) {
            return false;
        }
        foreach ($privileges as $privilege) {
            if ($user->hasPrivilege($consultation, $privilege, $context)) {
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

    public function getUserGroups(): ActiveQuery
    {
        return $this->hasMany(ConsultationUserGroup::class, ['id' => 'groupId'])->viaTable('userGroup', ['userId' => 'id']);
    }

    public function getConsultationScreenings(): ActiveQuery
    {
        return $this->hasMany(UserConsultationScreening::class, ['userId' => 'id']);
    }

    private static array $preloadedConsultationUserGroups = [];
    public static function preloadConsultationUserGroups(Consultation $consultation): void
    {
        if (isset(self::$preloadedConsultationUserGroups[$consultation->id])) {
            return;
        }

        self::$preloadedConsultationUserGroups[$consultation->id] = [];
        foreach ($consultation->getAllAvailableUserGroups([], true) as $userGroup) {
            foreach ($userGroup->getUserIds() as $userId) {
                if (!isset(self::$preloadedConsultationUserGroups[$consultation->id][$userId])) {
                    self::$preloadedConsultationUserGroups[$consultation->id][$userId] = [];
                }
                self::$preloadedConsultationUserGroups[$consultation->id][$userId][] = $userGroup;
            }
        }
    }

    /**
     * @return ConsultationUserGroup[]
     */
    public function getConsultationUserGroups(Consultation $consultation): array
    {
        if (isset(self::$preloadedConsultationUserGroups[$consultation->id][$this->id])) {
            return self::$preloadedConsultationUserGroups[$consultation->id][$this->id];
        }

        $groups = [];
        foreach ($consultation->getAllAvailableUserGroups([], true) as $userGroup) {
            foreach ($userGroup->getUserIds() as $userId) {
                if ($userId === $this->id) {
                    $groups[] = $userGroup;
                }
            }
        }
        return $groups;
    }

    /**
     * @return int[]
     */
    public function getConsultationUserGroupIds(Consultation $consultation): array
    {
        return array_map(function (ConsultationUserGroup $group): int {
            return $group->id;
        }, $this->getConsultationUserGroups($consultation));
    }

    /**
     * @return ConsultationUserGroup[]
     */
    public function getUserGroupsForConsultation(Consultation $consultation): array
    {
        return array_filter((array)$this->userGroups, function (ConsultationUserGroup $group) use ($consultation): bool {
            return $group->isSpecificallyRelevantForConsultationOrSite($consultation);
        });
    }

    /**
     * @return ConsultationUserGroup[]
     */
    public function getUserGroupsWithoutConsultation(?string $authType = null): array
    {
        return array_filter((array)$this->userGroups, function (ConsultationUserGroup $group): bool {
            return $group->consultationId === null && $group->siteId === null;
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
                $this->secretKey    = \Yii::$app->getSecurity()->generateRandomString();
                $this->dateCreation = new Expression("NOW()");
            }
            return true;
        }
        return false;
    }

    private ?\app\models\settings\User $settingsObject = null;

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
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    private function getSecretKey(): string
    {
        if (!$this->secretKey) {
            // for older accounts, this property was not generated during account creation, so we generate it on demand here.
            $this->secretKey = \Yii::$app->getSecurity()->generateRandomString(32);
            $this->save();
        }
        return $this->secretKey;
    }

    private function createConfirmationCode(string $base): string
    {
        $key = $base . $this->getSecretKey() . AntragsgruenApp::getInstance()->randomSeed;

        return substr(base64_encode(sha1($key, true)), 0, 16);
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

        if ($date === '') {
            $date = date('Ymd');
        }

        return $this->createConfirmationCode($this->email . $date);
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

    public function getGivenNameWithFallback(): string
    {
        if ($this->nameGiven !== null && trim($this->nameGiven) !== '') {
            return $this->nameGiven;
        }
        $nameParts = explode(" ", trim($this->name));
        if (count($nameParts) > 1) {
            array_pop($nameParts); // We assume the last part to be the family name; not always correct but the best guess we have
        }
        return implode(" ", $nameParts);
    }

    public function getFamilyNameWithFallback(): string
    {
        if ($this->nameFamily !== null && trim($this->nameFamily) !== '') {
            return $this->nameFamily;
        }
        $nameParts = explode(" ", trim($this->name));
        if (count($nameParts) > 1) {
            return array_pop($nameParts);
        }
        return '';
    }

    public function getFullName(): string
    {
        return trim($this->getGivenNameWithFallback() . ' ' . $this->getFamilyNameWithFallback());
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

    public function isEmailAuthUser(): bool
    {
        $authParts = explode(':', $this->auth);
        return ($authParts[0] === self::AUTH_EMAIL);
    }

    public function supportsSecondFactorAuth(): bool
    {
        return $this->isEmailAuthUser() && !$this->getSettingsObj()->preventPasswordChange;
    }

    /**
     * @return ConsultationUserGroup[]|null
     */
    public function getSelectableUserOrganizations(): ?array
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if (($loginProvider = $plugin::getDedicatedLoginProvider()) && $loginProvider->userWasLoggedInWithProvider($this)) {
                return $loginProvider->getSelectableUserOrganizations($this);
            }
        }
        return null;
    }

    public function validatePassword(string $password): bool
    {
        $correctPassword = password_verify($password, $this->pwdEnc);

        if ($correctPassword && password_needs_rehash($this->pwdEnc, PASSWORD_DEFAULT)) {
            $this->pwdEnc = password_hash($password, PASSWORD_DEFAULT);
            $this->save();
        }

        return $correctPassword;
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
    public function getMySupportedMotionsByConsultation(Consultation $consultation): array
    {
        $query = MotionSupporter::find();
        $query->innerJoin(
            'motion',
            'motionSupporter.motionId = motion.id'
        );
        $query->where('motion.status != ' . intval(Motion::STATUS_DELETED));
        $query->andWhere('motion.consultationId = ' . intval($consultation->id));
        $query->andWhere('motionSupporter.userId = ' . intval($this->id));
        $query->orderBy('(motionSupporter.role = "initiates") DESC, motion.titlePrefix ASC, motion.dateCreation DESC, motion.id DESC');

        /** @var MotionSupporter[] $supporters */
        $supporters = $query->all();

        // Hint: we go through the supports, from the newest motion version to the oldest, and keep track of the root motions already seen.
        // Skipping older entries resolving to the same root motion. Thus, we will only return the most recent version of each motion.
        $filteredSupporters = [];
        $firstMotionIds = [];
        foreach ($supporters as $supporter) {
            /** @var Motion $motion */
            $motion = $supporter->getIMotion();
            if (!$motion->isReadable()) {
                continue;
            }
            $history = MotionNumbering::getSortedHistoryForMotion($motion, false);
            if (count($history) === 0 || in_array($history[0]->id, $firstMotionIds)) {
                continue;
            }
            $firstMotionIds[] = $history[0]->id;
            $filteredSupporters[] = $supporter;
        }

        return $filteredSupporters;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getMySupportedAmendmentsByConsultation(Consultation $consultation): array
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
        $query->orderBy('(amendmentSupporter.role = "initiates") DESC, motion.titlePrefix ASC, amendment.titlePrefix ASC, amendment.dateCreation DESC');
        /** @var AmendmentSupporter[] $supporters */
        $supporters = $query->all();
        return $supporters;
    }

    public function getNotificationUnsubscribeCode(): string
    {
        return $this->id . '-' . $this->createConfirmationCode('unsubscribe');
    }

    public static function getUserByUnsubscribeCode(string $code): ?User
    {
        $parts = explode('-', $code);
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
            RequestContext::getSession()->setFlash('error', \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage());
        }
    }


    /**
     * Checks if this user has the given privilege for the given consultation
     */
    public function hasPrivilege(?Consultation $consultation, int $privilege, ?PrivilegeQueryContext $context): bool
    {
        if (!$consultation) {
            return false;
        }

        if (in_array($this->id, AntragsgruenApp::getInstance()->adminUserIds)) {
            return true;
        }

        foreach ($this->getUserGroupsForConsultation($consultation) as $userGroup) {
            if ($userGroup->getGroupPermissions()->containsPrivilege($privilege, $context)) {
                return true;
            }
        }

        return false;
    }

    public function getAuthName(): string
    {
        $authparts = explode(':', $this->auth);

        $externalAuthenticator = static::getExternalAuthenticator();
        try {
            if ($externalAuthenticator && $authparts[0] === $externalAuthenticator->getAuthPrefix()) {
                return $externalAuthenticator->formatUsername($this);
            }
        } catch (ExceptionBase $exception) {
            return 'User authenticator not set up correctly';
        }

        switch ($authparts[0]) {
            case self::AUTH_EMAIL:
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
            case self::AUTH_EMAIL:
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

        return Layout::getFormattedUsername($username ?? \Yii::t('user', 'username_deleted'), $this);
    }

    public function getAuthType(): int
    {
        if ($this->isGruenesNetzUser()) {
            return \app\models\settings\Site::LOGIN_GRUENES_NETZ;
        }
        $authparts = explode(':', $this->auth);
        if (preg_match('/^openslides-/siu', $authparts[0])) {
            return \app\models\settings\Site::LOGIN_OPENSLIDES;
        }
        switch ($authparts[0]) {
            case self::AUTH_EMAIL:
                return \app\models\settings\Site::LOGIN_STD;
            default:
                return \app\models\settings\Site::LOGIN_EXTERNAL;
        }
    }

    public function hasTooRecentRecoveryEmail(): bool
    {
        if (!$this->recoveryAt) {
            return false;
        }
        $recTs = Tools::dateSql2timestamp($this->recoveryAt);

        return ($recTs + 3600) > time();
    }

    /**
     * @throws MailNotSent
     * @throws FormError
     * @throws \app\models\exceptions\ServerConfiguration
     */
    public function sendRecoveryMail(): void
    {
        if ($this->hasTooRecentRecoveryEmail()) {
            throw new FormError(\Yii::t('user', 'err_recover_mail_sent'));
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
        $text     = str_replace(
            ['%NAME_GIVEN%', '%NAME_FAMILY%'],
            [$this->getGivenNameWithFallback(), $this->getFamilyNameWithFallback()],
            \Yii::t('user', 'recover_mail_body')
        );
        $noLogReplaces = ['%URL%' => $url, '%CODE%' => $recoveryToken];
        MailTools::sendWithLog($type, null, $this->email, $this->id, $subject, $text, '', $noLogReplaces);
    }

    /**
     * @throws FormError
     */
    public function checkRecoveryToken(string $token): bool
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

    public function createEmailChangeToken(string $newEmail, int $timestamp): string
    {
        if (YII_ENV==='test' && str_contains($newEmail, '@example.org')) {
            return 'testCode';
        }

        return $this->createConfirmationCode($newEmail . $timestamp);
    }

    /**
     * @throws FormError
     */
    public function checkEmailChangeToken(string $newEmail, string $code): void
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
    public function sendEmailChangeMail(string $newEmail): void
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
    public function changeEmailAddress(string $newEmail, string $code): void
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

    /**
     * Usually, null is returned, meaning all groups are selectable. If an array is returned, only the group IDs mentioned there are selectable.
     *
     * @return null|int[]
     */
    public function getSelectableUserGroups(Consultation $consultation): ?array
    {
        $selectableGroups = null;
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $pluginSelectableGroups = $plugin::getSelectableGroupsForUser($consultation, $this);
            if ($pluginSelectableGroups !== null && $selectableGroups === null) {
                $selectableGroups = [];
            }
            if ($pluginSelectableGroups !== null) {
                $selectableGroups = array_merge($selectableGroups, $pluginSelectableGroups);
            }
        }
        return $selectableGroups;
    }

    public function getUserAdminApiObject(Consultation $consultation): array
    {
        $settings = $this->getSettingsObj();

        $data = $this->getUserdataExportObject();
        $data['id'] = $this->id;
        $data['selectable_groups'] = $this->getSelectableUserGroups($consultation);
        $data['vote_weight'] = $settings->getVoteWeight($consultation);
        $data['has_2fa'] = $settings->secondFactorKeys !== null && count($settings->secondFactorKeys) > 0;
        $data['force_2fa'] = $settings->enforceTwoFactorAuthentication;
        $data['prevent_password_change'] = $settings->preventPasswordChange;
        $data['force_password_change'] = $settings->forcePasswordChange;

        $groups = array_values(array_filter($this->userGroups, function (ConsultationUserGroup $group) use ($consultation): bool {
            return $group->isSpecificallyRelevantForConsultationOrSite($consultation);
        }));
        $data['groups'] = array_map(function (ConsultationUserGroup $group): int {
            return $group->id;
        }, $groups);

        return $data;
    }

    public function getUserdataExportObject(): array
    {
        $status = match ($this->status) {
            static::STATUS_CONFIRMED => 'confirmed',
            static::STATUS_UNCONFIRMED => 'unconfirmed',
            static::STATUS_DELETED => 'deleted',
            default => '',
        };
        return [
            'name'             => $this->name,
            'name_given'       => $this->nameGiven,
            'name_family'      => $this->nameFamily,
            'organization'     => $this->organization,
            'email'            => $this->email,
            'email_confirmed'  => ($this->emailConfirmed == 1),
            'auth'             => $this->auth,
            'date_creation'    => $this->dateCreation,
            'status'           => $status,
            'auth_type'        => $this->getAuthType(),
            'ppreplyto'        => $this->getSettingsObj()->ppReplyTo,
        ];
    }

    public function deleteAccount(): void
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
        $this->secretKey       = null;
        $this->recoveryToken   = null;
        $this->recoveryAt      = null;
        $this->save(false);

        foreach ($this->userGroups as $userGroup) {
            $this->unlink('userGroups', $userGroup, true);
        }
        foreach ($this->consultationScreenings as $consultationScreening) {
            $this->unlink('consultationScreenings', $consultationScreening, true);
        }

        $this->trigger(User::EVENT_DELETED, new UserEvent($this));
    }
}
