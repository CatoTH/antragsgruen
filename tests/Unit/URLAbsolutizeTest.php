<?php

namespace unit;

use app\components\UrlHelper;
use app\models\settings\AntragsgruenApp;
use Codeception\Specify;

class URLAbsolutizeTest extends TestBase
{
    use Specify;

    /**
     */
    public function testSubdir()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $params->domainSubdomain = '';
        $params->domainPlain = 'https://antragsgruen.local/';
        $params->resourceBase = '/antragsgruen/web/';

        UrlHelper::setCurrentSite(null);
        UrlHelper::setCurrentConsultation(null);

        $absolutized = UrlHelper::absolutizeLink('/antragsgruen/web/test/');
        $this->assertEquals('https://antragsgruen.local/antragsgruen/web/test/', $absolutized);
    }
}