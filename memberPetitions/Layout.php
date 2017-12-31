<?php

namespace app\memberPetitions;

class Layout extends \app\models\settings\Layout
{
    /**
     * @param string $title
     * @return string
     */
    public function formatTitle($title)
    {
        return $title;
    }
}
