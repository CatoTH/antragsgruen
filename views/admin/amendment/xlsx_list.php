<?php

use app\components\HTMLTools;
use app\models\db\{Amendment, AmendmentSection, Motion};
use app\models\sectionTypes\{ISectionType, TextSimpleCommon};
use app\models\supportTypes\SupportBase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var array<array{motion: Motion, amendments: Amendment[]}> $amendments
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheetTitle = preg_replace('/[^a-z0-9_ -]/siu', '', Yii::t('export', 'amendments'));
$sheetTitle = (grapheme_strlen($sheetTitle) > 30 ? grapheme_substr($sheetTitle, 0, 28) . '...' : $sheetTitle);
$sheet->setTitle($sheetTitle);
//$sheet->getColumnDimension('A')->setWidth($width, 'cm');
//$sheet->getColumnDimension('B')->setWidth($width, 'cm');


$currCol = 0;

$hasAgendaItems = false;
$hasResponsibilities = false;
$hasLikes = false;
$hasDislikes = false;
foreach ($amendments as $amendmentGroup) {
    $motion = $amendmentGroup['motion'];
    if ($motion->getMyMotionType()->amendmentsOnly) {
        continue;
    }
    if ($motion->agendaItem) {
        $hasAgendaItems = true;
    }
    if ($motion->responsibilityId || $motion->responsibilityComment) {
        $hasResponsibilities = true;
    }
    if ($motion->getMyMotionType()->amendmentLikesDislikes & SupportBase::LIKEDISLIKE_LIKE) {
        $hasLikes = true;
    }
    if ($motion->getMyMotionType()->amendmentLikesDislikes & SupportBase::LIKEDISLIKE_DISLIKE) {
        $hasDislikes = true;
    }
}

if ($hasAgendaItems) {
    $COL_AGENDA_ITEM = chr(ord('A') + $currCol++);
}
$COL_PREFIX     = chr(ord('A') + $currCol++);
$COL_INITIATOR  = chr(ord('A') + $currCol++);
$COL_FIRST_LINE = chr(ord('A') + $currCol++);
$COL_STATUS     = chr(ord('A') + $currCol++);
$COL_CHANGE     = chr(ord('A') + $currCol++);
$COL_REASON     = chr(ord('A') + $currCol++);
$COL_CONTACT    = chr(ord('A') + $currCol++);
$COL_PROCEDURE  = chr(ord('A') + $currCol++);
$LAST_COL      = $COL_PROCEDURE;
if ($hasResponsibilities) {
    $COL_RESPONSIBILITY = chr(ord('A') + $currCol++);
    $LAST_COL           = $COL_RESPONSIBILITY;
}
if ($hasLikes) {
    $COL_LIKES = chr(ord('A') + $currCol++);
    $LAST_COL = $COL_LIKES;
}
if ($hasDislikes) {
    $COL_DISLIKES = chr(ord('A') + $currCol++);
    $LAST_COL = $COL_DISLIKES;
}


// Title

$sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true]]);
$sheet->setCellValue('A1', Yii::t('export', 'amendments'));
$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->getStyle('A1')->getFont()->setSize(16);


// Heading

if ($hasAgendaItems) {
    $sheet->setCellValue($COL_AGENDA_ITEM . '2', Yii::t('export', 'agenda_item'));
    $sheet->getStyle($COL_AGENDA_ITEM . '2')->getFont()->setBold(true);
    $sheet->getColumnDimension($COL_AGENDA_ITEM)->setWidth(3, 'cm');
}

$sheet->setCellValue($COL_PREFIX . '2', Yii::t('export', 'prefix_short'));
$sheet->getStyle($COL_PREFIX . '2')->getFont()->setBold(true);
$sheet->getColumnDimension($COL_PREFIX)->setWidth(2, 'cm');

$sheet->setCellValue($COL_INITIATOR . '2', Yii::t('export', 'initiator'));
$sheet->getColumnDimension($COL_INITIATOR)->setWidth(4, 'cm');

