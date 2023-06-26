<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents contact page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class MotionPage extends BasePage
{
    public string|array $route = 'motion/view';

    /**
     * @return int
     */
    public function getFirstLineNumber(): int
    {
        $jscomm = 'return $(".motionTextHolder .paragraph .lineNumber").first().data("line-number")';
        return $this->actor->executeJS($jscomm);
    }
}
