<?php

use app\models\db\{Amendment, IVotingItem, Motion, Vote, VotingBlock, VotingQuestion};
use app\models\policies\EligibilityByGroup;
use app\models\proposedProcedure\AgendaVoting;
use app\models\votings\Answer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\{Xlsx, Html, Ods};

/**
 * @param EligibilityByGroup[]|null $eligibilityList
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 */
function printResultTable(Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, ?array $eligibilityList, IVotingItem $voteItem): int
{
    $doneRows = \app\models\layoutHooks\Layout::printVotingAlternativeSpreadsheetResults($worksheet, $startRow, $agendaVoting, $voteItem);
    if ($doneRows > 0) {
        return $doneRows;
    }

    $voteResults = $voteItem->getVotingData();
    if ($agendaVoting->voting->votingStatus !== VotingBlock::STATUS_CLOSED_PUBLISHED) {
        $voteResults->augmentWithResults($agendaVoting->voting, $voteItem);
    }
    $rows = 0;

    $worksheet->mergeCells('A' . ($startRow + $rows) . ':B' . ($startRow + $rows));
    $worksheet->getStyle('A' . ($startRow + $rows))->applyFromArray(['font' => ['bold' => true]]);
    $worksheet->setCellValue('A' . ($startRow + $rows), Yii::t('export', 'voting_total_ans'));
    $worksheet->getStyle('B' . ($startRow + $rows))->applyFromArray(['font' => ['bold' => true]]);
    $worksheet->setCellValue('B' . ($startRow + $rows), Yii::t('export', 'voting_groups_all'));

    if ($agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ADMIN || $agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ALL) {
        $col = 'B';
        foreach ($eligibilityList ?? [] as $eligibility) {
            $col = chr(ord($col) + 1);
            $worksheet->getStyle($col . ($startRow + $rows))->applyFromArray(['font' => ['bold' => true]]);
            $worksheet->setCellValue($col . ($startRow + $rows), $eligibility->groupTitle);
        }
    }
    $rows++;

    foreach ($agendaVoting->voting->getAnswers() as $answer) {
        $worksheet->setCellValue('A' . ($startRow + $rows), $answer->title);
        $worksheet->setCellValue('B' . ($startRow + $rows), $voteResults->getTotalVotesForAnswer($answer));

        $col = 'B';
        if ($agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ADMIN || $agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ALL) {
            foreach ($eligibilityList ?? [] as $eligibility) {
                $userIds = array_map(function (array $user): int { return $user['user_id']; }, $eligibility->users);
                $votes = 0;
                foreach ($agendaVoting->voting->votes as $vote) {
                    if ($vote->vote === $answer->dbId && in_array($vote->userId, $userIds)) {
                        $votes++;
                    }
                }
                $col = chr(ord($col) + 1);
                $worksheet->setCellValue($col . ($startRow + $rows), (string)$votes);
            }
        }

        $rows++;
    }

    return $rows;
}

/**
 * @param Vote[] $votes
 * @param EligibilityByGroup[]|null $eligibilityList
 */
function printVoteResults(Worksheet $worksheet, string $col, int $startRow, Answer $answer, array $votes, ?array $eligibilityList): void
{
    $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['bold' => true]]);
    $worksheet->setCellValue($col . $startRow, $answer->title);
    $startRow++;

    $votedUserIds = [];
    $votedUserWeights = [];
    foreach ($votes as $vote) {
        if ($vote->vote === $answer->dbId) {
            $votedUserIds[$vote->userId] = ($vote->getUser() ? $vote->getUser()->getAuthUsername() : Yii::t('export', 'voting_unknown_user'));
            $votedUserWeights[$vote->userId] = $vote->weight;
            if ($vote->weight > 1) {
                $votedUserIds[$vote->userId] .= ' (×' . $vote->weight . ')';
            }
        }
    }

    // Hint: a user can be shown in multiple groups
    $shownUsers = [];
    foreach ($eligibilityList ?? [] as $eligibility) {
        $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['underline' => true]]);
        $worksheet->setCellValue($col . $startRow, $eligibility->groupTitle);
        $startRow++;

        $foundOne = false;
        foreach ($eligibility->users as $user) {
            if (isset($votedUserWeights[$user['user_id']])) {
                $foundOne = true;
                $shownUsers[] = $user['user_id'];
                $toShowName = $user['user_name'];
                $weight = $votedUserWeights[$user['user_id']];
                if ($weight > 1) {
                    $toShowName .= ' (×' . $weight . ')';
                }
                $worksheet->setCellValue($col . $startRow, $toShowName);
                $startRow++;
            }
        }
        if (!$foundOne) {
            $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['italic' => true]]);
            $worksheet->setCellValue($col . $startRow, Yii::t('export', 'voting_no_users'));
            $startRow++;
        }
        $startRow++;
    }

    $hasUnknown = false;
    foreach ($votedUserIds as $votedUserId => $votedUserName) {
        if (!in_array($votedUserId, $shownUsers)) {
            if (!$hasUnknown) {
                $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['underline' => true, 'italic' => true]]);
                $worksheet->setCellValue($col . $startRow, Yii::t('export', 'voting_unknown_users'));
                $hasUnknown = true;
                $startRow++;
            }
            $worksheet->setCellValue($col . $startRow, $votedUserName);
            $startRow++;
        }
    }
}

