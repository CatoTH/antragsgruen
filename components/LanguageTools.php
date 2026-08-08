<?php

declare(strict_types=1);

namespace app\components;

use app\components\yii\MessageSource;
use app\models\db\{Consultation, Site};
use app\models\settings\AntragsgruenApp;

/**
 * Everything related to the language a user is browsing the site in.
 *
 * A site can be browsed in several languages if at least two of them are set in the site settings.
 * If less than two languages are configured, the site behaves as it always did: the language is
 * determined by the consultation's wording base alone and no language switching is offered.
 */
class LanguageTools
{
    public const SESSION_KEY = 'userLanguage';

    private static ?string $currentLanguage = null;

    /**
     * The languages this site may be browsed in. Less than two entries means that this is a
     * single-language site.
     *
     * @return string[]
     */
    public static function getSupportedLanguages(?Site $site = null): array
    {
        $site ??= UrlHelper::getCurrentSite();
        if (!$site) {
            return [];
        }

        return self::filterSupportedLanguages($site->getSettings()->supportedLanguages);
    }

    /**
     * Drops languages Antragsgrün has no translation for at all.
     *
     * @param string[] $languages
     * @return string[]
     */
    public static function filterSupportedLanguages(array $languages): array
    {
        $knownLanguages = MessageSource::getBaseLanguages();

        return array_values(array_filter(
            $languages,
            fn (string $language): bool => isset($knownLanguages[$language])
        ));
    }

    public static function isMultiLanguageSite(?Site $site = null): bool
    {
        return count(self::getSupportedLanguages($site)) > 1;
    }

    /**
     * The language the consultation is primarily held in, derived from the wording base.
     * Content without an explicit language is assumed to be in this language.
     */
    public static function getPrimaryLanguage(?Consultation $consultation = null): string
    {
        $consultation ??= UrlHelper::getCurrentConsultation();

        return self::resolvePrimaryLanguage($consultation?->wordingBase);
    }

    /**
     * Derives the primary language from a wording base like "de-parteitag", falling back to the
     * language this installation is set up in.
     */
    public static function resolvePrimaryLanguage(?string $wordingBase): string
    {
        $candidates = [];
        if ($wordingBase) {
            $candidates[] = $wordingBase;
        }
        $candidates[] = AntragsgruenApp::getInstance()->baseLanguage;

        $knownLanguages = MessageSource::getBaseLanguages();
        foreach ($candidates as $candidate) {
            // The wording base can hold several comma-separated variants like "de-parteitag"
            $language = explode('-', explode(',', $candidate)[0])[0];
            if (isset($knownLanguages[$language])) {
                return $language;
            }
        }

        return 'en';
    }

    /**
     * The language the current user is browsing the site in.
     */
    public static function getCurrentLanguage(): string
    {
        if (self::$currentLanguage !== null) {
            return self::$currentLanguage;
        }

        $language = self::resolveCurrentLanguage();

        // Only memoize once the site is known; before that, the set of supported languages is not resolvable yet
        if (UrlHelper::getCurrentSite()) {
            self::$currentLanguage = $language;
        }

        return $language;
    }

    private static function resolveCurrentLanguage(): string
    {
        $supportedLanguages = self::getSupportedLanguages();
        if (count($supportedLanguages) < 2 || !self::hasSession()) {
            return self::getPrimaryLanguage();
        }

        $session      = RequestContext::getSession();
        $chosenByUser = $session->get(self::SESSION_KEY);
        if (is_string($chosenByUser) && in_array($chosenByUser, $supportedLanguages, true)) {
            return $chosenByUser;
        }

        // No language chosen yet: infer it from the browser and remember it for the rest of the session
        $acceptableLanguages = RequestContext::getWebRequest()->getAcceptableLanguages();
        $language            = self::matchBrowserLanguage($acceptableLanguages, $supportedLanguages)
                               ?? self::getPrimaryLanguage();
        $session->set(self::SESSION_KEY, $language);

        return $language;
    }

    /**
     * Picks the first of the browser's languages that this site supports. Only base languages are
     * compared, so a browser asking for "en-GB" is served the site's "en".
     *
     * @param string[] $acceptableLanguages as sent by the browser, ordered by preference
     * @param string[] $supportedLanguages
     */
    public static function matchBrowserLanguage(array $acceptableLanguages, array $supportedLanguages): ?string
    {
        foreach ($acceptableLanguages as $acceptableLanguage) {
            $baseLanguage = explode('-', strtolower(str_replace('_', '-', $acceptableLanguage)))[0];
            if (in_array($baseLanguage, $supportedLanguages, true)) {
                return $baseLanguage;
            }
        }

        return null;
    }

    /**
     * Returns false if the language is not supported by this site.
     */
    public static function setCurrentLanguage(string $language): bool
    {
        if (!in_array($language, self::getSupportedLanguages(), true)) {
            return false;
        }

        RequestContext::getSession()->set(self::SESSION_KEY, $language);
        self::$currentLanguage = $language;

        return true;
    }

    public static function getLanguageName(string $language): string
    {
        return MessageSource::getBaseLanguages()[$language] ?? $language;
    }

    /**
     * Rendered output that varies by reader language (view/PDF caches, ...) may have a cached copy
     * under any of these languages, all of which need flushing together. On a single-language site
     * there is only ever one variant, in which case this returns the consultation's primary
     * language - not the ambient getCurrentLanguage(), since a flush can be triggered by anyone
     * (an admin action, a console command, a background job) editing content that belongs to a
     * consultation/site other than whichever one the current request happens to be about.
     *
     * @return string[]
     */
    public static function getLanguagesToFlush(?Consultation $consultation): array
    {
        $supported = self::getSupportedLanguages($consultation?->site);

        return (count($supported) >= 2) ? $supported : [self::getPrimaryLanguage($consultation)];
    }

    /**
     * Console commands and unit tests have no session to read the language from.
     */
    private static function hasSession(): bool
    {
        return \Yii::$app instanceof \yii\web\Application;
    }

    public static function resetRequestCache(): void
    {
        self::$currentLanguage = null;
    }
}
