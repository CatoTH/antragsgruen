<?php

namespace app\models\settings;

class Stylesheet implements \JsonSerializable
{
    use JsonConfigTrait;

    public $colorLinks                 = '#6d7e00';
    public $colorLinksLight            = '#6d7e00';
    public $primaryColor               = 'rgb(226, 0, 122)';
    public $textColor                  = 'rgb(72, 70, 73)';
    public $headingPrimaryText         = 'rgb(255, 255, 255)';
    public $headingPrimaryBackground   = 'rgb(40, 95, 25)';
    public $headingSecondaryText       = 'rgb(255, 255, 255)';
    public $headingSecondaryBackground = 'rgb(175, 203, 8)';
    public $headingTertiaryText        = 'black';
    public $headingTertiaryBackground  = 'rgb(27, 74, 251)';

    /**
     * @return string
     */
    public function getSettingsHash()
    {
        return sha1(json_encode($this));
    }
}
