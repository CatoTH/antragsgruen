<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AdminMotionTypePage extends BasePage
{
    public $route = 'admin/motion/type';

    public static $tabularLabel = 'Angaben';
    public static $commentsLabel = 'Kommentare';

    /**
     * @return int[]
     */
    public function getCurrentOrder()
    {
        return $this->actor->executeJS('return $("#sectionsList").data("sortable").toArray()');
    }

    /**
     * @param int[] $order
     */
    public function setCurrentOrder($order)
    {
        $order = json_encode($order);
        $this->actor->executeJS('$("#sectionsList").data("sortable").sort(' . $order . ')');
    }

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('.adminTypeForm', [], 'save');
    }
}
