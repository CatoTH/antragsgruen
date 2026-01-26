<?php

namespace app\models\settings;

use app\components\UrlHelper;
use app\plugins\ModuleBase;

class AntragsgruenApp implements \JsonSerializable
{
    use JsonConfigTrait;

    public const CAPTCHA_MODE_NEVER = 'never';
    public const CAPTCHA_MODE_THROTTLE = 'throttle';
    public const CAPTCHA_MODE_ALWAYS = 'always';

    public const CAPTCHA_DIFFICULTY_EASY = 'easy';
    public const CAPTCHA_DIFFICULTY_MEDIUM = 'medium';

    public ?array $dbConnection = null;
    public ?string $siteSubdomain = null;
    public ?array $redis = null;
    public bool $prettyUrl = true;
    public string $tablePrefix = '';
    public string $resourceBase = '/';
    public string $baseLanguage = 'en';
    public string $randomSeed = '';
    public bool $multisiteMode = false;
    public string $domainPlain = 'http://antragsgruen.local/';
    public ?string $domainSubdomain = null;
    public ?string $cookieDomain = null;
    public bool $hasSaml = false;
    public bool $prependWWWToSubdomain = true;
    public bool $allowRegistration = true;
    public bool $allowAccountDeletion = true;
    public bool $confirmEmailAddresses = true;
    public bool $enforceTwoFactorAuthentication = false;
    public bool $dataPrivacyCheckbox = false;
    public string $mailFromName = 'Antragsgrün';
    public string $mailFromEmail = '';
    public ?string $mailDefaultReplyTo = null;
    /** @var int[] */
    public array $adminUserIds = [];
    /** @var string[] */
    public array $authClientCollection = [];
    /** @var string[] */
    public array $blockedSubdomains = ['www', 'rest', 'ftp', 'smtp', 'imap'];
    public int $autoLoginDuration = 31536000; // 1 Year
    public ?string $xelatexPath = null; // @TODO OBSOLETE
    public ?string $xdvipdfmx = null; // @TODO OBSOLETE
    public ?string $lualatexPath = null;
    public ?string $weasyprintPath = null;
    public ?string $pdfunitePath = null;
    public bool $pdfExportConcat = true;
    public mixed $pdfExportIntegFrame = false; // Type: mixed, can be ether int or array
    public array $localMessages = [];
    public ?string $imageMagickPath = null;
    public ?int $sitePurgeAfterDays = null;
    public ?string $binaryFilePath = null;
    public ?string $viewCacheFilePath = null; // If set, then view caches are saved to a separate directory, overriding the default and not using Redis
    public string $mode = 'production'; // [production | sandbox]
    public ?string $updateKey = null;
    public ?string $jwtPrivateKey = null;
    public ?string $healthCheckKey = null; // A hash generated with password_hash(..., PASSWORD_DEFAULT)

    /** @var array{mode: string, ignoredIps: string[], difficulty: string} */
    public array $captcha = [
        'mode' => self::CAPTCHA_MODE_THROTTLE,
        'ignoredIps' => [],
        'difficulty' => self::CAPTCHA_DIFFICULTY_MEDIUM,
    ];

    /** @var array<class-string<ModuleBase>> */
    protected array $plugins = [];

    /** @var array<array<class-string<ModuleBase>>> */
    protected array $sitePlugins = [];

    public array $mailService = ['transport' => 'sendmail'];

    /** @var array{installationId: string, wsUri: string, stompJsUri: string, rabbitMqUri: string, rabbitMqExchangeName: string, rabbitMqUsername: string, rabbitMqPassword: string}|null */
    public ?array $live = null;

    /** @var array{notifications?: bool}|null */
    public ?array $backgroundJobs = null;

