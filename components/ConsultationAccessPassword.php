<?php

namespace app\components;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\settings\AntragsgruenApp;

/**
 * Class ConsultationAccessPassword
 * @package app\components
 *
 * Two hashes / codes are relevant:
 * - consultation.settings.accessPwd is the consultation password, hashed by password_hash(PASSWORD_DEFAULT)
 * - The cookie holds a hash of consultation.settings.accessPwd(hashed) + the App's secret key.
 */
class ConsultationAccessPassword
{
    /** @var Consultation */
    private $consultation;

    /** @var Site */
    private $site;

    /**
     * ConsultationAccessPassword constructor.
     * @param Consultation $consultation
     */
    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
        $this->site         = $consultation->site;
    }

    /**
     * @return bool
     */
    public function isPasswordSet()
    {
        return ($this->consultation->getSettings()->accessPwd !== null);
    }

    /**
     * @return bool
     */
    public function allHaveSamePwd()
    {
        foreach ($this->site->consultations as $consultation) {
            if ($consultation->getSettings()->accessPwd !== $this->consultation->getSettings()->accessPwd) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $pwd
     */
    public function setPwdForOtherConsultations($pwd)
    {
        foreach ($this->site->consultations as $otherCon) {
            if ($otherCon->id !== $this->consultation->id) {
                $otherSett            = $otherCon->getSettings();
                $otherSett->accessPwd = password_hash($pwd, PASSWORD_DEFAULT);
                $otherCon->setSettings($otherSett);
                $otherCon->save();
            }
        }
    }

    /**
     * @param string $pwd
     * @return bool
     */
    public function checkPassword($pwd)
    {
        $pwd = trim($pwd);
        if (!$pwd) {
            return false;
        }
        return password_verify($pwd, $this->consultation->getSettings()->accessPwd);
    }

    /**
     * @param $cookie
     * @return bool
     */
    public function checkCookie($cookie)
    {
        if (!$cookie) {
            return false;
        }
        /** @var AntragsgruenApp $app */
        $app      = \Yii::$app->params;
        $hashBase = $app->randomSeed . $this->consultation->getSettings()->accessPwd;
        try {
            $correctHash = base64_encode(sodium_crypto_generichash($hashBase));
        } catch (\SodiumException $e) {
            die("LibSodium: " . $e->getMessage());
        }
        return $cookie === $correctHash;
    }

    /**
     * @return string
     */
    public function createCookieHash()
    {
        /** @var AntragsgruenApp $app */
        $app      = \Yii::$app->params;
        $hashBase = $app->randomSeed . $this->consultation->getSettings()->accessPwd;
        try {
            return base64_encode(sodium_crypto_generichash($hashBase));
        } catch (\SodiumException $e) {
            die("LibSodium: " . $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function isCookieLoggedIn()
    {
        $cookie = (isset($_COOKIE['consultationPwd']) ? $_COOKIE['consultationPwd'] : null);
        return $this->checkCookie($cookie);
    }

    /**
     */
    public function setCorrectCookie()
    {
        setcookie('consultationPwd', $this->createCookieHash(), time() + 365 * 24 * 3600);
    }
}
