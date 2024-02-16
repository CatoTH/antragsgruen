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

$pdfLayout = new BDK($motion->getMyMotionType());
/** @var \app\views\pdfLayouts\BDKPDF $pdf */
$pdf = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetTitle(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix() . ' - Merge Draft');
$pdf->SetSubject(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix() . ' - Merge Draft');
$pdf->setMotionTitle($motion->getTitleWithPrefix(), Yii::t('export', 'draft'));

$pdf->startPageGroup();
$pdf->AddPage();

$motionData = '<div style="font-size: 15px; font-weight: bold;">';
$motionData .= Yii::t('export', 'pdf_merging_draft');
$motionData .= '</div><br>';

$motionData .= '<span style="font-size: 20px; font-weight: bold">';
$motionData .= Html::encode($motion->getFormattedTitlePrefix()) . ' </span>';
$motionData .= '<span style="font-size: 16px;">';
$motionData .= Html::encode($motion->title) . '</span>';


BDK::printHeaderTable($pdf, $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction, $motionData);


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
    foreach ($section->getTextParagraphLines() as $para) {
        $paragraphs[] = $draft->paragraphs[$section->sectionId . '_' . $para->paragraphWithLineSplit]->text;
    }
    $html = implode("\n", $paragraphs);

    $html = \app\views\motion\LayoutHelper::convertMergingHtmlToTcpdfable($html);

    $pdf->writeHTML($html);
}

$pdf->Output($motion->getFilenameBase(true) . '-Merging-Draft.pdf', 'I');
