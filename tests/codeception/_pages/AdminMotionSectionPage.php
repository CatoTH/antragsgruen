<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AntragsgruenAcceptenceTester|\FunctionalTester $actor
 */
class AdminMotionSectionPage extends BasePage
{
    public $route = 'admin/motion/sections';

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
        $this->actor->submitForm('.adminSectionsForm', [], 'save');
    }
}
