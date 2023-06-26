<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminMotionTypePage extends BasePage
{
    public string|array $route = 'admin/motion-type/type';

    public static string $tabularLabel = 'Angaben';
    public static string $commentsLabel = 'Kommentare';

    /**
     * @return int[]
     */
    public function getCurrentOrder(): array
    {
        return $this->actor->executeJS('return $("#sectionsList").data("sortable").toArray()');
    }

    /**
     * @param int[] $order
     */
    public function setCurrentOrder(array $order): void
    {
        $order = json_encode($order);
        $this->actor->executeJS('$("#sectionsList").data("sortable").sort(' . $order . ')');
    }

    /**
     *
     */
    public function saveForm(): void
    {
        $this->actor->submitForm('.adminTypeForm', [], 'save');
    }
}