    public static function getInstance(): AntragsgruenApp
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app;
    }

    private function isHttps(): bool
    {
        // Needs to be equal to Yii2's web/Request.php
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }

    /**
     * Constructor - loads configuration from JSON and/or environment variables
     * 
     * Configuration loading follows this precedence (highest to lowest):
     * 1. config.json values (if provided)
     * 2. Environment variables (fallback)
     * 3. Installer mode defaults (if no config at all)
     * 
     * This allows backwards compatibility with config.json while supporting
     * modern containerized deployments using environment variables.
     * 
     * @param string|null $data JSON configuration string
     * @throws \Exception
     * @see EnvironmentConfigLoader
     */
    public function __construct($data)
    {
        // Phase 1: Load from JSON if provided (backwards compatibility)
        if ($data && $data !== '{}') {
            $this->setPropertiesFromJSON($data);
        }

        // Phase 2: Apply environment variable configuration
        // Only sets values that are not already configured from JSON
        $this->applyEnvironmentConfig();

        // Phase 3: Installer mode fallback (no config at all)
        if (!$this->hasMinimalConfig()) {
            $this->applyInstallerDefaults();
        }
    }

    /**
     * Apply configuration from environment variables
     *
     * This method loads configuration from environment variables using the
     * EnvironmentConfigLoader. It only sets values that haven't been
     * configured from config.json, ensuring config.json takes precedence.
     *
     * @see EnvironmentConfigLoader
     */
    private function applyEnvironmentConfig(): void
    {
        // Load environment config loader
        require_once(__DIR__ . '/EnvironmentConfigLoader.php');

        // Database configuration
        if ($this->dbConnection === null) {
            $dbConfig = EnvironmentConfigLoader::getDatabaseConfig();
            if ($dbConfig !== null) {
                $this->dbConnection = $dbConfig;
            }
        }

        // Redis configuration
        if ($this->redis === null) {
            $redisConfig = EnvironmentConfigLoader::getRedisConfig();
            if ($redisConfig !== null) {
                $this->redis = $redisConfig;
            }
        }

        // Mail service configuration
        // Check if still at default value
        if ($this->mailService === ['transport' => 'sendmail']) {
            $mailConfig = EnvironmentConfigLoader::getMailServiceConfig();
            if ($mailConfig !== null) {
                $this->mailService = $mailConfig;
            }
        }

        // Application-level configuration
        $appConfig = EnvironmentConfigLoader::getApplicationConfig();
        foreach ($appConfig as $key => $value) {
            // Only apply if property exists and is at default value
            if (property_exists($this, $key) && $this->isPropertyAtDefaultValue($key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Check if a property is at its default value
     *
     * This is used to determine if environment variables should override
     * the current value. If a property is at its default, it means it
     * wasn't explicitly set in config.json.
     *
     * @param string $key Property name
     * @return bool True if property is at default value
     */
    private function isPropertyAtDefaultValue(string $key): bool
    {
        $currentValue = $this->$key;

        // Map of property names to their default values
        $defaults = [
            'domainPlain' => 'http://antragsgruen.local/',
            'baseLanguage' => 'en',
            'resourceBase' => '/',
            'tablePrefix' => '',
            'mailFromName' => 'Antragsgrün',
            'mailFromEmail' => '',
            'prependWWWToSubdomain' => true,
            'allowRegistration' => true,
            'confirmEmailAddresses' => true,
            'multisiteMode' => false,
        ];

        // Check against known defaults
        if (array_key_exists($key, $defaults)) {
            return $currentValue === $defaults[$key];
        }

        // For properties not in the defaults map, check if null or empty string
        return $currentValue === null || $currentValue === '';
    }

    /**
     * Check if minimal required configuration is present
     * 
     * Minimal config requires either:
     * - Database connection configured, OR
     * - We're in installer mode (no config needed yet)
     * 
     * @return bool True if sufficient config exists
     */
    private function hasMinimalConfig(): bool
    {
        // If we have database config, we're good
        if ($this->dbConnection !== null) {
            return true;
        }

        // Check if installer mode is active
        $installingFile = __DIR__ . '/../../config/INSTALLING';
        if (file_exists($installingFile)) {
            return false; // Installer needs defaults
        }

        // If we got here, we don't have config but also not installing
        // This is okay - might be running installer for first time
        return false;
    }

    /**
     * Apply defaults for installer mode
     * 
     * When no configuration is available (no config.json and no environment
     * variables), apply sensible defaults for the web-based installer.
     */
    private function applyInstallerDefaults(): void
    {
        require_once(__DIR__ . '/../../components/UrlHelper.php');

        $this->resourceBase = $_SERVER['SCRIPT_NAME'] ?? '/';
        $this->resourceBase = str_replace('index.php', '', $this->resourceBase);

        // Only set domainPlain if it's still at the default value
        if ($this->domainPlain === 'http://antragsgruen.local/') {
            $this->domainPlain = UrlHelper::getCurrentScheme() . '://' .
                                ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/';
        }
    }

    public function setCaptcha(?array $captcha): void
    {
        if (!is_array($captcha)) {
            return;
        }
        if (isset($captcha['mode'])) {
            if (!in_array($captcha['mode'], [self::CAPTCHA_MODE_NEVER, self::CAPTCHA_MODE_THROTTLE, self::CAPTCHA_MODE_ALWAYS], true)) {
                throw new \Exception('Invalid captcha mode setting');
            }
            $this->captcha['mode'] = $captcha['mode'];
        }
        if (isset($captcha['difficulty'])) {
            if (!in_array($captcha['difficulty'], [self::CAPTCHA_DIFFICULTY_EASY, self::CAPTCHA_DIFFICULTY_MEDIUM], true)) {
                throw new \Exception('Invalid captcha difficulty setting');
            }
            $this->captcha['difficulty'] = $captcha['difficulty'];
        }
        if (isset($captcha['ignoredIps']) && is_array($captcha['ignoredIps'])) {
            $this->captcha['ignoredIps'] = $captcha['ignoredIps'];
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public static function flushAllCaches(): void
    {
        $tables = ['amendment', 'amendmentSection', 'motion', 'motionSection'];
        foreach ($tables as $table) {
            $command = \Yii::$app->db->createCommand();
            $command->setSql('UPDATE `' . $table . '` SET cache = ""');
            $command->execute();
        }

        \Yii::$app->cache->flush();
    }

    public function isSamlActive(): bool
    {
        return ($this->hasSaml && class_exists('\SimpleSAML\Auth\Simple'));
    }

    /**
     * @return array<class-string<ModuleBase>>
     */
    public function getPluginNames(): array
    {
        $names = $this->plugins;
        $currSubdomain = (isset($_SERVER['HTTP_HOST']) ? explode('.', $_SERVER['HTTP_HOST'])[0] : '');
        if ($this->multisiteMode && count($this->sitePlugins) && isset($this->sitePlugins[$currSubdomain])) {
            foreach ($this->sitePlugins[$currSubdomain] as $name) {
                $names[] = $name;
            }
        }
        /** @var array<class-string<ModuleBase>> $plugins */
        $plugins = array_unique($names);

        return $plugins;
    }

    /**
     * @return array<string, class-string<ModuleBase>>
     */
    public function getPluginClasses(): array
    {
        $plugins = [];
        foreach ($this->getPluginNames() as $name) {
            $plugins[$name] = 'app\\plugins\\' . $name . '\\Module';
        }
        /** @var array<class-string<ModuleBase>> $plugins */
        return $plugins;
    }

    /**
     * @return array<string, class-string<ModuleBase>>
     */
    public static function getActivePlugins(): array
    {
        return AntragsgruenApp::getInstance()->getPluginClasses();
    }

    /**
     * @return string[]
     */
    public static function getActivePluginIds(): array
    {
        return array_keys(static::getActivePlugins());
    }

    public function getAbsoluteResourceBase(): string
    {
        $url = $this->domainPlain;
        if ($url && $url[strlen($url) - 1] === '/' && $this->resourceBase[0] === '/') {
            $url = substr($url, 0, strlen($url) - 1);
        }
        $url .= $this->resourceBase;

        return $url;
    }

    public function getTmpDir(): string
    {
        $dir = \Yii::$app->runtimePath . '/tmp';
        if (!file_exists($dir)) {
            mkdir($dir, 0700);
        }
        return $dir . '/';
    }
}
