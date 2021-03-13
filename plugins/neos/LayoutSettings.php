<?php

namespace app\plugins\neos;

use app\models\settings\Layout;

class LayoutSettings extends Layout
{
    public function formatTitle(string $title): string
    {
        if (stripos($title, 'NEOS Antragsschmiede') === false) {
            if ($title === '') {
                $title = 'NEOS Antragsschmiede';
            } elseif ($title[strlen($title) - 1] === ')') {
                $title = substr($title, 0, strlen($title) - 1) . ', NEOS Antragsschmiede)';
            } else {
                $title .= ' (NEOS Antragsschmiede)';
            }
        }
        return $title;
    }
}
