<?php

declare(strict_types=1);

namespace app\plugins\european_youth_forum;

use app\components\UrlHelper;
use app\models\proposedProcedure\AgendaVoting;
use app\models\settings\Layout;
use app\models\db\{Amendment, Consultation, ConsultationUserGroup, ISupporter, IVotingItem, User, VotingBlock};
use app\models\layoutHooks\Hooks;
use app\models\settings\VotingData;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    private const USERGROUP_REMOTE_LABEL = 'Remote'; // This needs to be _part_ of a user group name

    public function beforePage(string $before): string
    {
        $out = '';
        if (User::getCurrentUser()) {
            $out .= $this->getUserBar();
        }
        return $out;
    }

    protected function getUserBarGroups(User $user): string
    {
        $groups = array_filter($user->getConsultationUserGroups($this->consultation), function (ConsultationUserGroup $group) {
            if (str_starts_with($group->title, 'Voting ')) {
                return false;
            }
            return $group->templateId !== ConsultationUserGroup::TEMPLATE_PARTICIPANT;
        });
        return implode(", ", array_map(function (ConsultationUserGroup $group): string {
            return $group->getNormalizedTitle();
        }, $groups));
    }

    protected function getUserBar(): string
    {
        $user = User::getCurrentUser();
        $out = '<div id="userLoginPanel">';
        $out .= '<div class="username"><strong>' . \Yii::t('base', 'menu_logged_in') . ':</strong> ';
        $out .= Html::encode($user->name);
        if ($user->organization) {
            $out .= ' (' . Html::encode($user->organization) . ')';
        }
        $out .= '</div>';

        $out .= '<div class="groups">';
        $out .= Html::encode($this->getUserBarGroups($user));
        $out .= '</div>';

        $out .= '</div>';

        return $out;
    }

    public function endOfHead(string $before): string
    {
        $before .= '<style>' . file_get_contents(__DIR__ . '/assets/styles.css') . '</style>';

        return $before;
    }

    public function logoRow(string $before): string
    {
        $user = User::getCurrentUser();

        $out = '<div class="logoRow">';
        $out .= '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo">';
        $out .= '<span class="sr-only">' . \Yii::t('base', 'home_back') . '</span>';
        $out .= $this->layout->getLogoStr();
        $out .= '</a>';
        $out .= '<div class="yfjTitleGroup">';
        if ($user) {
            $groups = $this->getUserBarGroups($user);
            if (str_contains($groups, 'INGYO')) {
                $out .= 'INGYO';
            }
            if (str_contains($groups, 'NYC')) {
                $out .= 'NYC';
            }
        }
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }

    public function getAdditionalUserAdministrationVueTemplate(string $before, Consultation $consultation): string
    {
        ob_start();
        require(__DIR__ . '/views/user-admin-add.vue.php');
        return $before . ob_get_clean();
    }

    public function registerAdditionalVueUserAdministrationTemplates(?string $before, Consultation $consultation, Layout $layout): ?string
    {
        $layout->addVueTemplate('@app/plugins/european_youth_forum/views/user-admin.mixins.vue.php');

        return null;
    }

    public function getVotingAlternativeAdminHeader(?string $before, Consultation $consultation): ?string
    {
        ob_start();
        require(__DIR__ . '/views/voting-admin-header.php');
        return $before . ob_get_clean();
    }

    public function getVotingAdditionalActions(?string $before, Consultation $consultation): ?string
    {
        ob_start();
        require(__DIR__ . '/views/voting-admin-add-actions.vue.php');
        return (string)ob_get_clean();
    }

    public function getVotingAlternativeResults(?string $before, Consultation $consultation): ?string
    {
        ob_start();
        require(__DIR__ . '/views/voting-result.vue.php');
        return (string)ob_get_clean();
    }

    public function registerAdditionalVueVotingTemplates(?string $before, Consultation $consultation, Layout $layout): ?string
    {
        $layout->addVueTemplate('@app/plugins/european_youth_forum/views/votings.mixins.vue.php');

        return null;
    }

    public function getVotingAlternativeUserResults(?array $before, VotingData $votingData): ?array
    {
        return require(__DIR__ . '/views/voting-result-user.php');
    }

    public function getFormattedUsername(string $before, User $user): string
    {
        $username = trim($user->organization ?: '') !== '' ? $user->organization : $user->name;
        if ($this->consultation) {
            $username .= self::getUserRemoteLabelIfRelevant($user, $this->consultation, false);
        }

        return $username;
    }

    private function printYfjVotingAlternativeSpreadsheetResults(int $rowsBefore, Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, IVotingItem $voteItem): int
    {
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

    private function printRollCallAlternativeSpreadsheetResults(int $rowsBefore, Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, IVotingItem $voteItem): int
    {
        /** @var \app\plugins\european_youth_forum\VotingData $voteResults */
        $voteResults = $voteItem->getVotingData();
        if (!$agendaVoting->voting->isClosed()) {
            $voteResults->augmentWithResults($agendaVoting->voting, $voteItem);
        }
        if ($agendaVoting->voting->votesPublic !== VotingBlock::VOTES_PUBLIC_ADMIN && $agendaVoting->voting->votesPublic !== VotingBlock::VOTES_PUBLIC_ALL) {
            return 0;
        }

        $rows = 0;

        foreach ($agendaVoting->voting->getAnswers() as $answer) {
            $worksheet->getStyle('A' . ($startRow + $rows))->applyFromArray(['font' => ['bold' => true]]);
            $worksheet->setCellValue('A' . ($startRow + $rows), $answer->title);
            $worksheet->setCellValue('B' . ($startRow + $rows), $voteResults->getTotalVotesForAnswer($answer));

            $rows++;
        }

        $results = VotingHelper::getRollCallResultTable($agendaVoting->voting->getMyConsultation(), $agendaVoting->voting);
        foreach ($results as $result) {
            $worksheet->getStyle('A' . ($startRow + $rows))->applyFromArray(['font' => ['bold' => true]]);
            $worksheet->setCellValue('A' . ($startRow + $rows), $result['name']);
            $worksheet->setCellValue('B' . ($startRow + $rows), $result['number']);

            $rows++;
        }

        return $rows;
    }

    public function printVotingAlternativeSpreadsheetResults(int $rowsBefore, Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, IVotingItem $voteItem): int
    {
        if (VotingHelper::isSetUpAsYfjVoting($agendaVoting->voting)) {
            return $this->printYfjVotingAlternativeSpreadsheetResults($rowsBefore, $worksheet, $startRow, $agendaVoting, $voteItem);
        }
        if (VotingHelper::isSetUpAsYfjRollCall($agendaVoting->voting)) {
            return $this->printRollCallAlternativeSpreadsheetResults($rowsBefore, $worksheet, $startRow, $agendaVoting, $voteItem);
        }

        return 0;
    }

    public static function getUserRemoteLabelIfRelevant(User $user, Consultation $consultation, bool $html): string
    {
        $isRemote = false;
        foreach ($user->getConsultationUserGroups($consultation) as $group) {
            if (str_contains($group->title, self::USERGROUP_REMOTE_LABEL)) {
                $isRemote = true;
            }
        }
        if ($isRemote) {
            if ($html) {
                return ' <span class="label label-info">Remote</span>';
            } else {
                return ' [[Remote]]'; // [[...]] is a pattern replaced by labels in the frontend
            }
        } else {
            return '';
        }
    }

    public function getMotionDetailsInitiatorName(string $before, ISupporter $supporter): string
    {
        $user = $supporter->getMyUser();
        if (!$user) {
            return $before;
        }
        return $before . self::getUserRemoteLabelIfRelevant($supporter->getMyUser(), $supporter->getIMotion()->getMyConsultation(), true);
    }
}
