<?php

namespace Tests\Unit;

use app\views\pdfLayouts\IPdfWriter;
use Tests\Support\Helper\TestBase;

class IPdfWriterListMarkupTest extends TestBase
{
    public function testMapsClassBasedListStyles(): void
    {
        $this->assertSame(
            '<ol style="list-style-type:lower-alpha"><li>a</li><li>b</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="lowerAlpha"><li>a</li><li>b</li></ol>')
        );
        $this->assertSame(
            '<ol style="list-style-type:decimal"><li>a</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="decimalDot"><li>a</li></ol>')
        );
    }

    public function testKeepsUnrelatedClassesAndStyles(): void
    {
        $this->assertSame(
            '<ol class="foo" style="color:red;list-style-type:upper-alpha"><li>a</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="foo upperAlpha" style="color:red"><li>a</li></ol>')
        );
    }

    public function testLiValueSplitsList(): void
    {
        $this->assertSame(
            '<ol start="3" style="margin-bottom:0"><li>x</li></ol><ol start="7" style="margin-top:0"><li>y</li><li>z</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol start="3"><li>x</li><li value="7">y</li><li>z</li></ol>')
        );
    }

    public function testLiValueWithLetterInAlphaList(): void
    {
        $this->assertSame(
            '<ol style="list-style-type:lower-alpha;margin-bottom:0"><li>x</li></ol>' .
            '<ol style="list-style-type:lower-alpha;margin-top:0" start="5"><li>y</li><li>z</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="lowerAlpha"><li>x</li><li value="e">y</li><li>z</li></ol>')
        );
    }

    public function testDecimalCircleGetsInlineMarkers(): void
    {
        $this->assertSame(
            '<ol style="list-style-type:none"><li>(1)&nbsp;x</li><li>(5)&nbsp;y</li><li>(6)&nbsp;z</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="decimalCircle"><li>x</li><li value="5">y</li><li>z</li></ol>')
        );
    }

    public function testDecimalCircleShowsNonNumericValuesVerbatim(): void
    {
        $this->assertSame(
            '<ol style="list-style-type:none"><li>(1)&nbsp;x</li><li>(1b)&nbsp;y</li><li>(1c)&nbsp;z</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="decimalCircle"><li>x</li><li value="1b">y</li><li>z</li></ol>')
        );
    }

    public function testDecimalCircleMarkerIsWrittenInsideBlockElements(): void
    {
        $this->assertSame(
            '<ol style="list-style-type:none"><li><p>(1)&nbsp;x</p></li><li><p>(2)&nbsp;y</p><p>more</p></li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="decimalCircle"><li><p>x</p></li><li><p>y</p><p>more</p></li></ol>')
        );
    }

    public function testNestedListsInsideDecimalCircle(): void
    {
        $input = '<ol class="decimalCircle">'
            . '<li><p>one</p></li>'
            . '<li value="1b"><p>one-b</p><ol class="lowerAlpha"><li><p>sub a</p></li><li value="c"><p>sub c</p></li><li>sub d</li></ol></li>'
            . '<li value="3"><p>three</p><ol><li><p>plain one</p></li></ol></li>'
            . '<li>four</li>'
            . '</ol>';
        $expected = '<ol style="list-style-type:none">'
            . '<li><p>(1)&nbsp;one</p></li>'
            . '<li><p>(1b)&nbsp;one-b</p>'
            . '<ol style="list-style-type:lower-alpha;margin-bottom:0"><li>sub a</li></ol>'
            . '<ol style="list-style-type:lower-alpha;margin-top:0" start="3"><li>sub c</li><li>sub d</li></ol></li>'
            . '<li><p>(3)&nbsp;three</p><ol style="list-style-type:decimal"><li>plain one</li></ol></li>'
            . '<li>(4)&nbsp;four</li>'
            . '</ol>';
        $this->assertSame($expected, IPdfWriter::prepareHtmlListMarkup($input));
    }

    public function testUnwrapsFirstParagraphOfNestedListItems(): void
    {
        // Top-level list items keep their <p>, nested ones have their first <p> unwrapped;
        // further paragraphs of the same list item stay untouched
        $this->assertSame(
            '<ol><li><p>outer</p><ol><li>first<p>second</p></li></ol></li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol><li><p>outer</p><ol><li><p>first</p><p>second</p></li></ol></li></ol>')
        );
        $this->assertSame(
            '<ul><li><p>outer</p><ul><li>nested</li></ul></li></ul>',
            IPdfWriter::prepareHtmlListMarkup('<ul><li><p>outer</p><ul><li><p>nested</p></li></ul></li></ul>')
        );
    }

    public function testNestedUnorderedListDoesNotDisturbCounter(): void
    {
        $this->assertSame(
            '<ol style="margin-bottom:0"><li>x<ul><li>bullet</li></ul></li></ol><ol start="9" style="margin-top:0"><li>y</li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol><li>x<ul><li>bullet</li></ul></li><li value="9">y</li></ol>')
        );
    }

    public function testUnorderedListsAndPlainHtmlAreUntouched(): void
    {
        $this->assertSame(
            '<ul><li>a</li><li>b</li></ul>',
            IPdfWriter::prepareHtmlListMarkup('<ul><li>a</li><li>b</li></ul>')
        );
        $this->assertSame(
            '<p>hello <strong>world</strong></p>',
            IPdfWriter::prepareHtmlListMarkup('<p>hello <strong>world</strong></p>')
        );
    }

    public function testNestedPlainListsGetExplicitDefaultStyle(): void
    {
        // The engine inherits list-style-type into nested lists, so plain lists inside restyled
        // ones need an explicit default; inside unstyled lists they are left alone
        $this->assertSame(
            '<ol style="list-style-type:none"><li>(1)&nbsp;x<ol style="list-style-type:decimal"><li>a</li></ol><ul style="list-style-type:disc"><li>b</li></ul></li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol class="decimalCircle"><li>x<ol><li>a</li></ol><ul><li>b</li></ul></li></ol>')
        );
        $this->assertSame(
            '<ol><li>x<ol><li>a</li></ol></li></ol>',
            IPdfWriter::prepareHtmlListMarkup('<ol><li>x<ol><li>a</li></ol></li></ol>')
        );
    }

    public function testIsIdempotent(): void
    {
        $once = IPdfWriter::prepareHtmlListMarkup('<ol class="decimalCircle"><li>x</li><li value="5">y</li></ol>');
        $this->assertSame($once, IPdfWriter::prepareHtmlListMarkup($once));

        $once = IPdfWriter::prepareHtmlListMarkup('<ol class="lowerAlpha" start="2"><li>x</li><li value="7">y</li></ol>');
        $this->assertSame($once, IPdfWriter::prepareHtmlListMarkup($once));
    }
}
