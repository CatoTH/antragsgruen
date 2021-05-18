<?php

/**
 * @var \app\models\mergeAmendments\Init $form
 */

use yii\helpers\Html;

$motionType = $form->motion->getMyMotionType();
$pdfLayout  = $motionType->getPDFLayoutClass();
$pdf        = $pdfLayout->createPDFClass();

$amendmentsById = [];
foreach ($form->motion->getVisibleAmendments(false, false) as $amendment) {
    $amendmentsById[$amendment->id] = $amendment;
}

// set document information
$title = $form->motion->title;
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle($title);
$pdf->SetSubject($title);

$pdfLayout->printMotionHeader($form->motion);

$pdf->Ln(5);
$amendmentsHtml = '<table border="1" cellpadding="5"><tr><td><h2>Änderungsanträge</h2>';
foreach ($form->motion->getVisibleAmendments(false, false) as $amendment) {
    $amendmentsHtml .= '<div><strong>' . Html::encode($amendment->titlePrefix) . '</strong>: ' . Html::encode($amendment->getInitiatorsStr()) . '</div>';
}
if (count($form->motion->getVisibleAmendments(false, false)) === 0) {
    $amendmentsHtml .= '<em>Keine</em>';
}
$amendmentsHtml .= '</td></tr></table>';
$pdf->writeHTML($amendmentsHtml, true, false, false, true);
$pdf->Ln(5);

foreach ($form->motion->getSortedSections(false) as $section) {
    $type = $section->getSettings();
    if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
        // echo $this->render('_merging_section_title', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
    } elseif ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
        $paragraphs = $section->getTextParagraphObjects(false, false, false, true);
        //$type = $section->getSettings();
        //$lineNo = $section->getFirstLineNumber();
        //$hasLineNumbers = $section->getSettings()->lineNumbers;
        $paragraphNos = array_keys($paragraphs);

        foreach ($paragraphNos as $paragraphNo) {
            $draftParagraph      = $form->draftData->paragraphs[$section->sectionId . '_' . $paragraphNo];
            $paragraphCollisions = array_filter(
                $form->getParagraphTextCollisions($section, $paragraphNo),
                function ($amendmentId) use ($draftParagraph) {
                    return !in_array($amendmentId, $draftParagraph->handledCollisions);
                },
                ARRAY_FILTER_USE_KEY
            );

            $html = \app\views\motion\LayoutHelper::convertMergingHtmlToTcpdfable($draftParagraph->text);
            $pdf->writeHTML($html);

            foreach ($paragraphCollisions as $amendmentId => $paraData) {
                $amendment = $amendmentsById[$amendmentId];
                $html = \app\components\diff\amendmentMerger\ParagraphMerger::getFormattedCollision($paraData, $amendment, $amendmentsById);
                $html = \app\views\motion\LayoutHelper::convertMergingHtmlToTcpdfable($draftParagraph->text);
                $html = '<table border="1" cellpadding="5"><tr><td><div><strong>Colliding amendment</strong></div>' . $html . '</td></tr></table>';
                $pdf->writeHTML($html);
            }
        }
    } else {
        // echo $this->render('_merging_section_other', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
    }
}


$pdf->Output('Antragsspiegel.pdf', 'I');

die();
