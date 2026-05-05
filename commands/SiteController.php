<?php

declare(strict_types=1);

namespace app\commands;

use app\components\yii\MessageSource;
use app\models\db\{Consultation, Site, User};
use app\models\forms\SiteCreateForm;
use app\models\settings\AntragsgruenApp;
use yii\console\{Controller, ExitCode};

/**
 * @extends Controller<\yii\console\Application>
 *
 * Bootstraps the very first site (or any additional site) on a multisite
 * installation, including its admin user, without going through the
 * interactive web installer (no need for the global config/INSTALLING flag).
 */
class SiteController extends Controller
{
    public ?string $subdomain = null;
    public ?string $title = null;
    public ?string $contact = null;
    public string $organization = '';
    public ?string $language = null;
    public ?string $password = null;

    public string $givenName = '';
    public string $familyName = '';

    /**
     * Comma-separated list of SiteCreateForm::FUNCTIONALITY_* codes.
     * 1=motions, 2=manifesto, 3=applications, 4=agenda, 5=speech_lists,
     * 6=statute_amendments, 7=votings, 8=documents.
     */
    public string $functionality = '1';

    public bool $openNow = true;
    public bool $superuser = true;
    public bool $forcePasswordChange = true;
    public bool $force = false;

    public function options($actionID): array
    {
        return match ($actionID) {
            'create' => [
                'subdomain', 'title', 'contact', 'organization', 'language',
                'password', 'givenName', 'familyName', 'functionality',
                'openNow', 'superuser', 'forcePasswordChange', 'force',
            ],
            default => [],
        };
    }

    public function optionAliases(): array
    {
        return [
            'd' => 'subdomain',
            't' => 'title',
            'c' => 'contact',
            'o' => 'organization',
            'l' => 'language',
            'p' => 'password',
        ];
    }

