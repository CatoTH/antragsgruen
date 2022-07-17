<?php

namespace app\plugins\european_youth_forum;

use app\models\proposedProcedure\AgendaVoting;
use app\models\db\{Consultation, IVotingItem, User, VotingBlock};
use app\models\layoutHooks\Hooks;
use app\models\settings\VotingData;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LayoutHooks extends Hooks
{
    public function getVotingAlternativeAdminResults(?string $before, Consultation $consultation): ?string
    {
        $result = $this;
        ob_start();
        require(__DIR__ . '/views/voting-result-admin.vue.php');
        return (string)ob_get_clean();
    }

    public function getVotingAlternativeUserResults(?array $before, VotingData $votingData): ?array
    {
        return require(__DIR__ . '/views/voting-result-user.php');
    }

    public function getFormattedUsername(string $before, User $user): string
    {
        return trim($user->organization ?: '') !== '' ? $user->organization : $user->name;
    }

    public function printVotingAlternativeSpreadsheetResults(int $rowsBefore, Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, IVotingItem $voteItem): int
    {
        if (!VotingHelper::isSetUpAsYfjVoting($agendaVoting->voting)) {
            return 0;
        }

        /** @var \app\plugins\european_youth_forum\VotingData $voteResults */
        $voteResults = $voteItem->getVotingData();
        if (!$agendaVoting->voting->isClosed()) {
            $voteResults->augmentWithResults($agendaVoting->voting, $voteItem);
        }
        $rows = 0;


        $worksheet->getStyle('A' . ($startRow + $rows + 0))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('A' . ($startRow + $rows), 'Eligible users');
        $worksheet->getStyle('A' . ($startRow + $rows + 1))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('A' . ($startRow + $rows + 1), 'NYC');
        $worksheet->setCellValue('B' . ($startRow + $rows + 1), $voteResults->nycUsers);
        $worksheet->getStyle('A' . ($startRow + $rows + 2))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('A' . ($startRow + $rows + 2), 'INGYO');
        $worksheet->setCellValue('B' . ($startRow + $rows + 2), $voteResults->ingyoUsers);

        $rows += 4;

        $worksheet->mergeCells('A' . ($startRow + $rows) . ':B' . ($startRow + $rows));
        $worksheet->getStyle('A' . ($startRow + $rows))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('A' . ($startRow + $rows), \Yii::t('export', 'voting_total_ans'));
        $rows++;


        // First two rows
        $baseRow = $startRow + $rows;
        $worksheet->mergeCells('B' . ($baseRow + 0) . ':B' . ($baseRow + 1));
        $worksheet->getStyle('A' . ($baseRow + 0))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('B' . ($baseRow + 0), '');

        $worksheet->mergeCells('C' . ($baseRow + 0) . ':C' . ($baseRow + 1));
        $worksheet->getStyle('C' . ($baseRow + 0))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('C' . ($baseRow + 0), 'Votes cast');

        $worksheet->mergeCells('D' . ($baseRow + 0) . ':E' . ($baseRow + 0));
        $worksheet->getStyle('D' . ($baseRow + 0))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('D' . ($baseRow + 0), 'Yes');
        $worksheet->getStyle('D' . ($baseRow + 1))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('D' . ($baseRow + 1), 'Ticks');
        $worksheet->getStyle('E' . ($baseRow + 1))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('E' . ($baseRow + 1), 'Votes');

        $worksheet->mergeCells('F' . ($baseRow + 0) . ':G' . ($baseRow + 0));
        $worksheet->getStyle('F' . ($baseRow + 0))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('F' . ($baseRow + 0), 'No');
        $worksheet->getStyle('F' . ($baseRow + 1))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('F' . ($baseRow + 1), 'Ticks');
        $worksheet->getStyle('G' . ($baseRow + 1))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('G' . ($baseRow + 1), 'Votes');

        $worksheet->getStyle('H' . ($baseRow + 0))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('H' . ($baseRow + 0), 'Abst.');
        $worksheet->getStyle('H' . ($baseRow + 1))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('H' . ($baseRow + 1), 'Ticks');

        $worksheet->getStyle('I' . ($baseRow + 0))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('I' . ($baseRow + 0), 'Total');
        $worksheet->getStyle('I' . ($baseRow + 1))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('I' . ($baseRow + 1), 'Ticks');

        // Organization names

        $worksheet->getStyle('B' . ($baseRow + 2))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('B' . ($baseRow + 2), 'NYC');
        $worksheet->getStyle('B' . ($baseRow + 3))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('B' . ($baseRow + 3), 'INGYO');
        $worksheet->getStyle('B' . ($baseRow + 4))->applyFromArray(['font' => ['bold' => true]]);
        $worksheet->setCellValue('B' . ($baseRow + 4), 'Total');

        // NYC Results
        $worksheet->setCellValue('C' . ($baseRow + 2), $voteResults->nycTotalMultiplied);
        $worksheet->setCellValue('D' . ($baseRow + 2), $voteResults->nycYes);
        $worksheet->setCellValue('E' . ($baseRow + 2), $voteResults->nycYesMultiplied);
        $worksheet->setCellValue('F' . ($baseRow + 2), $voteResults->nycNo);
        $worksheet->setCellValue('G' . ($baseRow + 2), $voteResults->nycNoMultiplied);
        $worksheet->setCellValue('H' . ($baseRow + 2), $voteResults->nycAbstention);
        $worksheet->setCellValue('I' . ($baseRow + 2), $voteResults->nycTotal);

        // INGYO Results
        $worksheet->setCellValue('C' . ($baseRow + 3), $voteResults->ingyoTotalMultiplied);
        $worksheet->setCellValue('D' . ($baseRow + 3), $voteResults->ingyoYes);
        $worksheet->setCellValue('E' . ($baseRow + 3), $voteResults->ingyoYesMultiplied);
        $worksheet->setCellValue('F' . ($baseRow + 3), $voteResults->ingyoNo);
        $worksheet->setCellValue('G' . ($baseRow + 3), $voteResults->ingyoNoMultiplied);
        $worksheet->setCellValue('H' . ($baseRow + 3), $voteResults->ingyoAbstention);
        $worksheet->setCellValue('I' . ($baseRow + 3), $voteResults->ingyoTotal);

        // Total results
        $worksheet->setCellValue('C' . ($baseRow + 4), $voteResults->totalTotalMultiplied);
        $worksheet->setCellValue('E' . ($baseRow + 4), $voteResults->totalYesMultiplied);
        $worksheet->setCellValue('G' . ($baseRow + 4), $voteResults->totalNoMultiplied);

        return 11;
    }
}