$sheet->setCellValue($COL_FIRST_LINE . '2', Yii::t('export', 'line'));
$sheet->getColumnDimension($COL_FIRST_LINE)->setWidth(2, 'cm');

$sheet->setCellValue($COL_STATUS . '2', Yii::t('export', 'status'));
$sheet->getColumnDimension($COL_STATUS)->setWidth(2, 'cm');

$sheet->setCellValue($COL_CHANGE . '2', Yii::t('export', 'amend_change'));
$sheet->getColumnDimension($COL_CHANGE)->setWidth(6, 'cm');

$sheet->setCellValue($COL_REASON . '2', Yii::t('export', 'amend_reason'));
$sheet->getColumnDimension($COL_REASON)->setWidth(6, 'cm');

$sheet->setCellValue($COL_CONTACT . '2', Yii::t('export', 'contact'));
$sheet->getColumnDimension($COL_CONTACT)->setWidth(4, 'cm');

$sheet->setCellValue($COL_PROCEDURE . '2', Yii::t('export', 'procedure'));
$sheet->getColumnDimension($COL_PROCEDURE)->setWidth(6, 'cm');

if ($hasResponsibilities) {
    $sheet->setCellValue($COL_RESPONSIBILITY . '2', Yii::t('export', 'responsibility'));
    $sheet->getColumnDimension($COL_RESPONSIBILITY)->setWidth(4, 'cm');
}

if ($hasLikes) {
    $sheet->setCellValue($COL_LIKES . '2', Yii::t('motion', 'likes'));
    $sheet->getColumnDimension($COL_LIKES)->setWidth(2, 'cm');
}

if ($hasDislikes) {
    $sheet->setCellValue($COL_DISLIKES . '2', Yii::t('motion', 'dislikes'));
    $sheet->getColumnDimension($COL_DISLIKES)->setWidth(2, 'cm');
}

$sheet->getStyle('A1:' . $LAST_COL . '2')->applyFromArray([
    'borders' => [
        'outline' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
            'color' => ['argb' => '00000000'],
        ]
    ]
]);


// Amendments

$row = 3;
$htmlHelper = new PhpOffice\PhpSpreadsheet\Helper\Html();

