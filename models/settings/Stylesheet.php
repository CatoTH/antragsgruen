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
    public $brandPrimary;
    public $textColor;
    public $sidebarBackground;
    public $sidebarActionFont;
    public $headingPrimaryText;
    public $headingPrimaryBackground;
    public $headingSecondaryText;
    public $headingSecondaryBackground;
    public $headingTertiaryText;
    public $headingTertiaryBackground;
    public $linkTextDecoration;

    public $useBoxShadow;
    public $contentBorderRadius;

    /**
     * @return array
     */
    public static function getAllSettings()
    {
        return [
            'useBoxShadow'        => [
                'group'    => 'layout',
                'default'  => true,
                'title'    => 'Box shadows',
                'type'     => static::TYPE_CHECKBOX,
                'scssName' => 'use-box-shadow',
            ],
            'contentBorderRadius' => [
                'group'    => 'layout',
                'default'  => 10,
                'title'    => 'Content border radius (px)',
                'type'     => static::TYPE_PIXEL,
                'scssName' => 'contentBorderRadius',
            ],
            'sidebarBackground'   => [
                'group'    => 'layout',
                'default'  => '#e2007a',
                'title'    => 'Background of the motion-sidebar',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'sidebarBackground',
            ],
            'sidebarActionFont'   => [
                'group'    => 'layout',
                'default'  => '"Open Sans", sans-serif',
                'title'    => 'Font of the motion-sidebar',
                'type'     => static::TYPE_FONT,
                'scssName' => 'sidebarActionFont',
            ],
            'bodyFont'            => [
                'group'    => 'text',
                'default'  => '"Segoe UI", Frutiger, "Frutiger Linotype", "Dejavu sans", "Helvetica Neue", Helvetica, Arial, sans-serif',
                'title'    => 'Base font',
                'type'     => static::TYPE_FONT,
                'scssName' => 'bodyFont',
            ],
            'textColor'           => [
                'group'    => 'text',
                'default'  => '#484649',
                'title'    => 'Default text color',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'textColor',
            ],
            'colorLinks'          => [
                'group'    => 'text',
                'default'  => '#6d7e00',
                'title'    => 'Color of links (normal)',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinks',
            ],
            'colorLinksLight'     => [
                'group'    => 'text',
                'default'  => '#6d7e00',
                'title'    => 'Color of links (light)',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinksLight',
            ],
            'linkTextDecoration'  => [
                'group'    => 'text',
                'default'  => false,
                'title'    => 'Undelined links',
                'type'     => static::TYPE_CHECKBOX,
                'scssName' => 'linkTextDecoration',
            ],
            'brandPrimary'        => [
                'group'    => 'buttons',
                'default'  => '#e2007a',
                'title'    => 'Color of primary buttons',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'brand-primary',
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