/**
 * @param EligibilityByGroup[] $groups
 */
function printEligibilityList(Worksheet $worksheet, string $col, int $startRow, array $groups): void {
    $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['bold' => true]]);
    $worksheet->setCellValue($col . $startRow, Yii::t('export', 'voting_eligible_all'));
    $startRow++;
    $startRow++;

    foreach ($groups as $group) {
        $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['underline' => true]]);
        $worksheet->setCellValue($col . $startRow, $group->groupTitle);
        $startRow++;

        foreach ($group->users as $user) {
            $toShowName = $user['user_name'];
            $weight = $user['weight'] ?? 1;
            if ($weight > 1) {
                $toShowName .= ' (×' . $weight . ')';
            }
            $worksheet->setCellValue($col . $startRow, $toShowName);
            $startRow++;
        }
        if (count($group->users) === 0) {
            $worksheet->getStyle($col . $startRow)->applyFromArray(['font' => ['italic' => true]]);
            $worksheet->setCellValue($col . $startRow, Yii::t('export', 'voting_no_users'));
        }
        $startRow++;
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

    if ($agendaVoting->voting->isClosed()) {
        $eligibilityList = $voteItem->getVotingData()->getEligibilityList();
    } else {
        $eligibilityList = $agendaVoting->voting->getVotingPolicy()->getEligibilityByGroup();
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
    $sheetTitle = preg_replace('/[^a-z0-9_ -]/siu', '', $title);
    $sheetTitle = (grapheme_strlen($sheetTitle) > 30 ? grapheme_substr($sheetTitle, 0, 28) . '...' : $sheetTitle);
    $sheet->setTitle($sheetTitle);
    $sheet->getColumnDimension('A')->setWidth($width, 'cm');
    $sheet->getColumnDimension('B')->setWidth($width, 'cm');

    $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true]]);
    $sheet->setCellValue('A1', $agendaVoting->voting->title);
    $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true]]);
    $sheet->setCellValue('A2', $title);

    $sheet->setCellValue('A3', Yii::t('export', 'voting_export_date') . ':');
    if ($format === 'xslx') {
        $sheet->setCellValue('B3', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(time()));
        $sheet->getStyle('B3')
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME);
    } else {
        $sheet->setCellValue('B3', date("Y-m-d H:i"));
    }

    $row = 5;
    $row += printResultTable($sheet, $row, $agendaVoting, $eligibilityList, $voteItem);
    $row++;

    $col = 'A';
    $userListBaseRow = $row;
    if ($agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ADMIN || $agendaVoting->voting->votesPublic === VotingBlock::VOTES_PUBLIC_ALL) {
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $sheet->setCellValue('A' . $row, Yii::t('export', 'voting_userlist'));
        $row++;

        foreach ($agendaVoting->voting->getAnswers() as $answer) {
            printVoteResults($sheet, $col, $row, $answer, $voteItem->votes, $eligibilityList);
            $col = chr(ord($col) + 1);
        }
    }

    if ($eligibilityList) {
        $col = chr(ord($col) + 1);
        printEligibilityList($sheet, $col, $userListBaseRow, $eligibilityList);
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

