<?php

declare(strict_types=1);

namespace app\components;

/**
 * HTML generation helpers, replacing yii\helpers\Html.
 *
 * Only the subset actually used by Antragsgrün is implemented. The generated markup is identical to Yii's
 * (including the order of attributes), so this class can be swapped in without affecting the output.
 * Framework-specific bits (the CSRF token, the current URL) are taken from RequestContext.
 */
final class Html
{
    /** Attributes are rendered in this order first; all others follow in the order they are given. */
    private const ATTRIBUTE_ORDER = [
        'type', 'id', 'class', 'name', 'value', 'href', 'src', 'srcset', 'form', 'action', 'method', 'selected',
        'checked', 'readonly', 'disabled', 'multiple', 'size', 'maxlength', 'width', 'height', 'rows', 'cols',
        'alt', 'title', 'rel', 'media',
    ];

    /** Array values of these attributes are expanded into one attribute per entry, e.g. 'data' => ['id' => 1] to data-id="1" */
    private const DATA_ATTRIBUTES = ['aria', 'data', 'data-ng', 'ng'];

    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param',
        'source', 'track', 'wbr',
    ];

    public static function encode(mixed $content, bool $doubleEncode = true): string
    {
        return htmlspecialchars((string)$content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    public static function tag(string $name, string $content = '', array $options = []): string
    {
        $html = '<' . $name . self::renderTagAttributes($options) . '>';

        return in_array(strtolower($name), self::VOID_ELEMENTS, true) ? $html : $html . $content . '</' . $name . '>';
    }

    /**
     * @param string|null $url Used as-is; an empty string links to the current URL. Null renders no href.
     */
    public static function a(string $text, ?string $url = null, array $options = []): string
    {
        if ($url !== null) {
            $options['href'] = ($url === '' ? RequestContext::getCurrentUrl() : $url);
        }

        return self::tag('a', $text, $options);
    }

    /**
     * Renders the opening form tag, including the CSRF token for POST forms.
     * Set the option 'csrf' to false to omit the token.
     *
     * @param string $action An empty string submits to the current URL.
     * @param string $method Only "get" and "post" are supported (case-insensitive).
     */
    public static function beginForm(string $action = '', string $method = 'post', array $options = []): string
    {
        if ($action === '') {
            $action = RequestContext::getCurrentUrl();
        }
        if (strcasecmp($method, 'get') !== 0 && strcasecmp($method, 'post') !== 0) {
            throw new \InvalidArgumentException('Unsupported form method: ' . $method);
        }

        $hiddenInputs = [];

        $csrf = $options['csrf'] ?? true;
        unset($options['csrf']);
        if ($csrf && strcasecmp($method, 'post') === 0) {
            $csrfField = RequestContext::getCsrfFormField();
            if ($csrfField !== null) {
                $hiddenInputs[] = self::hiddenInput($csrfField['param'], $csrfField['token']);
            }
        }

        if (strcasecmp($method, 'get') === 0 && ($pos = strpos($action, '?')) !== false) {
            // Browsers drop the query string of the action of GET forms, so the parameters are added as hidden fields
            foreach (explode('&', substr($action, $pos + 1)) as $pair) {
                if (($pos1 = strpos($pair, '=')) !== false) {
                    $hiddenInputs[] = self::hiddenInput(urldecode(substr($pair, 0, $pos1)), urldecode(substr($pair, $pos1 + 1)));
                } else {
                    $hiddenInputs[] = self::hiddenInput(urldecode($pair), '');
                }
            }
            $action = substr($action, 0, $pos);
        }

        $options['action'] = $action;
        $options['method'] = $method;
        $form = '<form' . self::renderTagAttributes($options) . '>';
        if (count($hiddenInputs) > 0) {
            $form .= "\n" . implode("\n", $hiddenInputs);
        }

        return $form;
    }

    public static function endForm(): string
    {
        return '</form>';
    }

    public static function csrfMetaTags(): string
    {
        $csrfField = RequestContext::getCsrfFormField();
        if ($csrfField === null) {
            return '';
        }

        return self::tag('meta', '', ['name' => 'csrf-param', 'content' => $csrfField['param']]) . "\n"
            . self::tag('meta', '', ['name' => 'csrf-token', 'content' => $csrfField['token']]) . "\n";
    }

    public static function input(string $type, ?string $name = null, mixed $value = null, array $options = []): string
    {
        if (!isset($options['type'])) {
            $options['type'] = $type;
        }
        $options['name'] = $name;
        $options['value'] = ($value === null ? null : (string)$value);

        return self::tag('input', '', $options);
    }

    public static function textInput(?string $name, mixed $value = null, array $options = []): string
    {
        return self::input('text', $name, $value, $options);
    }

    public static function hiddenInput(?string $name, mixed $value = null, array $options = []): string
    {
        return self::input('hidden', $name, $value, $options);
    }

    /**
     * Option 'doubleEncode' (default true): whether to encode HTML entities already contained in the value.
     */
    public static function textarea(string $name, mixed $value = '', array $options = []): string
    {
        $options['name'] = $name;
        $doubleEncode = $options['doubleEncode'] ?? true;
        unset($options['doubleEncode']);

        return self::tag('textarea', self::encode($value, $doubleEncode), $options);
    }

    /**
     * Special options:
     * - checked: takes precedence over $checked
     * - value: defaults to "1"
     * - uncheck: if set, a hidden field with this value is added, so a value is also submitted when unchecked
     * - label / labelOptions: wraps the input in a label tag (the label is not encoded)
     */
    public static function checkbox(string $name, mixed $checked = false, array $options = []): string
    {
        return self::booleanInput('checkbox', $name, $checked, $options);
    }

    /**
     * @see checkbox() for the special options
     */
    public static function radio(string $name, mixed $checked = false, array $options = []): string
    {
        return self::booleanInput('radio', $name, $checked, $options);
    }

    private static function booleanInput(string $type, string $name, mixed $checked, array $options): string
    {
        if (!isset($options['checked'])) {
            $options['checked'] = (bool)$checked;
        }
        $value = array_key_exists('value', $options) ? $options['value'] : '1';

        $hidden = '';
        if (isset($options['uncheck'])) {
            $hiddenOptions = [];
            if (isset($options['form'])) {
                $hiddenOptions['form'] = $options['form'];
            }
            if (!empty($options['disabled'])) {
                // A disabled input must not submit a value
                $hiddenOptions['disabled'] = $options['disabled'];
            }
            $hidden = self::hiddenInput($name, $options['uncheck'], $hiddenOptions);
            unset($options['uncheck']);
        }

        if (isset($options['label'])) {
            $label = $options['label'];
            $labelOptions = $options['labelOptions'] ?? [];
            unset($options['label'], $options['labelOptions']);
            $labelOptions['for'] = null;

            return $hidden . self::tag('label', self::input($type, $name, $value, $options) . ' ' . $label, $labelOptions);
        }

        return $hidden . self::input($type, $name, $value, $options);
    }

    /**
     * $items maps option values to labels; a nested array renders an optgroup labeled by its key.
     *
     * Special options:
     * - prompt: string, or ['text' => string, 'options' => array], rendered as the first option with an empty value
     * - options: attributes for the individual options, indexed by option value
     * - groups: attributes for the optgroups, indexed by group key
     * - encode (default true) / encodeSpaces (default false): HTML encoding of the labels
     * - strict (default false): compare the selection to the option values with strict string comparison
     * - multiple / size / unselect: renders a list box instead (unselect: value of a hidden field submitted
     *   when nothing is selected)
     */
    public static function dropDownList(string $name, mixed $selection = null, array $items = [], array $options = []): string
    {
        if (!empty($options['multiple'])) {
            return self::listBox($name, $selection, $items, $options);
        }
        $options['name'] = $name;
        unset($options['unselect']);
        $selectOptions = self::renderSelectOptions($selection, $items, $options);

        return self::tag('select', "\n" . $selectOptions . "\n", $options);
    }

    private static function listBox(string $name, mixed $selection, array $items, array $options): string
    {
        if (!array_key_exists('size', $options)) {
            $options['size'] = 4;
        }
        if ($name !== '' && !str_ends_with($name, '[]')) {
            $name .= '[]';
        }
        $options['name'] = $name;

        $hidden = '';
        if (isset($options['unselect'])) {
            $hiddenOptions = [];
            if (!empty($options['disabled'])) {
                $hiddenOptions['disabled'] = $options['disabled'];
            }
            $hidden = self::hiddenInput(substr($name, 0, -2), $options['unselect'], $hiddenOptions);
            unset($options['unselect']);
        }
        $selectOptions = self::renderSelectOptions($selection, $items, $options);

        return $hidden . self::tag('select', "\n" . $selectOptions . "\n", $options);
    }

    /**
     * $items maps the values of the radio buttons to their labels.
     *
     * Special options:
     * - item: callable(int $index, string $label, string $name, bool $checked, string|int $value): string to render each item
     * - itemOptions: options passed to radio() for each item
     * - encode (default true): HTML-encode the labels
     * - separator (default "\n"), tag (default "div"; false for no container)
     * - strict (default false): use strict comparison when $selection is an array
     * - unselect: value of a hidden field submitted when nothing is selected
     */
    public static function radioList(string $name, mixed $selection = null, array $items = [], array $options = []): string
    {
        $selection = self::normalizeSelection($selection);
        $formatter = $options['item'] ?? null;
        $itemOptions = $options['itemOptions'] ?? [];
        $encode = $options['encode'] ?? true;
        $separator = $options['separator'] ?? "\n";
        $tag = $options['tag'] ?? 'div';
        $strict = $options['strict'] ?? false;
        unset($options['item'], $options['itemOptions'], $options['encode'], $options['separator'], $options['tag'], $options['strict']);

        $hidden = '';
        if (isset($options['unselect'])) {
            $hiddenOptions = [];
            if (!empty($options['disabled'])) {
                $hiddenOptions['disabled'] = $options['disabled'];
            }
            $hidden = self::hiddenInput($name, $options['unselect'], $hiddenOptions);
            unset($options['unselect'], $options['disabled']);
        }

        $lines = [];
        $index = 0;
        foreach ($items as $value => $label) {
            if (is_array($selection)) {
                $checked = in_array((string)$value, $selection, $strict);
            } else {
                $checked = $selection !== null && (string)$value === (string)$selection;
            }
            if ($formatter !== null) {
                $lines[] = $formatter($index, $label, $name, $checked, $value);
            } else {
                $lines[] = self::radio($name, $checked, array_merge([
                    'value' => $value,
                    'label' => $encode ? self::encode($label) : $label,
                ], $itemOptions));
            }
            $index++;
        }

        $visibleContent = implode($separator, $lines);
        if ($tag === false) {
            return $hidden . $visibleContent;
        }

        return $hidden . self::tag($tag, $visibleContent, $options);
    }

    /**
     * @return string[]|string|int|float|null
     */
    private static function normalizeSelection(mixed $selection): array|string|int|float|null
    {
        if ($selection instanceof \Traversable) {
            $selection = iterator_to_array($selection);
        }
        if (is_array($selection)) {
            return array_map(fn ($item): string => is_bool($item) ? ($item ? '1' : '0') : (string)$item, $selection);
        }
        if (is_bool($selection)) {
            return $selection ? '1' : '0';
        }

        /** @var string|int|float|null $selection */
        return $selection;
    }

    /**
     * Removes the select-specific entries (prompt, options, groups, encode, encodeSpaces, strict) from $tagOptions.
     */
    private static function renderSelectOptions(mixed $selection, array $items, array &$tagOptions): string
    {
        $selection = self::normalizeSelection($selection);
        $encodeSpaces = $tagOptions['encodeSpaces'] ?? false;
        $encode = $tagOptions['encode'] ?? true;
        $strict = $tagOptions['strict'] ?? false;
        unset($tagOptions['encodeSpaces'], $tagOptions['encode'], $tagOptions['strict']);

        $lines = [];
        if (isset($tagOptions['prompt'])) {
            $promptOptions = ['value' => ''];
            if (is_string($tagOptions['prompt'])) {
                $promptText = $tagOptions['prompt'];
            } else {
                $promptText = $tagOptions['prompt']['text'];
                $promptOptions = array_merge($promptOptions, $tagOptions['prompt']['options']);
            }
            $promptText = ($encode ? self::encode($promptText) : $promptText);
            if ($encodeSpaces) {
                $promptText = str_replace(' ', '&nbsp;', $promptText);
            }
            $lines[] = self::tag('option', $promptText, $promptOptions);
        }

        $options = $tagOptions['options'] ?? [];
        $groups = $tagOptions['groups'] ?? [];
        unset($tagOptions['prompt'], $tagOptions['options'], $tagOptions['groups']);

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $groupAttrs = $groups[$key] ?? [];
                if (!isset($groupAttrs['label'])) {
                    $groupAttrs['label'] = $key;
                }
                $attrs = ['options' => $options, 'groups' => $groups, 'encodeSpaces' => $encodeSpaces, 'encode' => $encode, 'strict' => $strict];
                $content = self::renderSelectOptions($selection, $value, $attrs);
                $lines[] = self::tag('optgroup', "\n" . $content . "\n", $groupAttrs);
            } else {
                $attrs = $options[$key] ?? [];
                $attrs['value'] = (string)$key;
                if (!array_key_exists('selected', $attrs)) {
                    $selected = false;
                    if (is_array($selection)) {
                        $selected = in_array((string)$key, $selection, $strict);
                    } elseif ($selection !== null) {
                        if ($key === '' || $selection === '') {
                            $selected = ($selection === $key);
                        } elseif ($strict) {
                            $selected = ((string)$key === (string)$selection);
                        } else {
                            $selected = ($selection == $key);
                        }
                    }
                    $attrs['selected'] = $selected;
                }
                $text = ($encode ? self::encode($value) : (string)$value);
                if ($encodeSpaces) {
                    $text = str_replace(' ', '&nbsp;', $text);
                }
                $lines[] = self::tag('option', $text, $attrs);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * - true renders a boolean attribute, false and null omit the attribute
     * - 'class' and 'style' may be arrays (style: property => value)
     * - array values of data attributes (see DATA_ATTRIBUTES) are expanded, other arrays are JSON-encoded
     */
    public static function renderTagAttributes(array $attributes): string
    {
        if (count($attributes) > 1) {
            $sorted = [];
            foreach (self::ATTRIBUTE_ORDER as $name) {
                if (isset($attributes[$name])) {
                    $sorted[$name] = $attributes[$name];
                }
            }
            $attributes = array_merge($sorted, $attributes);
        }

        $html = '';
        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= ' ' . $name;
                }
            } elseif (is_array($value)) {
                if (in_array($name, self::DATA_ATTRIBUTES, true)) {
                    foreach ($value as $n => $v) {
                        if (is_array($v)) {
                            $html .= ' ' . $name . '-' . $n . "='" . self::jsonHtmlEncode($v) . "'";
                        } elseif (is_bool($v)) {
                            if ($v) {
                                $html .= ' ' . $name . '-' . $n;
                            }
                        } elseif ($v !== null) {
                            $html .= ' ' . $name . '-' . $n . '="' . self::encode($v) . '"';
                        }
                    }
                } elseif ($name === 'class') {
                    if (count($value) > 0) {
                        $html .= ' class="' . self::encode(implode(' ', array_filter($value))) . '"';
                    }
                } elseif ($name === 'style') {
                    if (count($value) > 0) {
                        $style = '';
                        foreach ($value as $property => $propertyValue) {
                            $style .= $property . ': ' . $propertyValue . '; ';
                        }
                        $html .= ' style="' . self::encode(rtrim($style)) . '"';
                    }
                } else {
                    $html .= ' ' . $name . "='" . self::jsonHtmlEncode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= ' ' . $name . '="' . self::encode($value) . '"';
            }
        }

        return $html;
    }

    private static function jsonHtmlEncode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_THROW_ON_ERROR);
    }
}
