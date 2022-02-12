<?php

use app\models\db\{Amendment, IVotingItem, Motion, Vote, VotingBlock, VotingQuestion};
use app\models\proposedProcedure\AgendaVoting;
use app\models\votings\{Answer, AnswerTemplates};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\{Xlsx, Html, Ods};

function printResultTable(Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, IVotingItem $voteItem): int
{
    $doneRows = \app\models\layoutHooks\Layout::printVotingAlternativeSpreadsheetResults($worksheet, $startRow, $agendaVoting, $voteItem);
    if ($doneRows > 0) {
        return $doneRows;
    }

    $voteResults = $voteItem->getVotingData()->augmentWithResults($agendaVoting->voting, $voteItem->votes);
    $rows = 0;

    $worksheet->mergeCells('A' . ($startRow + $rows) . ':B' . ($startRow + $rows));
    $worksheet->getStyle('A' . ($startRow + $rows))->applyFromArray(['font' => ['bold' => true]]);
    $worksheet->setCellValue('A' . ($startRow + $rows), Yii::t('export', 'voting_total_ans'));
    $rows++;

    foreach ($agendaVoting->voting->getAnswers() as $answer) {
        $worksheet->setCellValue('A' . ($startRow + $rows), $answer->title);
        $worksheet->setCellValue('B' . ($startRow + $rows), $voteResults->getTotalVotesForAnswer($answer));
        $rows++;
    }

    return $rows;
}

/**
 * @param Vote[] $votes
 */
function printVoteResults(Worksheet $worksheet, string $col, int $startRow, Answer $answer, array $votes): void
{
    $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['bold' => true]]);
    $worksheet->setCellValue($col . $startRow, $answer->title);
    $startRow++;
    foreach ($votes as $vote) {
        if ($vote->vote === $answer->dbId) {
            $name = ($vote->getUser() ? $vote->getUser()->getAuthUsername() : Yii::t('export', 'voting_unknown_user'));
            $worksheet->setCellValue($col . $startRow, $name);
            $startRow++;
        }
    }
}

/**
 * @var AgendaVoting $agendaVoting
 * @var string $format
 */


$width = 5;


$spreadsheet = new Spreadsheet();
foreach ($agendaVoting->items as $i => $voteItem) {
    if ($i === 0) {
        $sheet = $spreadsheet->getActiveSheet();
    } else {
        $sheet = $spreadsheet->createSheet();
    }

    $title = '';
    switch (get_class($voteItem)) {
        case Motion::class:
            /** @var Motion $voteItem */
            $title = $voteItem->getTitleWithPrefix();
            break;
        case Amendment::class:
            /** @var Amendment $voteItem */
            $title = $voteItem->getTitleWithPrefix();
            break;
        case VotingQuestion::class:
            /** @var VotingQuestion $voteItem */
            $title = $voteItem->title;
            break;
    }
    $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true]]);
    $sheet->setTitle(preg_replace('/[^a-z0-9_ -]/siu', '', $title));
    $sheet->getColumnDimension('A')->setWidth($width, 'cm');
    $sheet->getColumnDimension('B')->setWidth($width, 'cm');
    $sheet->setCellValue('A1', $title);

    $sheet->setCellValue('A2', Yii::t('export', 'voting_export_date') . ':');
    if ($format === 'xslx') {
        $sheet->setCellValue('B2', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(time()));
        $sheet->getStyle('B2')
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME);
    } else {
        $sheet->setCellValue('B2', date("Y-m-d H:i"));
    }

    $row = 4;
    $row += printResultTable($sheet, $row, $agendaVoting, $voteItem);
    $row++;

    if ($agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ADMIN || $agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ALL) {
        $col = 'A';

        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $sheet->setCellValue('A' . $row, Yii::t('export', 'voting_userlist'));
        $row++;

        foreach ($agendaVoting->voting->getAnswers() as $answer) {
            printVoteResults($sheet, $col, $row, $answer, $voteItem->votes);
            $col = chr(ord($col) + 1);
        }
    }
}

$fileName = \app\models\settings\AntragsgruenApp::getInstance()->getTmpDir() . uniqid();
switch ($format) {
    case 'ods':
        $writer = new Ods($spreadsheet);
        $writer->save($fileName);
        break;
    case 'xlsx':
        $writer = new Xlsx($spreadsheet);
        $writer->save($fileName);
        break;
    default:
        $writer = new Html($spreadsheet);
        $writer->save($fileName);
}

$content = file_get_contents($fileName);
unlink($fileName);

echo $content;

