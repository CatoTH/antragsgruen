<?php

/**
 * @var Motion $motion
 * @var Draft $draft
 */

use app\models\db\Motion;
use app\models\mergeAmendments\Draft;
use app\models\sectionTypes\ISectionType;
use app\views\pdfLayouts\BDK;
use yii\helpers\Html;

$pdfLayout = new BDK($motion->motionType);
$pdf       = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator(\Yii::t('export', 'default_creator'));
$pdf->SetTitle(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix() . ' - Merge Draft');
$pdf->SetSubject(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix() . ' - Merge Draft');

$pdf->startPageGroup();
$pdf->AddPage();

$motionData = '';
$motionData .= '<div style="font-size: 15px; font-weight: bold;">';
$motionData .= \Yii::t('export', 'pdf_merging_draft');
$motionData .= '</div><br>';

$motionData .= '<span style="font-size: 20px; font-weight: bold">';
$motionData .= Html::encode($motion->titlePrefix) . ' </span>';
$motionData .= '<span style="font-size: 16px;">';
$motionData .= Html::encode($motion->title) . '</span>';


BDK::printHeaderTable($pdf, $motion->motionType->getSettingsObj()->pdfIntroduction, $motionData);


$pdf->setHtmlVSpace([
    'ul'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
    'li'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
    'div'        => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
    'p'          => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
    'blockquote' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
]);

foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
    $pdf->SetFont('Helvetica');
    $pdf->writeHTML('<h2>' . Html::encode($section->getSettings()->title) . '</h2><br>');

    $pdf->SetFont('Courier');

    $paragraphs = [];
    foreach ($section->getTextParagraphLines() as $paraNo => $para) {
        $paragraphs[] = $draft->paragraphs[$section->sectionId . '_' . $paraNo]->text;
    }
    $html = implode("\n", $paragraphs);

    // the following code is disgusting and doesn't even try to generate valid HTML.
    // Only code that will be rendered correctly by TCPDF.

    $html = preg_replace_callback('/<ins(?<attrs> [^>]*)?>(?<content>.*)<\/ins>/siuU', function ($matches) {
        $content = $matches['content'];
        if (preg_match('/data\-append\-hint=["\'](?<append>[^"\']*)["\']/siu', $matches['attrs'], $matches2)) {
            $content .= '<sub>' . $matches2['append'] . '</sub> ';
        }

        return '<span color="green"><b><u>' . $content . '</u></b></span>';
    }, $html);
    $html = preg_replace_callback('/<del(?<attrs> [^>]*)?>(?<content>.*)<\/del>/siuU', function ($matches) {
        $content = $matches['content'];
        if (preg_match('/data\-append\-hint=["\'](?<append>[^"\']*)["\']/siu', $matches['attrs'], $matches2)) {
            $content .= '<sub>' . $matches2['append'] . '</sub> ';
        }

        return '<span color="red"><b><s>' . $content . '</s></b></span>';
    }, $html);


    $html = preg_replace_callback(
        '/<(?<tag>\w+) (?<attributes>[^>]*appendHint[^>]*)>' .
        '(?<content>.*)' .
        '<\/\k<tag>>/siuU',
        function ($matches) {
            $content = $matches['content'];
            if (preg_match('/data\-append\-hint=["\'](?<append>[^"\']*)["\']/siu', $matches['attributes'], $matches2)) {
                $content .= '<sub>' . $matches2['append'] . '</sub> ';
            }

            return '<' . $matches['tag'] . ' ' . $matches['attributes'] . '>' . $content . '</' . $matches['tag'] . '>';
        },
        $html
    );
    $html = preg_replace_callback(
        '/<(?<tag>\w+) (?<attributes>[^>]*ice\-ins[^>]*)>' .
        '(?<content>.*)' .
        '<\/\k<tag>>/siuU',
        function ($matches) {
            $content = $matches['content'];
            $content = '<div color="green"><b><u>' . $content . '</u></b></div>';

            return '<' . $matches['tag'] . ' ' . $matches['attributes'] . '>' . $content . '</' . $matches['tag'] . '>';
        },
        $html
    );
    $html = preg_replace_callback(
        '/<(?<tag>\w+) (?<attributes>[^>]*ice\-del[^>]*)>' .
        '(?<content>.*)' .
        '<\/\k<tag>>/siuU',
        function ($matches) {
            $content = $matches['content'];
            $content = '<div color="red"><b><s>' . $content . '</s></b></div>';

            return '<' . $matches['tag'] . ' ' . $matches['attributes'] . '>' . $content . '</' . $matches['tag'] . '>';
        },
        $html
    );

    $html = preg_replace_callback(
        '/<(?<tag>p|ul|li|div|blockquote|h1|h2|h3|h4|h5|h6)(?<attributes> [^>]*)?>/siuU',
        function ($matches) {
            $str = '<' . $matches['tag'] . ' padding="0"';
            if (isset($matches['attributes'])) {
                $str .= ' ' . $matches['attributes'];
            }
            $str .= '>';

            return $str;
        },
        $html
    );


    $pdf->writeHTML($html);
}

$pdf->Output($motion->getFilenameBase(true) . '-Merging-Draft.pdf', 'I');

die();
