<?php /** @noinspection PhpUnusedParameterInspection */

namespace app\models\layoutHooks;

use app\models\db\{Amendment, Consultation, ConsultationMotionType, ConsultationText, IMotion, ISupporter, IVotingItem, Motion, MotionSection, Site, User};
use app\models\proposedProcedure\AgendaVoting;
use app\models\settings\{VotingData, Layout as LayoutSettings};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Hooks
{
    public function __construct(
        protected LayoutSettings $layout,
        protected ?Consultation $consultation
    ) {}

    public function beforePage(string $before): string
    {
        return $before;
    }

    public function beginPage(string $before): string
    {
        return $before;
    }

    public function logoRow(string $before): string
    {
        return $before;
    }

    public function favicons(string $before): string
    {
        return $before;
    }

    public function endOfHead(string $before): string
    {
        return $before;
    }

    public function squareLogoPath(?string $before): ?string
    {
        return $before;
    }

    public function beforeContent(string $before): string
    {
        return $before;
    }

    public function beginContent(string $before): string
    {
        return $before;
    }

    public function afterContent(string $before): string
    {
        return $before;
    }

    public function endPage(string $before): string
    {
        return $before;
    }

    public function renderSidebar(string $before): string
    {
        return $before;
    }

    public function getSearchForm(string $before): string
    {
        return $before;
    }

    public function getAntragsgruenAd(string $before): string
    {
        return $before;
    }

    /**
     * @param ConsultationMotionType[] $motionTypes
     */
    public function setSidebarCreateMotionButton(string $before, array $motionTypes): string
    {
        return $before;
    }

    public function getStdNavbarHeader(string $before): string
    {
        return $before;
    }

    public function breadcrumbs(string $before): string
    {
        return $before;
    }

    public function footerLine(string $before): string
    {
        return $before;
    }

    public function beforeMotionView(string $before, Motion $motion): string
    {
        return $before;
    }

    public function afterMotionView(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getMotionAlternativeComments(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getMotionViewData(array $motionData, Motion $motion): array
    {
        return $motionData;
    }

    public function getMotionExportData(array $motionData, Motion $motion): array
    {
        return $motionData;
    }

    public function getMotionFormattedAmendmentList(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getAmendmentViewData(array $amendmentData, Amendment $amendment): array
    {
        return $amendmentData;
    }

    public function getAmendmentExportData(array $amendmentData, Amendment $amendment): array
    {
        return $amendmentData;
    }

    public function getAmendmentBookmarkName(string $before, Amendment $amendment): string
    {
        return $before;
    }

    public function getAmendmentAlternativeComments(string $before, Amendment $amendment): string
    {
        return $before;
    }

    public function getFormattedMotionStatus(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getFormattedAmendmentStatus(string $before, Amendment $amendment): string
    {
        return $before;
    }

    public function getFormattedMotionVersion(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getFormattedTitlePrefix(?string $before, IMotion $imotion, ?int $context): ?string
    {
        return $before;
    }

    public function getFormattedUsername(string $before, User $user): string
    {
        return $before;
    }

    public function getConsultationPreWelcome(string $before): string
    {
        return $before;
    }

    public function getConsultationWelcomeReplacer(?string $before): ?string
    {
        return $before;
    }

    public function getConsultationMotionLineContent(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getConsultationAmendmentLineContent(string $before, Amendment $amendment): string
    {
        return $before;
    }

    public function getMotionDetailsInitiatorName(string $before, ISupporter $supporter): string
    {
        return $before;
    }

    public function getSupporterNameWithOrga(string $before, ISupporter $supporter): string
    {
        return $before;
    }

    public function getSupporterNameWithResolutionDate(string $before, ISupporter $supporter, bool $html): string
    {
        return $before;
    }

    public function getAdminIndexHint(string $before, Consultation $consultation): string
    {
        return $before;
    }

    public function getContentPageContent(string $before, ConsultationText $text, bool $admin): string
    {
        return $before;
    }

    /**
     * @param string[] $before
     * @return string[]
     */
    public function getSitewidePublicWarnings(array $before, Site $site): array
    {
        return $before;
    }

    public function getConsultationwidePublicWarnings(array $before, Consultation $consultation): array
    {
        return $before;
    }

    public function renderMotionSection(?string $before, MotionSection $section, Motion $motion): ?string
    {
        return $before;
    }

    public function getMotionPublishedInitiatorEmail(?array $before, Motion $motion): ?array
    {
        return $before;
    }

    public function getAmendmentPublishedInitiatorEmail(?array $before, Amendment $amendment): ?array
    {
        return $before;
    }

    public function getAdditionalUserAdministrationVueTemplate(string $before, Consultation $consultation): string
    {
        return $before;
    }

    public function registerAdditionalVueUserAdministrationTemplates(?string $before, Consultation $consultation, LayoutSettings $layout): ?string
    {
        return null;
    }

    public function getVotingAlternativeAdminHeader(?string $before, Consultation $consultation): ?string
    {
        return $before;
    }

    public function getVotingAlternativeResults(?string $before, Consultation $consultation): ?string
    {
        return $before;
    }

    public function getVotingAdditionalActions(?string $before, Consultation $consultation): ?string
    {
        return $before;
    }

    public function getVotingAlternativeUserResults(?array $before, VotingData $votingData): ?array
    {
        return $before;
    }

    public function registerAdditionalVueVotingTemplates(?string $before, Consultation $consultation, LayoutSettings $layout): ?string
    {
        return null;
    }

    public function printVotingAlternativeSpreadsheetResults(int $rowsBefore, Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, IVotingItem $voteItem): int
    {
        return $rowsBefore;
    }
}
