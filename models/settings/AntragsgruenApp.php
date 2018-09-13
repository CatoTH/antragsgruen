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
    public $mailFromName          = 'Antragsgrün';
    public $mailFromEmail         = '';
    public $adminUserIds          = [];
    public $siteBehaviorClasses   = [];
    public $authClientCollection  = [];
    public $blockedSubdomains     = ['www'];
    public $autoLoginDuration     = 31536000; // 1 Year
    public $xelatexPath           = null;
    public $xdvipdfmx             = null;
    public $pdfunitePath          = null;
    public $pdfExportConcat       = true;
    public $pdfExportIntegFrame   = false;
    public $localMessages         = [];
    public $imageMagickPath       = null;
    public $sitePurgeAfterDays    = null;
    public $mode                  = 'production'; // [production | sandbox]
    public $updateKey             = null;

    /** @var string[] */
    public $plugins = [];

    /** @var null|array */
    public $mailService = ['transport' => 'sendmail'];

    /**
     * @return AntragsgruenApp
     */
    public static function getInstance()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app;
    }

    /**
     * @return bool
     */
    private function isHttps()
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
    public static function flushAllCaches()
    {
        $tables = ['amendment', 'amendmentSection', 'motion', 'motionSection'];
        foreach ($tables as $table) {
            $command = \yii::$app->db->createCommand();
            $command->setSql('UPDATE `' . $table . '` SET cache = ""');
            $command->execute();
        }

        \Yii::$app->cache->flush();
    }

    /**
     * @return bool
     */
    public static function hasPhpExcel()
    {
        return class_exists('\PHPExcel', true);
    }

    /**
     * @return bool
     */
    public function isSamlActive()
    {
        return ($this->hasSaml && class_exists('\SimpleSAML_Auth_Simple'));
    }

    /**
     * @return ModuleBase[]
     */
    public function getPluginClasses()
    {
        $plugins = [];
        foreach ($this->plugins as $name) {
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

    /**
     * @return string
     */
    public function getAbsoluteResourceBase()
    {
        $url = $this->domainPlain;
        if ($url && $url[strlen($url) - 1] === '/' && $this->resourceBase[0] === '/') {
            $url = substr($url, 0, strlen($url) - 1);
        }
        $url .= $this->resourceBase;

        return $url;
    }

    /**
     * @return string
     */
    public function getTmpDir()
    {
        $dir = \Yii::$app->runtimePath . '/tmp';
        if (!file_exists($dir)) {
            mkdir($dir, 0700);
        }
        return $dir . '/';
    }
}
