<?php

namespace unit;

use app\components\yii\UrlManager;
use app\models\settings\AntragsgruenApp;
use Codeception\Specify;
use yii\web\Request;

class RoutingTest extends TestBase
{
    use Specify;

    private function resolveRequest(string $method, string $route): array {
        /** @var UrlManager $manager */
        $manager = \Yii::$app->get('urlManager');

        $request = new class($method, $route) extends Request {
            private $url;
            private $method;

            public function __construct($method, $url)
            {
                $this->url = $url;
                $this->method = $method;
                parent::__construct([]);
            }


            public function getMethod()
            {
                return $this->method;
            }
            public function getUrl()
            {
                return $this->url;
            }
        };

        return $manager->parseRequest($request);
    }

    public function testConsultationIndex()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/std-parteitag');
        $this->assertEquals([
            'consultation/index',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
            ]
        ], $resolvedRoute);
    }

    public function testMotionViewBySlug()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/std-parteitag/Testing_proposed_changes-630');
        $this->assertEquals([
            'motion/view',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug' => 'Testing_proposed_changes-630',
            ]
        ], $resolvedRoute);
    }

    public function testMotionViewById()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/std-parteitag/motion/630');
        $this->assertEquals([
            'motion/view',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug' => '630',
            ]
        ], $resolvedRoute);
    }

    public function testAmendmentViewBySlug()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/std-parteitag/Testing_proposed_changes-630/12345');
        $this->assertEquals([
            'amendment/view',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug' => 'Testing_proposed_changes-630',
                'amendmentId' => '12345',
            ]
        ], $resolvedRoute);
    }

    public function testRestSiteIndex()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/rest');
        $this->assertEquals([
            'consultation/rest-site',
            [
                'subdomain' => 'stdparteitag',
            ]
        ], $resolvedRoute);
    }

    public function testRestConsultationIndex()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/rest/std-parteitag');
        $this->assertEquals([
            'consultation/rest',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
            ]
        ], $resolvedRoute);
    }

    public function testRestConsultationWithDashIndex()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/rest/std-parteitag-2010');
        $this->assertEquals([
            'consultation/rest',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag-2010',
            ]
        ], $resolvedRoute);
    }

    public function testRestMotionViewById()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/rest/std-parteitag/motion/630');
        $this->assertEquals([
            'motion/rest',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug' => '630',
            ]
        ], $resolvedRoute);
    }

    public function testRestAmendmentViewBySlug()
    {
        $resolvedRoute = $this->resolveRequest('GET', '/stdparteitag/rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/12345');
        $this->assertEquals([
            'amendment/rest',
            [
                'subdomain' => 'stdparteitag',
                'consultationPath' => 'std-parteitag',
                'motionSlug' => 'Testing_proposed_changes-630',
                'amendmentId' => '12345',
            ]
        ], $resolvedRoute);
    }
}
