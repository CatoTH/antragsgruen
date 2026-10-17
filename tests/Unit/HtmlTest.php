<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\Html;
use Tests\Support\Helper\TestBase;

/**
 * The expected markup is identical to what yii\helpers\Html generated, which app\components\Html replaces.
 */
class HtmlTest extends TestBase
{
    public function testEncode(): void
    {
        $this->assertSame('&lt;a href=&quot;x&quot;&gt;&amp;amp; &#039;q&#039;', Html::encode('<a href="x">&amp; \'q\''));
        $this->assertSame('&amp;', Html::encode('&amp;', false));
        $this->assertSame('42', Html::encode(42));
        $this->assertSame('', Html::encode(null));
        $this->assertSame("\u{FFFD}", Html::encode("\xff"));
    }

    public function testAttributes(): void
    {
        $this->assertSame(
            '<a id="i" class="x y" href="/foo?a=1&amp;b=2" title="t" rel="nofollow" aria-label="L&quot;" target="_blank">Text</a>',
            Html::a('Text', '/foo?a=1&b=2', ['class' => ['x', '', 'y'], 'aria-label' => 'L"', 'id' => 'i', 'rel' => 'nofollow', 'title' => 't', 'data-x' => null, 'target' => '_blank'])
        );
        $this->assertSame(
            '<a data-a="1" data-b data-d=\'{"x":"\u003C"}\' style="color: red; width: 1px;">T</a>',
            Html::a('T', null, ['data' => ['a' => 1, 'b' => true, 'c' => false, 'd' => ['x' => '<'], 'e' => null], 'style' => ['color' => 'red', 'width' => '1px']])
        );
        $this->assertSame(
            '<a href="#" foo=\'{"ü":"\u0027"}\'>T</a>',
            Html::a('T', '#', ['style' => [], 'class' => [], 'foo' => ['ü' => "'"]])
        );
    }

    public function testCheckboxAndRadio(): void
    {
        $this->assertSame(
            '<input type="checkbox" id="c" class="x" name="n" value="3" checked disabled>',
            Html::checkbox('n', true, ['id' => 'c', 'value' => 3, 'class' => 'x', 'disabled' => true])
        );
        $this->assertSame('<input type="checkbox" name="n" value="1">', Html::checkbox('n', 1, ['checked' => false]));
        $this->assertSame(
            '<input type="hidden" name="n[]" value="0" form="f" disabled><label class="lc"><input type="checkbox" name="n[]" value="1" form="f" disabled> <b>L</b></label>',
            Html::checkbox('n[]', false, ['uncheck' => '0', 'form' => 'f', 'disabled' => true, 'label' => '<b>L</b>', 'labelOptions' => ['class' => 'lc']])
        );
        $this->assertSame('<input type="radio" id="r1" name="r" value="0">', Html::radio('r', false, ['value' => 0, 'id' => 'r1']));
    }

