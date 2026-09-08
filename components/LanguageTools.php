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

    /**
     * Three sources, in this order:
     *
     * 1. the language the user picked, kept in their session - browsing requests only, see below
     * 2. what the request asks for in its Accept-Language header
     * 3. the consultation's primary language
     *
     * This is a pure lookup: nothing is written back. The inference in step 2 is deterministic, so
     * seeding the session with its result (as this used to do) would only produce the same answer
     * again next request - while making a getter that half the codebase calls write to the session,
     * which the API is not allowed to do at all, and pinning a language that a change to the site's
     * supported languages should have re-evaluated.
     *
     * Step 1 is skipped for the API, which never touches the session. Its clients state the language
     * they want in Accept-Language instead: the frontend sends the language its page was rendered in
     * (see web/js/modules/shared/ApiClient.js), so a user's pick is honoured there just the same,
     * per browser tab rather than per user - the very distinction docs/technical/live-data.md draws
     * for live events, which are addressed by language for the same reason. Other API clients get
     * their own Accept-Language honoured, which is the best answer available for them.
     */
    private static function resolveCurrentLanguage(): string
    {
        $supportedLanguages = self::getSupportedLanguages();
        if (count($supportedLanguages) < 2 || !self::isWebRequest()) {
            return self::getPrimaryLanguage();
        }

        if (!RequestContext::isRestApiRequest()) {
            $chosenByUser = RequestContext::getSession()->get(self::SESSION_KEY);
            if (is_string($chosenByUser) && in_array($chosenByUser, $supportedLanguages, true)) {
                return $chosenByUser;
            }
        }

        $acceptableLanguages = RequestContext::getWebRequest()->getAcceptableLanguages();

        return self::matchBrowserLanguage($acceptableLanguages, $supportedLanguages)
               ?? self::getPrimaryLanguage();
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
     * An icon (flag emoji) representing the language, e.g. for the navbar language picker. Not a
     * hard-coded map: each language provides its own icon via the "language_icon" message in its own
     * messages/<language>/base.php, resolved here rather than in the current viewer's language - the
     * icon is the same symbol no matter who is looking at it, but sourcing it from the target
     * language's own translation file (instead of a static PHP array) keeps it next to that
     * language's other self-descriptive strings (aria_language_switch, ...) and lets plugins
     * providing a language override it like any other message.
     */
    public static function getLanguageIcon(string $language): string
    {
        return \Yii::t('base', 'language_icon', [], $language);
    }

    /**
     * Every language content of this consultation can be read in: the site's supported languages, or
     * just the consultation's primary language on a single-language site. Deliberately not the
     * ambient getCurrentLanguage(), as this is used by code that runs on behalf of all readers at
     * once (live events, cache flushing) and can be triggered by anyone - an admin action, a console
     * command, a background job - for a consultation other than the one the current request is about.
     *
     * @return string[]
     */
    public static function getContentLanguages(?Consultation $consultation): array
    {
        $supported = self::getSupportedLanguages($consultation?->site);

        return (count($supported) >= 2) ? $supported : [self::getPrimaryLanguage($consultation)];
    }

    /**
     * Runs the given function as if the reader were browsing in the given language, restoring the
     * ambient language afterwards. Needed wherever output is produced for someone other than the
     * current user - most notably the live events, which are rendered once per language and pushed
     * to all readers of a consultation, no matter which language the moderator triggering them
     * happens to be using.
     *
     * The user's session is deliberately not touched (unlike setCurrentLanguage()): this only
     * changes the language for the duration of the call.
     *
     * Hint: the consultation's wording variant ("de-parteitag") does not need to be resolved here.
     * MessageSource reads it off the current consultation itself and applies it whenever the reader
     * language matches the consultation's primary language.
     */
    public static function renderInLanguage(?Consultation $consultation, string $language, callable $callback): mixed
    {
        $prevMemoized = self::$currentLanguage;
        $prevYii = \Yii::$app->language;

        self::$currentLanguage = $language;
        \Yii::$app->language = $language;

        try {
            return $callback();
        } finally {
            self::$currentLanguage = $prevMemoized;
            \Yii::$app->language = $prevYii;
        }
    }

    /**
     * Console commands and unit tests have neither a session nor an Accept-Language header to read
     * the language from.
     */
    private static function isWebRequest(): bool
    {
        return \Yii::$app instanceof \yii\web\Application;
    }

    public static function resetRequestCache(): void
    {
        self::$currentLanguage = null;
    }
}
