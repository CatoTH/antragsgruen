<?php

namespace app\models\settings;

use app\plugins\ModuleBase;

class AntragsgruenApp implements \JsonSerializable
{
    use JsonConfigTrait;

    public $dbConnection          = null;
    public $siteSubdomain         = null;
    public $redis                 = null;
    public $prettyUrl             = true;
    public $tablePrefix           = '';
    public $resourceBase          = '/';
    public $baseLanguage          = 'en';
    public $randomSeed            = '';
    public $multisiteMode         = false;
    public $domainPlain           = 'http://antragsgruen.local/';
    public $domainSubdomain       = '';
    public $cookieDomain          = null;
    public $hasSaml               = false;
    public $samlOrgaFile          = null;
    public $prependWWWToSubdomain = true;
    public $confirmEmailAddresses = true;
    public $dataPrivacyCheckbox   = false;
    public $mailFromName          = 'Antragsgrün';
    public $mailFromEmail         = '';
    public $adminUserIds          = [];
    public $siteBehaviorClasses   = [];
    public $authClientCollection  = [];
    public $blockedSubdomains     = ['www', 'rest', 'ftp', 'smtp', 'imap'];
    public $autoLoginDuration     = 31536000; // 1 Year
    public $xelatexPath           = null; // @TODO OBSOLETE
    public $xdvipdfmx             = null; // @TODO OBSOLETE
    public $lualatexPath          = null;
    public $pdfunitePath          = null;
    public $pdfExportConcat       = true;
    public $pdfExportIntegFrame   = false;
    public $localMessages         = [];
    public $imageMagickPath       = null;
    public $sitePurgeAfterDays    = null;
    public $binaryFilePath        = null;
    public $mode                  = 'production'; // [production | sandbox]
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
            $command = \yii::$app->db->createCommand();
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
    public function getPluginNames()
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
    public function getPluginClasses()
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
    public static function getActivePlugins()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        return $params->getPluginClasses();
    }

    /**
     * @return string[]
     */
    public static function getActivePluginIds()
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
