<?php

namespace app\models\settings;

use app\models\behavior\DefaultBehavior;

class AntragsgruenApp
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
    public $hasWurzelwerk         = false;
    public $hasSaml               = false;
    public $samlOrgaFile          = null;
    public $createNeedsWurzelwerk = false;
    public $prependWWWToSubdomain = true;
    public $pdfLogo               = '';
    public $confirmEmailAddresses = true;
    public $mailFromName          = 'Antragsgrün';
    public $mailFromEmail         = '';
    public $adminUserIds          = [];
    public $siteBehaviorClasses   = [];
    public $behaviorClass         = null;
    public $authClientCollection  = [];
    public $blockedSubdomains     = ['www'];
    public $autoLoginDuration     = 31536000; // 1 Year
    public $tmpDir                = '/tmp/';
    public $xelatexPath           = null;
    public $xdvipdfmx             = null;
    public $pdfunitePath          = null;
    public $pdfExportConcat       = true;
    public $pdfExportIntegFrame   = false;
    public $localLayouts          = [];
    public $localMessages         = [];
    public $imageMagickPath       = null;
    public $sitePurgeAfterDays    = null;
    public $mode                  = 'production'; // [production | sandbox]

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

        if ($data == '') {
            $this->resourceBase = $_SERVER['SCRIPT_NAME'];
            $this->resourceBase = str_replace('index.php', '', $this->resourceBase);
            $this->domainPlain  = ($this->isHttps() ? 'https' : 'http');
            $this->domainPlain  .= '://' . $_SERVER['HTTP_HOST'] . '/';
        }
    }

    /**
     * @return DefaultBehavior
     */
    public function getBehaviorClass()
    {
        if ($this->behaviorClass !== null) {
            return new $this->behaviorClass();
        }
        return new DefaultBehavior();
    }

    /**
     * @return null|string
     */
    public function getAbsolutePdfLogo()
    {
        if ($this->pdfLogo === '' || $this->pdfLogo === null) {
            return null;
        }
        if ($this->pdfLogo[0] == '/') {
            return $this->pdfLogo;
        }
        return \yii::$app->basePath . DIRECTORY_SEPARATOR . $this->pdfLogo;
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
        return (class_exists('\SimpleSAML_Auth_Simple') && $this->hasSaml);
    }
}
