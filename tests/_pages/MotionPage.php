<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class MotionPage extends BasePage
{
    public $route = 'motion/view';

    /**
     * @return int
     */
    public function getFirstLineNumber()
    {
        $jscomm = 'return $(".motionTextHolder .paragraph .lineNumber").first().data("line-number")';
        return $this->actor->executeJS($jscomm);
    }
}
