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
     * @param string|null $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        $this->setPropertiesFromJSON($data);

        if ($data === '' || $data === null) {
            // No configuration set, e.g. in installer
            require_once(__DIR__ . '/../../components/UrlHelper.php');

            $this->resourceBase = $_SERVER['SCRIPT_NAME'];
            $this->resourceBase = str_replace('index.php', '', $this->resourceBase);
            $this->domainPlain  = UrlHelper::getCurrentScheme() . '://' . $_SERVER['HTTP_HOST'] . '/';
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