    /**
     * Creates a new site together with its first admin user.
     *
     * Usage:
     *   ./yii site/create admin@example.org \
     *       --title="My Organization" \
     *       --contact="My Org, Foo Street 1, foo@example.org"
     *
     * --subdomain is a single DNS label (e.g. "std", "dbbj"), not a full hostname.
     * The full URL is configured separately via "domainPlain" / "domainSubdomain"
     * in config.json. In single-site mode (multisiteMode=false), --subdomain
     * defaults to the value of "siteSubdomain" in config.json, falling back to
     * "std" — so you usually don't need to pass it.
     *
     * Optional flags:
     *   --subdomain=std              DNS label of the site (single-label, no dots)
     *   --organization=...           Display name of the organization
     *   --language=de|en|fr|...      UI / wording base language (default: configured baseLanguage)
     *   --password=...               If unset, a 16-char password is generated and printed
     *   --givenName=... --familyName=...
     *   --functionality=1,4          Comma-separated FUNCTIONALITY_* codes (default: 1 = motions only)
     *                                1=motions, 2=manifesto, 3=applications, 4=agenda,
     *                                5=speech_lists, 6=statute_amendments, 7=votings, 8=documents
     *   --openNow=0                  Create site as inactive (default: 1)
     *   --superuser=0                Don't add user to adminUserIds in config.json (default: 1)
     *   --forcePasswordChange=0      Don't force a password change on first login (default: 1)
     *   --force=1                    Skip the single-site siteSubdomain mismatch warning
     *
     * The command performs all DB writes inside a transaction and only touches
     * config.json after the DB commit. config/INSTALLING is never created or
     * read, so other instances sharing this codebase are unaffected.
     */
    public function actionCreate(string $email): int
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->stderr('Invalid email address: ' . $email . "\n");
            return ExitCode::USAGE;
        }
        if (!$this->title) {
            $this->stderr("Missing required --title\n");
            return ExitCode::USAGE;
        }
        if (!$this->contact) {
            $this->stderr("Missing required --contact\n");
            return ExitCode::USAGE;
        }

        $params = AntragsgruenApp::getInstance();

        $subdomain = $this->resolveSubdomain($params);
        if ($subdomain === null) {
            return ExitCode::USAGE;
        }

        $language = $this->language ?: $params->baseLanguage;
        if (!isset(MessageSource::getBaseLanguages()[$language])) {
            $this->stderr('Unknown language: ' . $language . "\n");
            $this->stderr('Available: ' . implode(', ', array_keys(MessageSource::getBaseLanguages())) . "\n");
            return ExitCode::USAGE;
        }

        if (User::findOne(['auth' => 'email:' . $email])) {
            $this->stderr('A user with this email already exists. Use yii user/create to attach an existing user to a different site.' . "\n");
            return ExitCode::USAGE;
        }

        $functionalityCodes = array_values(array_filter(array_map(
            static fn(string $v): int => (int)trim($v),
            explode(',', $this->functionality)
        )));
        if ($functionalityCodes === []) {
            $functionalityCodes = [SiteCreateForm::FUNCTIONALITY_MOTIONS];
        }

        $passwordWasGenerated = false;
        $password = $this->password;
        if ($password === null || $password === '') {
            $password = \Yii::$app->getSecurity()->generateRandomString(16);
            $passwordWasGenerated = true;
        }

        $forcePasswordChange = $this->forcePasswordChange;

        // Pre-flight check for the config.json mutation so we don't have to roll back the DB.
        $configFile = null;
        if ($this->superuser) {
            $configFile = $this->resolveConfigFile();
            if (!file_exists($configFile)) {
                $this->stderr(
                    'config.json not found at ' . $configFile . "\n" .
                    'Cannot persist superuser status. Re-run with --superuser=0 and set adminUserIds via your environment, ' .
                    "or create config.json first.\n"
                );
                return ExitCode::CONFIG;
            }
            if (!is_writable($configFile)) {
                $this->stderr(
                    'config.json is not writable: ' . $configFile . "\n" .
                    "Fix permissions or re-run with --superuser=0.\n"
                );
                return ExitCode::CONFIG;
            }
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $user = $this->createUser($email, $password, $forcePasswordChange);

            $form = new SiteCreateForm();
            $form->subdomain     = $subdomain;
            $form->title         = $this->title;
            $form->contact       = $this->contact;
            $form->organization  = $this->organization;
            $form->language      = $language;
            $form->openNow       = $this->openNow;
            $form->functionality = $functionalityCodes;

            $consultation = $form->create($user);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr('Failed to create site: ' . $e->getMessage() . "\n");
            return ExitCode::SOFTWARE;
        }

        if ($form->site === null) {
            $this->stderr("Internal error: site was not created.\n");
            return ExitCode::SOFTWARE;
        }
        $site = $form->site;

        $superuserPersisted = false;
        if ($this->superuser && $configFile !== null) {
            try {
                $this->appendAdminUserId($configFile, (int)$user->id);
                $superuserPersisted = true;
            } catch (\Throwable $e) {
                $this->stderr(
                    "WARNING: Site and user were created, but updating config.json failed:\n" .
                    '  ' . $e->getMessage() . "\n" .
                    'Add user id ' . $user->id . ' to "adminUserIds" in ' . $configFile . " manually.\n"
                );
            }
        }

        $this->printSummary(
            $params,
            $user,
            $site,
            $consultation,
            $password,
            $passwordWasGenerated,
            $superuserPersisted,
            $configFile
        );

        return ExitCode::OK;
    }

    /**
     * Resolves and validates the --subdomain option.
     *
     * Returns the validated single-label subdomain, or null on error
     * (in which case a message has already been written to stderr).
     */
    private function resolveSubdomain(AntragsgruenApp $params): ?string
    {
        $subdomain = $this->subdomain;

        if ($subdomain === null || $subdomain === '') {
            // In single-site mode, the wizard defaults to siteSubdomain (or "std").
            // Mirror that here so operators rarely have to think about this label.
            if (!$params->multisiteMode) {
                $subdomain = $params->siteSubdomain ?: 'std';
                $this->stdout('Using subdomain "' . $subdomain . '" (from siteSubdomain in config.json)' . "\n");
            } else {
                $this->stderr("Missing required --subdomain (multisiteMode is on, no default available)\n");
                return null;
            }
        }

        if (!preg_match('/^[A-Za-z0-9]([A-Za-z0-9\-]{0,61}[A-Za-z0-9])?$/siu', $subdomain)) {
            $this->stderr(
                '--subdomain must be a single DNS label (letters, digits, hyphens; no dots; max 63 chars).' . "\n" .
                'Got: "' . $subdomain . '"' . "\n" .
                'The full URL of the site is configured separately via "domainPlain" / "domainSubdomain" in config.json.' . "\n"
            );
            return null;
        }

        if (in_array($subdomain, $params->blockedSubdomains, true)) {
            $this->stderr(
                'Subdomain "' . $subdomain . '" is in blockedSubdomains in config.json.' . "\n"
            );
            return null;
        }

        if (Site::findOne(['subdomain' => $subdomain]) !== null) {
            $this->stderr('A site with subdomain "' . $subdomain . '" already exists.' . "\n");
            return null;
        }

        // Single-site safety net: warn (or fail) if the chosen label won't be reachable
        // through the configured siteSubdomain mapping.
        if (!$params->multisiteMode && $params->siteSubdomain && $params->siteSubdomain !== $subdomain) {
            $msg = 'Warning: --subdomain="' . $subdomain . '" does not match siteSubdomain="' .
                $params->siteSubdomain . '" in config.json.' . "\n" .
                'In single-site mode (multisiteMode=false), only the site whose "subdomain" column matches' . "\n" .
                'the "siteSubdomain" config value is reachable. The new site would not be served.' . "\n";
            if (!$this->force) {
                $this->stderr($msg . 'Pass --force=1 to create it anyway, or change one of the two values.' . "\n");
                return null;
            }
            $this->stderr($msg . 'Continuing because --force=1 was passed.' . "\n");
        }

        return $subdomain;
    }

    private function createUser(string $email, string $password, bool $forcePasswordChange): User
    {
        $givenName  = $this->givenName;
        $familyName = $this->familyName;
        if ($givenName === '' && $familyName === '') {
            $localPart = strstr($email, '@', true) ?: $email;
            $givenName = $localPart;
        }

        $user                  = new User();
        $user->auth            = 'email:' . $email;
        $user->email           = $email;
        $user->status          = User::STATUS_CONFIRMED;
        $user->emailConfirmed  = 1;
        $user->pwdEnc          = password_hash($password, PASSWORD_DEFAULT);
        $user->name            = trim($givenName . ' ' . $familyName);
        $user->nameGiven       = $givenName;
        $user->nameFamily      = $familyName;
        $user->organization    = $this->organization;
        $user->organizationIds = '';
        $user->fixedData       = 0;
        $user->dateCreation    = date('Y-m-d H:i:s');

        if ($forcePasswordChange) {
            $settings = $user->getSettingsObj();
            $settings->forcePasswordChange = true;
            $user->setSettingsObj($settings);
        }

        if (!$user->save()) {
            throw new \RuntimeException('Could not save user: ' . json_encode($user->getErrors()));
        }

        return $user;
    }

    private function resolveConfigFile(): string
    {
        if (isset($_SERVER['ANTRAGSGRUEN_CONFIG']) && $_SERVER['ANTRAGSGRUEN_CONFIG'] !== '') {
            return (string)$_SERVER['ANTRAGSGRUEN_CONFIG'];
        }
        if (isset($_ENV['ANTRAGSGRUEN_CONFIG']) && $_ENV['ANTRAGSGRUEN_CONFIG'] !== '') {
            return (string)$_ENV['ANTRAGSGRUEN_CONFIG'];
        }
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
    }

    /**
     * Append the user id to "adminUserIds" in the active config.json,
     * preserving every other key. Writes atomically (temp file + rename).
     */
    private function appendAdminUserId(string $configFile, int $userId): void
    {
        $raw = file_get_contents($configFile);
        if ($raw === false) {
            throw new \RuntimeException('Could not read config file: ' . $configFile);
        }

        $data = AntragsgruenApp::decodeJson5($raw);
        if (!is_array($data)) {
            throw new \RuntimeException('Could not decode config file as JSON: ' . $configFile);
        }

        $existing = $data['adminUserIds'] ?? [];
        if (!is_array($existing)) {
            $existing = [];
        }
        $existing = array_values(array_map('intval', $existing));
        if (!in_array($userId, $existing, true)) {
            $existing[] = $userId;
        }
        $data['adminUserIds'] = $existing;

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $tmp = $configFile . '.tmp.' . getmypid();
        if (file_put_contents($tmp, $encoded . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('Could not write temp config file: ' . $tmp);
        }
        // Preserve original permissions (best effort).
        $perms = @fileperms($configFile);
        if ($perms !== false) {
            @chmod($tmp, $perms & 0777);
        }
        if (!@rename($tmp, $configFile)) {
            @unlink($tmp);
            throw new \RuntimeException('Could not move temp config file into place: ' . $configFile);
        }
    }

    private function printSummary(
        AntragsgruenApp $params,
        User $user,
        Site $site,
        Consultation $consultation,
        string $password,
        bool $passwordWasGenerated,
        bool $superuserPersisted,
        ?string $configFile
    ): void {
        if ($params->domainSubdomain) {
            $siteUrl = str_replace('<subdomain:[\\w_-]+>', (string)$site->subdomain, $params->domainSubdomain);
        } elseif ($params->domainPlain) {
            $siteUrl = $params->domainPlain;
        } else {
            $siteUrl = '(set domainPlain or domainSubdomain in config.json)';
        }

        $this->stdout("\nSite created.\n");
        $this->stdout("---------------------------------------------\n");
        $this->stdout('Site id           : ' . $site->id . "\n");
        $this->stdout('Subdomain         : ' . $site->subdomain . "\n");
        $this->stdout('Site URL          : ' . $siteUrl . "\n");
        $this->stdout('Consultation id   : ' . $consultation->id . "\n");
        $this->stdout('Consultation path : ' . $consultation->urlPath . "\n");
        $this->stdout("---------------------------------------------\n");
        $this->stdout('Admin user id     : ' . $user->id . "\n");
        $this->stdout('Admin email       : ' . $user->email . "\n");
        $this->stdout('Admin auth        : ' . $user->auth . "\n");
        if ($this->superuser) {
            if ($superuserPersisted) {
                $this->stdout('Superuser         : yes (added to adminUserIds in ' . $configFile . ')' . "\n");
            } else {
                $this->stdout('Superuser         : NOT persisted -- add ' . $user->id . ' to adminUserIds manually' . "\n");
            }
        } else {
            $this->stdout('Superuser         : no (per --superuser=0; site-admin only via the site user group)' . "\n");
        }
        if ($passwordWasGenerated) {
            $this->stdout("---------------------------------------------\n");
            $this->stdout("Generated password (store it now, it is not recoverable):\n");
            $this->stdout('PASSWORD=' . $password . "\n");
            $this->stdout("The user will be required to change it on first login.\n");
        }
        $this->stdout("---------------------------------------------\n");
    }
}
