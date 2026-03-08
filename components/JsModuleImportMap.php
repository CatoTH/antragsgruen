<?php

declare(strict_types=1);

namespace app\components;

class JsModuleImportMap
{
   public static function getJsModulesImportMap(): string
    {
        // @TODO Scan directory and add this cachebuster for each file that has changed recently.
        // Cache the result for non-debug environments
        $map = [
            '/js/modules/frontend/LoginForm.js' => '/js/modules/frontend/LoginForm.js?cache',
        ];

        if (count($map) === 0) {
            return '';
        }

        return '<script type="importmap">{
            "imports": ' . json_encode($map) . '
        }</script>';
    }
}
