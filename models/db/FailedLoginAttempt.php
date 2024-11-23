<?php

declare(strict_types=1);

namespace app\models\db;

use app\components\RequestContext;
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $ipHash
 * @property string $username
 * @property string $dateAttempt
 */
class FailedLoginAttempt extends ActiveRecord
{
    private const THROTTLING_MIN_ATTEMPTS = 3;
    private const THROTTLING_DURATION_MINUTES = 60;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'failedLoginAttempt';
    }

    private static function getCurrentIp(): string
    {
        return RequestContext::getWebRequest()->getRemoteIP() ?? '';
    }

    private static function getCurrentIpHash(): string
    {
        return hash('sha256', self::getCurrentIp());
    }

    private static function normalizeUsername(string $username): string
    {
        return trim(mb_strtolower($username));
    }

    public static function logAttempt(string $username): void
    {
        $normalizedUsername = self::normalizeUsername($username);
        $attempt = new FailedLoginAttempt();
        $attempt->ipHash = self::getCurrentIpHash();
        $attempt->username = $normalizedUsername;
        $attempt->dateAttempt = new Expression('NOW()');
        $attempt->save();

        RequestContext::getSession()->set('loginLastFailedAttemptUsername', $normalizedUsername);
    }

    private static function needsLoginThrottlingByIp(): bool
    {
        $ignoredIps = AntragsgruenApp::getInstance()->captcha['ignoredIps'];
        if (in_array(self::getCurrentIp(), $ignoredIps)) {
            return false;
        }

        $interval = new Expression('NOW() - INTERVAL ' . intval(self::THROTTLING_DURATION_MINUTES) . ' MINUTE');
        $attempts = FailedLoginAttempt::find()
            ->where(['=', 'ipHash', self::getCurrentIpHash()])
            ->andWhere(['>', 'dateAttempt', $interval])
            ->count();

        return ($attempts >= self::THROTTLING_MIN_ATTEMPTS);
    }

    private static function needsLoginThrottlingByUsername(string $username): bool
    {
        $interval = new Expression('NOW() - INTERVAL ' . intval(self::THROTTLING_DURATION_MINUTES) . ' MINUTE');
        $attempts = FailedLoginAttempt::find()
            ->where(['=', 'username', self::normalizeUsername($username)])
            ->andWhere(['>', 'dateAttempt', $interval])
            ->count();

        return ($attempts >= self::THROTTLING_MIN_ATTEMPTS);
    }

    /**
     * Hint: this method is called twice: when rendering the form, and when actually checking the login.
     * This is unfortunately not 100% consistent:
     * - the user might change the username (which could lead to an unnecessary captcha check, but that's fine)
     * - after submitting a form with captcha, the retry time might have been passed, making the captcha unnecessary. Also fine.
     * - Problem: when changing the username, one could run into a situation where the login form doesn't know that the
     *   username is actually blocked. With the current implementation, this leads to a failed attempt due to incorrect captcha
     */
    public static function needsLoginThrottling(?string $username): bool
    {
        if ($username === null) {
            // Coming from the login form, not the actual login
            $username = RequestContext::getSession()->get('loginLastFailedAttemptUsername');
        } else {
            // Edge case: someone logs in successfully (which leads to the session being reset), logs out and tries to login again
            RequestContext::getSession()->set('loginLastFailedAttemptUsername', $username);
        }
        if (self::needsLoginThrottlingByIp()) {
            return true;
        }
        if ($username && self::needsLoginThrottlingByUsername($username)) {
            return true;
        }
        return false;
    }

    public static function resetAfterSuccessfulLogin(string $username): void
    {
        FailedLoginAttempt::deleteAll(['username' => $username]);
        FailedLoginAttempt::deleteAll(['ipHash' => self::getCurrentIpHash()]);
    }
}
