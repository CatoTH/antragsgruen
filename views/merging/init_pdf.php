<?php

use app\models\db\{Amendment, Motion};
use app\views\pdfLayouts\BDK;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Motion $motion
 * @var Amendment[] $amendments
 * @var int[] $activated
 */


$pdfLayout = new BDK($motion->getMyMotionType());
/** @var \app\views\pdfLayouts\BDKPDF $pdf */
$pdf = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetTitle(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix() . ' - Merge Configuration');
$pdf->SetSubject(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix() . ' - Merge Configuration');
$pdf->setMotionTitle($motion->getTitleWithPrefix(), Yii::t('export', 'draft'));

$pdf->startPageGroup();
$pdf->AddPage();


$motionData = '';
$motionData .= '<div style="font-size: 15px; font-weight: bold;">';
$motionData .= Yii::t('export', 'pdf_merging_init');
$motionData .= '</div><br>';

$motionData .= '<span style="font-size: 20px; font-weight: bold">';
$motionData .= Html::encode($motion->getFormattedTitlePrefix()) . ' </span>';
$motionData .= '<span style="font-size: 16px;">';
$motionData .= Html::encode($motion->title) . '</span>';


BDK::printHeaderTable($pdf, $motion->motionType->getSettingsObj()->pdfIntroduction, $motionData);



$table = '<table width="100%" border="1" cellpadding="5">';
$table .= '<tr>';
$table .= '<th width="100"><b>' . Yii::t('amend', 'merge_amtable_merge') . '</b></th>';
$table .= '<th width="100"><b>' . Yii::t('amend', 'merge_amtable_title') . '</b></th>';
$table .= '<th width="150"><b>' . Yii::t('amend', 'merge_amtable_status') . '</b></th>';
$table .= '<th width="252"><b>' . Yii::t('amend', 'merge_amtable_proposal') . '</b></th>';
$table .= '</tr>';
foreach ($amendments as $amendment) {
    $table .= '<tr>';
    $table .= '<td align="center">';
    if (in_array($amendment->id, $activated)) {
        $table .= 'X';
    }
    $table .= '</td><td>';
    $table .= Html::encode($amendment->getFormattedTitlePrefix());
    $table .= '</td><td>';
    $table .= $amendment->getFormattedStatus();
    $table .= '</td><td>';
    $table .= $amendment->getFormattedProposalStatus();
    $table .= '</td></tr>' . "\n";
}
$table .= '</table>';


$pdf->writeHTML($table);

$pdf->Output($motion->getFilenameBase(true) . '-Merging-Selection.pdf', 'I');