    public function testDropDownList(): void
    {
        $this->assertSame(
            "<select id=\"d\" class=\"form-control\" name=\"d\" autocomplete=\"off\">\n<option value=\"1\">one</option>\n<option value=\"2\" selected>two</option>\n<option value=\"3\">th ree &lt;</option>\n</select>",
            Html::dropDownList('d', 2, [1 => 'one', 2 => 'two', '3' => 'th ree <'], ['id' => 'd', 'class' => 'form-control', 'autocomplete' => 'off'])
        );

        // The empty string only matches the empty string, and vice versa
        $items = ['' => 'none', 0 => 'zero'];
        $this->assertSame("<select name=\"d\">\n<option value=\"\" selected>none</option>\n<option value=\"0\">zero</option>\n</select>", Html::dropDownList('d', '', $items));
        $this->assertSame("<select name=\"d\">\n<option value=\"\">none</option>\n<option value=\"0\" selected>zero</option>\n</select>", Html::dropDownList('d', 0, $items));
        $this->assertSame("<select name=\"d\">\n<option value=\"\">none</option>\n<option value=\"0\">zero</option>\n</select>", Html::dropDownList('d', null, $items));

        $this->assertSame(
            "<select name=\"d\">\n<option value=\"\">Pick&nbsp;one</option>\n<optgroup class=\"g\" label=\"G1\">\n<option value=\"a\">A</option>\n<option value=\"b\" selected>B</option>\n</optgroup>\n<option value=\"c\" disabled>C</option>\n</select>",
            Html::dropDownList('d', 'b', ['G1' => ['a' => 'A', 'b' => 'B'], 'c' => 'C'], [
                'prompt' => 'Pick one', 'encodeSpaces' => true, 'groups' => ['G1' => ['class' => 'g']], 'options' => ['c' => ['disabled' => true]],
            ])
        );
        $this->assertSame(
            "<input type=\"hidden\" name=\"d\" value=\"\"><select name=\"d[]\" multiple size=\"4\">\n<option value=\"1\" selected>a</option>\n<option value=\"2\" selected>b</option>\n<option value=\"3\">c</option>\n</select>",
            Html::dropDownList('d', ['1', 2], [1 => 'a', 2 => 'b', 3 => 'c'], ['multiple' => true, 'unselect' => ''])
        );
    }

    public function testInputs(): void
    {
        $this->assertSame('<input type="text" class="c" name="t" value="5" placeholder="p">', Html::textInput('t', 5, ['placeholder' => 'p', 'class' => 'c']));
        $this->assertSame('<input type="text" name="n" value="1.5">', Html::input('number', 'n', 1.5, ['type' => 'text']));
        $this->assertSame('<input type="hidden" id="h" name="h" value="v">', Html::hiddenInput('h', 'v', ['id' => 'h']));
        $this->assertSame(
            '<textarea name="ta" rows="3" title="t" required>&lt;b&gt;&amp;amp;&lt;/b&gt;</textarea>',
            Html::textarea('ta', '<b>&amp;</b>', ['rows' => 3, 'title' => 't', 'required' => true])
        );
        $this->assertSame('<textarea name="ta">&amp;</textarea>', Html::textarea('ta', '&amp;', ['doubleEncode' => false]));
    }

    public function testRadioList(): void
    {
        $this->assertSame(
            "<div class=\"form-control\"><label><input type=\"radio\" name=\"rl\" value=\"a\"> A&lt;</label>\n<label><input type=\"radio\" name=\"rl\" value=\"b\" checked> B</label></div>",
            Html::radioList('rl', 'b', ['a' => 'A<', 'b' => 'B'], ['class' => 'form-control'])
        );
        $this->assertSame(
            '<input type="hidden" name="rl" value="x" disabled><label><input type="radio" class="ic" name="rl" value="0" checked> zero</label><br><label><input type="radio" class="ic" name="rl" value="1"> one</label>',
            Html::radioList('rl', 0, [0 => 'zero', 1 => 'one'], ['tag' => false, 'separator' => '<br>', 'itemOptions' => ['class' => 'ic'], 'unselect' => 'x', 'disabled' => true])
        );
        $this->assertSame(
            "<div>0|one|rl|C|1\n1|two|rl|-|2</div>",
            Html::radioList('rl', ['1'], [1 => 'one', 2 => 'two'], [
                'item' => fn (int $index, string $label, string $name, bool $checked, int $value): string => $index . '|' . $label . '|' . $name . '|' . ($checked ? 'C' : '-') . '|' . $value,
            ])
        );
    }

    public function testGetForm(): void
    {
        $this->assertSame(
            "<form class=\"createdForm\" action=\"/a/b\" method=\"get\">\n<input type=\"hidden\" name=\"c\" value=\"1\">\n<input type=\"hidden\" name=\"d\" value=\" e\">\n<input type=\"hidden\" name=\"f\" value=\"\">",
            Html::beginForm('/a/b?c=1&d=%20e&f', 'get', ['class' => 'createdForm'])
        );
        $this->assertSame('</form>', Html::endForm());
    }

    public function testUnsupportedFormMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Html::beginForm('/a', 'delete');
    }
}
