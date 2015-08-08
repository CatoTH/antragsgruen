<?php

namespace app\tests\_pages;

use yii\codeception\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class ConsolidatedMotionViewPage extends BasePage
{
    public $route = 'motion/consolidated';

    /**
     * @var \Codeception\Actor $actor
     * @return static
     */
    public static function openStd($actor)
    {
        return ConsolidatedMotionViewPage::openBy(
            $actor,
            ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag', 'motionId' => 2]
        );
    }
}
