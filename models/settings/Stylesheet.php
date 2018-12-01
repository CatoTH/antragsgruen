<?php

namespace app\models\settings;

class Stylesheet implements \JsonSerializable
{
    use JsonConfigTrait;

    const TYPE_COLOR    = 'color';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_PIXEL    = 'pixel';
    const TYPE_NUMBER   = 'number';
    const TYPE_FONT     = 'font';

    public $bodyFont;
    public $colorLinks;
    public $colorLinksLight;
    public $colorDelLink;
    public $brandPrimary;
    public $buttonFont;
    public $buttonSuccessBackground;
    public $textColor;
    public $sidebarBackground;
    public $sidebarActionFont;
    public $createMotionBtnColor;
    public $bookmarkAmendmentBackground;
    public $bookmarkCommentColor;
    public $headingFont;
    public $headingPrimaryText;
    public $headingPrimaryBackground;
    public $headingSecondaryText;
    public $headingSecondaryBackground;
    public $headingTertiaryText;
    public $headingTertiaryBackground;
    public $linkTextDecoration;
    public $useBoxShadow;
    public $contentBorderRadius;
    public $menuActive;
    public $menuLink;
    public $menuFont;
    public $motionFixedFontColor;
    public $motionFixedFont;
    public $motionStdFontSize;

    /**
     * @return array
     */
    public static function getAllSettings()
    {
        return [
            'useBoxShadow'                => [
                'group'    => 'layout',
                'default'  => true,
                'type'     => static::TYPE_CHECKBOX,
                'scssName' => 'use-box-shadow',
            ],
            'contentBorderRadius'         => [
                'group'    => 'layout',
                'default'  => 10,
                'type'     => static::TYPE_PIXEL,
                'scssName' => 'contentBorderRadius',
            ],
            'sidebarBackground'           => [
                'group'    => 'layout',
                'default'  => '#e2007a',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'sidebarBackground',
            ],
            'sidebarActionFont'           => [
                'group'    => 'layout',
                'default'  => '"Open Sans", sans-serif',
                'type'     => static::TYPE_FONT,
                'scssName' => 'sidebarActionFont',
            ],
            'createMotionBtnColor'        => [
                'group'    => 'layout',
                'default'  => '#e2007a',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'createMotionBtnColor',
            ],
            'bookmarkAmendmentBackground' => [
                'group'    => 'layout',
                'default'  => '#afcb08',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'bookmarkAmendmentBackground',
            ],
            'bookmarkCommentColor'        => [
                'group'    => 'layout',
                'default'  => '#e2007a',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'bookmarkCommentColor',
            ],
            'headingFont'                 => [
                'group'    => 'layout',
                'default'  => '"Open Sans", sans-serif',
                'type'     => static::TYPE_FONT,
                'scssName' => 'headingFont',
            ],
            'headingPrimaryText'          => [
                'group'    => 'layout',
                'default'  => '#ffffff',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'headingPrimaryText',
            ],
            'headingPrimaryBackground'    => [
                'group'    => 'layout',
                'default'  => '#285f19',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'headingPrimaryBackground',
            ],
            'headingSecondaryText'        => [
                'group'    => 'layout',
                'default'  => '#ffffff',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'headingSecondaryText',
            ],
            'headingSecondaryBackground'  => [
                'group'    => 'layout',
                'default'  => '#afcb08',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'headingSecondaryBackground',
            ],
            'headingTertiaryText'         => [
                'group'    => 'layout',
                'default'  => '#000000',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'headingTertiaryText',
            ],
            'headingTertiaryBackground'   => [
                'group'    => 'layout',
                'default'  => '#1b4afb',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'headingTertiaryBackground',
            ],
            'menuFont'                    => [
                'group'    => 'layout',
                'default'  => '"Open Sans", sans-serif',
                'type'     => static::TYPE_FONT,
                'scssName' => 'menuFont',
            ],
            'menuLink'                    => [
                'group'    => 'layout',
                'default'  => '#6d7e00',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'menuLink',
            ],
            'menuActive'                  => [
                'group'    => 'layout',
                'default'  => '#739b9b',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'menuActive',
            ],
            'bodyFont'                    => [
                'group'    => 'text',
                'default'  => '"Segoe UI", Frutiger, "Frutiger Linotype", "Dejavu sans", "Helvetica Neue", Helvetica, Arial, sans-serif',
                'type'     => static::TYPE_FONT,
                'scssName' => 'bodyFont',
            ],
            'textColor'                   => [
                'group'    => 'text',
                'default'  => '#484649',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'textColor',
            ],
            'colorLinks'                  => [
                'group'    => 'text',
                'default'  => '#6d7e00',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinks',
            ],
            'colorLinksLight'             => [
                'group'    => 'text',
                'default'  => '#6d7e00',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinksLight',
            ],
            'colorDelLink'                => [
                'group'    => 'text',
                'default'  => '#FF7777',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorDelLink',
            ],
            'linkTextDecoration'          => [
                'group'    => 'text',
                'default'  => false,
                'type'     => static::TYPE_CHECKBOX,
                'scssName' => 'linkTextDecoration',
            ],
            'motionFixedFont'             => [
                'group'    => 'text',
                'default'  => '"VeraMono", Consolas, Courier, sans-serif',
                'type'     => static::TYPE_FONT,
                'scssName' => 'motionFixedFont',
            ],
            'motionFixedFontColor'        => [
                'group'    => 'text',
                'default'  => '#222',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'motionFixedFontColor',
            ],
            'motionStdFontSize'           => [
                'group'    => 'text',
                'default'  => 14,
                'type'     => static::TYPE_PIXEL,
                'scssName' => 'motionStdFontSize',
            ],
            'brandPrimary'                => [
                'group'    => 'buttons',
                'default'  => '#e2007a',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'brand-primary',
            ],
            'buttonSuccessBackground'     => [
                'group'    => 'buttons',
                'default'  => '#2c882c',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'btn-success-bg',
            ],
            'buttonFont'                  => [
                'group'    => 'buttons',
                'default'  => '"Open Sans", sans-serif',
                'type'     => static::TYPE_FONT,
                'scssName' => 'buttonFont',
            ],
        ];
    }

    /**
     * Stylesheet constructor.
     * @param array $data
     */
    public function __construct($data)
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * @param string $field
     * @return string
     */
    public function getValue($field)
    {
        if ($this->$field !== null) {
            return $this->$field;
        } else {
            return static::getAllSettings()[$field]['default'];
        }
    }

    /**
     * @return string
     */
    public function toScssVariables()
    {
        $scss = '';
        foreach (static::getAllSettings() as $key => $data) {
            switch ($data['type']) {
                case static::TYPE_PIXEL:
                    $scss .= '$' . $data['scssName'] . ': ' . $this->getValue($key) . "px;\n";
                    break;
                case static::TYPE_CHECKBOX:
                    if ($key === 'linkTextDecoration') {
                        $scss .= '$linkTextDecoration: ' . ($this->getValue($key) ? 'underline' : 'none') . ";\n";
                    } else {
                        $scss .= '$' . $data['scssName'] . ': ' . ($this->getValue($key) ? 'true' : 'false') . ";\n";
                    }
                    break;
                case static::TYPE_NUMBER:
                case static::TYPE_COLOR:
                case static::TYPE_FONT:
                    $scss .= '$' . $data['scssName'] . ': ' . $this->getValue($key) . ";\n";
                    break;
            }
        }
        return $scss;
    }

    /**
     * @return string
     */
    public function getSettingsHash()
    {
        return sha1(json_encode($this));
    }
}