foreach ($amendments as $amendmentGroup) {
    $motion = $amendmentGroup['motion'];
    if ($motion->getMyMotionType()->amendmentsOnly) {
        continue;
    }

    $row++;
    $maxRows        = 1;
    $firstMotionRow = $row;

    $initiatorNames = [];
    foreach ($motion->getInitiators() as $supp) {
        $initiatorNames[] = $supp->getNameWithResolutionDate(false);
    }

    $title = '<strong>' . $motion->getTitleWithPrefix() . '</strong>';
    $title .= ' (' . Yii::t('export', 'motion_by') . ': ' . Html::encode(implode(', ', $initiatorNames)) . ')';
    if ($hasAgendaItems && $motion->agendaItem) {
        $sheet->setCellValue($COL_AGENDA_ITEM . $row, $motion->agendaItem->getShownCode(true));
    }
    $title = HTMLTools::correctHtmlErrors($title);
    $sheet->setCellValue($COL_PREFIX . $row, $htmlHelper->toRichTextObject($title)); // , null, ['fo:wrap-option' => 'no-wrap']

    if ($hasResponsibilities) {
        $responsibility = [];
        if ($motion->responsibilityUser) {
            $user = $motion->responsibilityUser;
            $responsibility[] = $user->name ? $user->name : $user->getAuthName();
        }
        if ($motion->responsibilityComment) {
            $responsibility[] = $motion->responsibilityComment;
        }
        $sheet->setCellValue($COL_RESPONSIBILITY . $row, implode(', ', $responsibility));
    }

    foreach ($amendmentGroup['amendments'] as $amendment) {
        $row++;

        // $sheet->getRowDimension($row)->setRowHeight(5, 'cm');

        $initiatorNames   = [];
        $initiatorContacs = [];
        foreach ($amendment->getInitiators() as $supp) {
            $initiatorNames[] = $supp->getNameWithResolutionDate(false);
            if ($supp->contactEmail != '') {
                $initiatorContacs[] = $supp->contactEmail;
            }
            if ($supp->contactPhone != '') {
                $initiatorContacs[] = $supp->contactPhone;
            }
        }
        $affectedLines = $amendment->getAffectedLines();

        if ($hasAgendaItems && $motion->agendaItem) {
            $sheet->setCellValue($COL_AGENDA_ITEM . $row, $motion->agendaItem->getShownCode(true));
        }
        $sheet->setCellValue($COL_PREFIX . $row, $amendment->getFormattedTitlePrefix());
        $sheet->setCellValue($COL_INITIATOR . $row, implode(', ', $initiatorNames));
        $sheet->setCellValue($COL_CONTACT . $row, implode(', ', $initiatorContacs));
        if ($affectedLines['from'] === $affectedLines['to']) {
            $sheet->setCellValue($COL_FIRST_LINE . $row, $affectedLines['from']);
        } else {
            $sheet->setCellValue($COL_FIRST_LINE . $row, $affectedLines['from'] . ' - ' . $affectedLines['to']);
        }

        $sheet->setCellValue($COL_STATUS . $row, $htmlHelper->toRichTextObject($amendment->getFormattedStatus()));

        $change = '';
        if ($amendment->changeEditorial != '') {
            $change .= '<h4>' . Yii::t('amend', 'editorial_hint') . '</h4><br>';
            $change .= $amendment->changeEditorial;
        }
        foreach ($amendment->getSortedSections(false) as $section) {
            if ($section->getSettings()->type === ISectionType::TYPE_TITLE) {
                continue;
            }
            $change .= $section->getSectionType()->getAmendmentPlainHtml(true);
        }
        $change = preg_replace('/<h4 class="lineSummary">([^<]+)<\/h4>/iu', '<h4><em>$1</em></h4>', $change);
        $change = HTMLTools::correctHtmlErrors($change);
        $sheet->setCellValue($COL_CHANGE . $row, $htmlHelper->toRichTextObject($change));

        $changeExplanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
        $sheet->setCellValue($COL_REASON . $row, $htmlHelper->toRichTextObject($changeExplanation));

        $proposal = $amendment->getFormattedProposalStatus();
        if ($amendment->hasAlternativeProposaltext()) {
            $reference = $amendment->getMyProposalReference();
            /** @var AmendmentSection[] $sections */
            $sections = $reference->getSortedSections(false);
            foreach ($sections as $section) {
                $firstLine    = $section->getFirstLineNumber();
                $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
                $originalData = $section->getOriginalMotionSection()?->getData() ?? '';
                $newData      = $section->getData();
                $proposal     .= TextSimpleCommon::formatAmendmentForOds($originalData, $newData, $firstLine, $lineLength);
            }
        }
        $sheet->setCellValue($COL_PROCEDURE . $row, $htmlHelper->toRichTextObject($proposal));

        if ($hasResponsibilities) {
            $responsibility = [];
            if ($amendment->responsibilityUser) {
                $user             = $amendment->responsibilityUser;
                $responsibility[] = $user->name ? $user->name : $user->getAuthName();
            }
            if ($amendment->responsibilityComment) {
                $responsibility[] = $amendment->responsibilityComment;
            }

            $sheet->setCellValue($COL_PROCEDURE . $row, implode(', ', $responsibility));
        }

        if ($amendment->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_LIKE) {
            $likeCount = count($amendment->getLikes());
            $sheet->setCellValue($COL_LIKES . $row, $likeCount);
        }
        if ($amendment->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_DISLIKE) {
            $dislikeCount = count($amendment->getDislikes());
            $sheet->setCellValue($COL_DISLIKES . $row, $dislikeCount);
        }
    }

    $sheet->getStyle('A' . $firstMotionRow . ':' . $LAST_COL . $row)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['argb' => '00000000'],
            ]
        ]
    ]);

    $row++;
}

$fileName = \app\models\settings\AntragsgruenApp::getInstance()->getTmpDir() . uniqid();
$writer = new Xlsx($spreadsheet);
$writer->save($fileName);
$content = file_get_contents($fileName);
unlink($fileName);

echo $content;
