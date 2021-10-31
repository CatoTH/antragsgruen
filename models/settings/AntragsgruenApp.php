<?php

namespace app\models\settings;

use app\plugins\ModuleBase;

class AntragsgruenApp implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var null|array */
    public $dbConnection          = null;
    /** @var null|string */
    public $siteSubdomain         = null;
    /** @var null|array */
    public $redis                 = null;
    /** @var bool */
    public $prettyUrl             = true;
    /** @var string */
    public $tablePrefix           = '';
    /** @var string */
    public $resourceBase          = '/';
    /** @var string */
    public $baseLanguage          = 'en';
    /** @var string */
    public $randomSeed            = '';
    /** @var bool */
    public $multisiteMode         = false;
    /** @var string */
    public $domainPlain           = 'http://antragsgruen.local/';
    /** @var string */
    public $domainSubdomain       = '';
    /** @var null|string */
    public $cookieDomain          = null;
    /** @var bool */
    public $hasSaml               = false;
    /** @var string */
    public $samlOrgaFile          = null;
    /** @var bool */
    public $prependWWWToSubdomain = true;
    /** @var bool */
    public $confirmEmailAddresses = true;
    /** @var bool */
    public $dataPrivacyCheckbox   = false;
    /** @var string */
    public $mailFromName          = 'Antragsgrün';
    /** @var string */
    public $mailFromEmail         = '';
    /** @var int[] */
    public $adminUserIds          = [];
    /** @var string[] */
    public $siteBehaviorClasses   = [];
    /** @var string[] */
    public $authClientCollection  = [];
    /** @var string[] */
    public $blockedSubdomains     = ['www', 'rest', 'ftp', 'smtp', 'imap'];
    /** @var int */
    public $autoLoginDuration     = 31536000; // 1 Year
    /** @var bool */
    public $loginCaptcha          = false; // Forces captcha even at the first login attempt
    /** @var null|string */
    public $xelatexPath           = null; // @TODO OBSOLETE
    /** @var null|string */
    public $xdvipdfmx             = null; // @TODO OBSOLETE
    /** @var null|string */
    public $lualatexPath          = null;
    /** @var null|string */
    public $pdfunitePath          = null;
    /** @var bool */
    public $pdfExportConcat       = true;
    /** @var bool */
    public $pdfExportIntegFrame   = false;
    /** @var array */
    public $localMessages         = [];
    /** @var null|string */
    public $imageMagickPath       = null;
    /** @var null|int */
    public $sitePurgeAfterDays    = null;
    /** @var null|string */
    public $binaryFilePath        = null;
    /** @var null|string */
    public $viewCacheFilePath     = null; // If set, then view caches are saved to a separate directory, overriding the default and not using Redis
    /** @var string */
    public $mode                  = 'production'; // [production | sandbox]
    /** @var null|string */
    public $updateKey             = null;

    /** @var string[] */
    protected $plugins = [];

    /** @var string[][] */
    protected $sitePlugins = [];

    /** @var null|array */
    public $mailService = ['transport' => 'sendmail'];

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
            $this->resourceBase = $_SERVER['SCRIPT_NAME'];
            $this->resourceBase = str_replace('index.php', '', $this->resourceBase);
            $this->domainPlain  = ($this->isHttps() ? 'https' : 'http');
            $this->domainPlain  .= '://' . $_SERVER['HTTP_HOST'] . '/';
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

    public static function hasPhpExcel(): bool
    {
        return class_exists('\PHPExcel', true);
    }

    public function isSamlActive(): bool
    {
        return ($this->hasSaml && class_exists('\SimpleSAML\Auth\Simple'));
    }

    /**
     * @return string[]
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
        return array_unique($names);
    }

    /**
     * @return ModuleBase[]
     */
    public function getPluginClasses(): array
    {
        $plugins = [];
        foreach ($this->getPluginNames() as $name) {
            $plugins[$name] = 'app\\plugins\\' . $name . '\\Module';
        }
        return $plugins;
    }

    /**
     * @return ModuleBase[]
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
