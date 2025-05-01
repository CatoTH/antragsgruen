<?php

use app\components\{HTMLTools, Tools};
use app\models\db\Motion;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @var yii\web\View $this
 * @var Motion[] $imotions
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheetTitle = preg_replace('/[^a-z0-9_ -]/siu', '', Yii::t('export', 'comments'));
$sheetTitle = (grapheme_strlen($sheetTitle) > 30 ? grapheme_substr($sheetTitle, 0, 28) . '...' : $sheetTitle);
$sheet->setTitle($sheetTitle);


$currCol = 0;
$COL_PREFIX = chr(ord('A') + $currCol++);
$COL_AUTHOR = chr(ord('A') + $currCol++);
$COL_TEXT = chr(ord('A') + $currCol++);
$COL_DATE = chr(ord('A') + $currCol++);
$LAST_COL = $COL_DATE;

// Title

$sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true]]);
$sheet->setCellValue('A1', Yii::t('export', 'comments'));
$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->getStyle('A1')->getFont()->setSize(16);

// Heading

$sheet->setCellValue($COL_PREFIX . '2', Yii::t('export', 'prefix_short'));
$sheet->getStyle($COL_PREFIX . '2')->getFont()->setBold(true);
$sheet->getColumnDimension($COL_PREFIX)->setWidth(2, 'cm');

$sheet->setCellValue($COL_AUTHOR . '2', Yii::t('export', 'author'));
$sheet->getColumnDimension($COL_AUTHOR)->setWidth(4, 'cm');

$sheet->setCellValue($COL_TEXT . '2', Yii::t('export', 'text'));
$sheet->getColumnDimension($COL_TEXT)->setWidth(8, 'cm');

$sheet->setCellValue($COL_DATE . '2', Yii::t('export', 'date'));
$sheet->getColumnDimension($COL_DATE)->setWidth(4, 'cm');

$sheet->getStyle('A1:' . $LAST_COL . '2')->applyFromArray([
    'borders' => [
        'outline' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
            'color' => ['argb' => '00000000'],
        ]
    ]
]);


// Comments

$row = 3;
$htmlHelper = new PhpOffice\PhpSpreadsheet\Helper\Html();

foreach ($imotions as $motion) {
    foreach ($motion->comments as $comment) {
        if ($comment->status !== \app\models\db\IComment::STATUS_VISIBLE) {
            continue;
        }

        $sheet->setCellValue($COL_PREFIX . $row, $motion->titlePrefix ?? ''); // , null, ['fo:wrap-option' => 'no-wrap']
        $sheet->setCellValue($COL_AUTHOR . $row, $comment->name);
        $sheet->setCellValue($COL_TEXT . $row, $htmlHelper->toRichTextObject(HTMLTools::textToHtmlWithLink($comment->text)));
        $sheet->setCellValue($COL_DATE . $row, Tools::formatMysqlDateTime($comment->dateCreation, false));

        $row++;
    }
}

$sheet->getStyle('A3:' . $LAST_COL . $row)->applyFromArray([
    'borders' => [
        'outline' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
            'color' => ['argb' => '00000000'],
        ]
    ]
]);

$fileName = \app\models\settings\AntragsgruenApp::getInstance()->getTmpDir() . uniqid();
$writer = new Xlsx($spreadsheet);
$writer->save($fileName);
$content = file_get_contents($fileName);
unlink($fileName);

echo $content;
