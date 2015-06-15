<?php

use app\components\LineSplitter;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;

/**
 * @var $this yii\web\View
 * @var Motion[] $motions
 * @var bool $textCombined
 * @var ConsultationMotionType $motionType
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);


$hasTags = ($consultation->tags > 0);


$currCol   = ord('B');
$first_col = chr($currCol);

$COL_PREFIX    = chr($currCol++);
$COL_INITIATOR = chr($currCol++);
$COL_TEXTS     = [];
if ($textCombined) {
    $COL_TEXTS[] = chr($currCol++);
} else {
    foreach ($motionType->motionSections as $section) {
        $COL_TEXTS[$section->id] = chr($currCol++);
    }
}
if ($hasTags) {
    $COL_TAGS = chr($currCol++);
}
$COL_CONTACT   = chr($currCol++);
$COL_PROCEDURE = chr($currCol++);

$objPHPExcel = new \PHPExcel();

$objPHPExcel->getProperties()->setCreator("Antragsgruen.de");
$objPHPExcel->getProperties()->setLastModifiedBy("Antragsgruen.de");
$objPHPExcel->getProperties()->setTitle($consultation->title);
$objPHPExcel->getProperties()->setSubject('Motions');
$objPHPExcel->getProperties()->setDescription($consultation->title . ' - ' . 'Motions');

$objPHPExcel->setActiveSheetIndex(0);

$objPHPExcel->getActiveSheet()->SetCellValue($COL_PREFIX . '2', 'Motions');
$objPHPExcel->getActiveSheet()->getStyle($COL_PREFIX . "2")->applyFromArray([
    "font" => [
        "bold" => true
    ]
]);

$objPHPExcel->getActiveSheet()->SetCellValue($COL_PREFIX . '3', 'Antragsnr.');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_INITIATOR . '3', 'AntragstellerIn');
if ($textCombined) {
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_TEXTS[0] . '3', 'Text');
    $objPHPExcel->getActiveSheet()->getColumnDimension($COL_TEXTS[0])->setAutoSize(80);
} else {
    foreach ($motionType->motionSections as $section) {
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_TEXTS[$section->id] . '3', $section->title);
        $objPHPExcel->getActiveSheet()->getColumnDimension($COL_TEXTS[$section->id])->setAutoSize(80);
    }
}
if (isset($COL_TAGS)) {
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_TAGS . '3', 'Schlagworte');
}
$objPHPExcel->getActiveSheet()->SetCellValue($COL_CONTACT . '3', 'Kontakt');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_PROCEDURE . '3', 'Verfahren');
$objPHPExcel->getActiveSheet()->getStyle($COL_PREFIX . "3:" . $COL_PROCEDURE . "3")->applyFromArray([
    "font" => [
        "bold" => true
    ]
]);

$styleThinBlackBorderOutline = [
    'borders' => [
        'outline' => [
            'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
$objPHPExcel->getActiveSheet()->getStyle($COL_PREFIX . '2:' . $COL_PROCEDURE . '3')
    ->applyFromArray($styleThinBlackBorderOutline);


PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());


$row = 3;

foreach ($motions as $motion) {
    $row++;
    $maxRows = 1;

    $initiatorNames   = [];
    $initiatorContacs = [];
    foreach ($motion->getInitiators() as $supp) {
        $initiatorNames[] = $supp->getNameWithResolutionDate(false);
        if ($supp->contactEmail != '') {
            $initiatorContacs[] = $supp->contactEmail;
        }
        if ($supp->contactPhone != '') {
            $initiatorContacs[] = $supp->contactPhone;
        }
    }

    $objPHPExcel->getActiveSheet()->SetCellValue($COL_PREFIX . $row, $motion->titlePrefix);
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_INITIATOR . $row, implode(', ', $initiatorNames));
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_CONTACT . $row, implode("\n", $initiatorContacs));

    if ($textCombined) {
        $text = '';
        foreach ($motion->getSortedSections(true) as $section) {
            $text .= $section->consultationSetting->title . "\n\n";
            $text .= $section->getSectionType()->getAmendmentPlainText();
            $text .= "\n\n";
        }
        $splitter = new LineSplitter($text, 80);
        $lines = $splitter->splitLines();
        if (count($lines) > $maxRows) {
            $maxRows = count($lines);
        }
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_TEXTS[0] . $row, implode("\n", $lines));
    } else {
        foreach ($motionType->motionSections as $section) {
            $text = '';
            foreach ($motion->sections as $sect) {
                if ($sect->sectionId == $section->id) {
                    $text .= $sect->getSectionType()->getAmendmentPlainText();
                }
            }
            $splitter = new LineSplitter($text, 80);
            $lines = $splitter->splitLines();
            if (count($lines) > $maxRows) {
                $maxRows = count($lines);
            }
            $objPHPExcel->getActiveSheet()->SetCellValue($COL_TEXTS[$section->id] . $row, implode("\n", $lines));
        }
    }
    if (isset($COL_TAGS)) {
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_TAGS . '3', 'Schlagworte');
    }

    if (isset($COL_TAGS)) {
        $tags = [];
        foreach ($motion->tags as $tag) {
            $tags[] = $tag->title;
        }
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_TAGS . $row, implode("\n", $tags));
    }
    $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(15.2 * $maxRows);
}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_PREFIX)->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_INITIATOR)->setWidth(24);

if (isset($COL_TAGS)) {
    $objPHPExcel->getActiveSheet()->getColumnDimension($COL_TAGS)->setAutoSize(18);
}
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_CONTACT)->setWidth(24);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_PROCEDURE)->setWidth(13);


$objPHPExcel->getActiveSheet()->setTitle('Anträge');

// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save('php://output');
