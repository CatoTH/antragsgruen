<?php

namespace app\components;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\settings\AntragsgruenApp;

/**
 * Two hashes / codes are relevant:
 * - consultation.settings.accessPwd is the consultation password, hashed by password_hash(PASSWORD_DEFAULT)
 * - The cookie holds a hash of consultation.settings.accessPwd(hashed) + the App's secret key.
 */
class ConsultationAccessPassword
{
    private Consultation $consultation;
    private Site $site;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
        $this->site         = $consultation->site;
    }

    public function isPasswordSet(): bool
    {
        return ($this->consultation->getSettings()->accessPwd !== null);
    }

    public function allHaveSamePwd(): bool
    {
        foreach ($this->site->consultations as $consultation) {
            if ($consultation->getSettings()->accessPwd !== $this->consultation->getSettings()->accessPwd) {
                return false;
            }
        }
        return true;
    }

    public function setPwdForOtherConsultations(string $pwdHash): void
    {
        foreach ($this->site->consultations as $otherCon) {
            if ($otherCon->id !== $this->consultation->id) {
                $otherSett            = $otherCon->getSettings();
                $otherSett->accessPwd = $pwdHash;
                $otherCon->setSettings($otherSett);
                $otherCon->save();
            }
        }
    }

    public function checkPassword(string $pwd): bool
    {
        $pwd = trim($pwd);
        if (!$pwd) {
            return false;
        }
        return password_verify($pwd, $this->consultation->getSettings()->accessPwd);
    }

    public function checkCookie(?string $cookie): bool
    {
        if (!$cookie) {
            return false;
        }
        $passwordHashes = explode(",", $cookie);
        $hashBase = AntragsgruenApp::getInstance()->randomSeed . $this->consultation->getSettings()->accessPwd;
        try {
            $correctHash = base64_encode(sodium_crypto_generichash($hashBase));
        } catch (\SodiumException $e) {
            die("LibSodium: " . $e->getMessage());
        }
        return in_array($correctHash, $passwordHashes);
    }

    public function createCookieHash(): string
    {
        $hashBase = AntragsgruenApp::getInstance()->randomSeed . $this->consultation->getSettings()->accessPwd;
        try {
            return base64_encode(sodium_crypto_generichash($hashBase));
        } catch (\SodiumException $e) {
            die("LibSodium: " . $e->getMessage());
        }
    }

    public function isCookieLoggedIn(): bool
    {
        return $this->checkCookie($_COOKIE['consultationPwd'] ?? null);
    }

    public function setCorrectCookie(): void
    {
        $cookie    = (isset($_COOKIE['consultationPwd']) ? explode(",", $_COOKIE['consultationPwd']) : []);
        $cookie[]  = $this->createCookieHash();
        $newCookie = implode(",", array_filter($cookie, function ($hash) {
            return ($hash !== "");
        }));
        setcookie('consultationPwd', $newCookie, time() + 365 * 24 * 3600);
    }
}
