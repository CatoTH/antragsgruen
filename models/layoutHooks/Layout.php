<?php

declare(strict_types=1);

namespace app\models\layoutHooks;

use app\models\db\{Amendment, Consultation, ConsultationMotionType, ConsultationText, IMotion, ISupporter, IVotingItem, Motion, MotionSection, Site, User};
use app\models\proposedProcedure\AgendaVoting;
use app\models\settings\{VotingData, Layout as LayoutSettings};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Layout
{
    public const CONTEXT_MOTION_LIST = 1;
    public const CONTEXT_MOTION = 2;

    /** @var Hooks[] */
    private static array $hooks = [];

    public static function addHook(Hooks $hook): void
    {
        // StdHooks is added by default; if another set of hooks is added that extends from StdHooks, remove it to prevent methods running twice
        $class = get_class($hook);
        while ($class = get_parent_class($class)) {
            self::$hooks = array_values(array_filter(self::$hooks, fn(Hooks $searchHook) => get_class($searchHook) !== $class));
        }

        if (!in_array($hook, self::$hooks)) {
            self::$hooks[] = $hook;
        }
    }

    private static function callHook(string $name, array $args = [], mixed $initValue = ''): mixed
    {
        $out = $initValue;
        foreach (self::$hooks as $hook) {
            $callArgs = array_merge([$out], $args);
            /** @phpstan-ignore-next-line - this is actually correct */
            $out = call_user_func_array([$hook, $name], $callArgs);
        }
        return $out;
    }

    public static function beforePage(): string
    {
        return self::callHook('beforePage');
    }

    public static function beginPage(): string
    {
        return self::callHook('beginPage');
    }

    public static function favicons(): string
    {
        return self::callHook('favicons');
    }

    public static function endOfHead(?Consultation $consultation): string
    {
        return self::callHook('endOfHead', [$consultation]);
    }

    public static function logoRow(): string
    {
        return self::callHook('logoRow');
    }

    public static function squareLogoPath(): ?string
    {
        return self::callHook('squareLogoPath', [], null);
    }

    public static function beforeContent(): string
    {
        return self::callHook('beforeContent');
    }

    public static function afterContent(): string
    {
        return self::callHook('afterContent');
    }

    public static function beginContent(): string
    {
        return self::callHook('beginContent');
    }

    public static function endPage(): string
    {
        return self::callHook('endPage');
    }

    public static function renderSidebar(): string
    {
        return self::callHook('renderSidebar');
    }

    public static function getSearchForm(): string
    {
        return self::callHook('getSearchForm');
    }

    public static function getAntragsgruenAd(): string
    {
        return self::callHook('getAntragsgruenAd');
    }

    /**
     * @param ConsultationMotionType[] $motionTypes
     * @return string
     */
    public static function setSidebarCreateMotionButton(array $motionTypes): string
    {
        return self::callHook('setSidebarCreateMotionButton', [$motionTypes]);
    }

    public static function getStdNavbarHeader(): string
    {
        return self::callHook('getStdNavbarHeader');
    }

    public static function footerLine(): string
    {
        return self::callHook('footerLine');
    }

    public static function breadcrumbs(): string
    {
        return self::callHook('breadcrumbs');
    }

    public static function beforeMotionView(Motion $motion): string
    {
        return self::callHook('beforeMotionView', [$motion]);
    }

    public static function afterMotionView(Motion $motion): string
    {
        return self::callHook('afterMotionView', [$motion]);
    }

    public static function getMotionAlternativeComments(Motion $motion): string
    {
        return self::callHook('getMotionAlternativeComments', [$motion]);
    }

    public static function getMotionFormattedAmendmentList(Motion $motion): string
    {
        return self::callHook('getMotionFormattedAmendmentList', [$motion]);
    }

    public static function getMotionViewData(array $motionData, Motion $motion): array
    {
        return self::callHook('getMotionViewData', [$motion], $motionData);
    }

    public static function getMotionExportData(array $motionData, Motion $motion): array
    {
        return self::callHook('getMotionExportData', [$motion], $motionData);
    }

    public static function getAmendmentViewData(array $amendmentData, Amendment $amendment): array
    {
        return self::callHook('getAmendmentViewData', [$amendment], $amendmentData);
    }

    public static function getAmendmentExportData(array $amendmentData, Amendment $amendment): array
    {
        return self::callHook('getAmendmentExportData', [$amendment], $amendmentData);
    }

    public static function getAmendmentBookmarkName(Amendment $amendment): string
    {
        return self::callHook('getAmendmentBookmarkName', [$amendment], '');
    }

    public static function getAmendmentAlternativeComments(Amendment $amendment): string
    {
        return self::callHook('getAmendmentAlternativeComments', [$amendment]);
    }

    public static function getConsultationPreWelcome(): string
    {
        return self::callHook('getConsultationPreWelcome', [], '');
    }

    public static function getConsultationWelcomeReplacer(): ?string
    {
        return self::callHook('getConsultationWelcomeReplacer', [], null);
    }

    public static function getFormattedMotionStatus(string $origStatus, Motion $motion): string
    {
        return self::callHook('getFormattedMotionStatus', [$motion], $origStatus);
    }

    public static function getFormattedAmendmentStatus(string $origStatus, Amendment $amendment): string
    {
        return self::callHook('getFormattedAmendmentStatus', [$amendment], $origStatus);
    }

    public static function getFormattedTitlePrefix(?string $origVersion, IMotion $imotion, ?int $context): ?string
    {
        return self::callHook('getFormattedTitlePrefix', [$imotion, $context], $origVersion);
    }

    public static function getFormattedMotionVersion(string $origVersion, Motion $motion): string
    {
        return self::callHook('getFormattedMotionVersion', [$motion], $origVersion);
    }

    public static function getFormattedUsername(string $origName, User $user): string
    {
        return self::callHook('getFormattedUsername', [$user], $origName);
    }

    public static function getConsultationMotionLineContent(string $origLine, Motion $motion): string
    {
        return self::callHook('getConsultationMotionLineContent', [$motion], $origLine);
    }

    public static function getConsultationAmendmentLineContent(string $origLine, Amendment $amendment): string
    {
        return self::callHook('getConsultationAmendmentLineContent', [$amendment], $origLine);
    }

    public static function getMotionDetailsInitiatorName(string $origLine, ISupporter $supporter): string
    {
        return self::callHook('getMotionDetailsInitiatorName', [$supporter], $origLine);
    }

    public static function getSupporterNameWithOrga(ISupporter $supporter): string
    {
        return self::callHook('getSupporterNameWithOrga', [$supporter], '');
    }

    public static function getSupporterNameWithResolutionDate(ISupporter $supporter, bool $html): string
    {
        return self::callHook('getSupporterNameWithResolutionDate', [$supporter, $html], '');
    }

    public static function getAdminIndexHint(Consultation $consultation): string
    {
        return self::callHook('getAdminIndexHint', [$consultation]);
    }

    public static function getContentPageContent(ConsultationText $text, bool $admin, string $default): string
    {
        return self::callHook('getContentPageContent', [$text, $admin], $default);
    }

    /**
     * @return string[]
     */
    public static function getSitewidePublicWarnings(Site $site): array
    {
        return self::callHook('getSitewidePublicWarnings', [$site], []);
    }

    /**
     * @return string[]
     */
    public static function getConsultationwidePublicWarnings(Consultation $consultation): array
    {
        return self::callHook('getConsultationwidePublicWarnings', [$consultation], []);
    }

    public static function renderMotionSection(MotionSection $section, Motion $motion): ?string
    {
        return self::callHook('renderMotionSection', [$section, $motion], null);
    }

    public static function getMotionPublishedInitiatorEmail(Motion $motion): ?array
    {
        return self::callHook('getMotionPublishedInitiatorEmail', [$motion], null);
    }

    public static function getAmendmentPublishedInitiatorEmail(Amendment $amendment): ?array
    {
        return self::callHook('getAmendmentPublishedInitiatorEmail', [$amendment], null);
    }

    public static function getAdditionalUserAdministrationVueTemplate(Consultation $consultation): ?string
    {
        return self::callHook('getAdditionalUserAdministrationVueTemplate', [$consultation], '');
    }

    public static function registerAdditionalVueUserAdministrationTemplates(Consultation $consultation, LayoutSettings $layout): void
    {
        self::callHook('registerAdditionalVueUserAdministrationTemplates', [$consultation, $layout], null);
    }

    public static function getVotingAlternativeAdminHeader(Consultation $consultation): ?string
    {
        return self::callHook('getVotingAlternativeAdminHeader', [$consultation], null);
    }

    public static function getVotingAdditionalActions(Consultation $consultation): ?string
    {
        return self::callHook('getVotingAdditionalActions', [$consultation], null);
    }

    public static function getVotingAlternativeResults(Consultation $consultation): ?string
    {
        return self::callHook('getVotingAlternativeResults', [$consultation], null);
    }

    public static function getVotingAlternativeUserResults(VotingData $votingData): ?array
    {
        return self::callHook('getVotingAlternativeUserResults', [$votingData], null);
    }

    public static function registerAdditionalVueVotingTemplates(Consultation $consultation, LayoutSettings $layout): void
    {
        self::callHook('registerAdditionalVueVotingTemplates', [$consultation, $layout], null);
    }

    public static function printVotingAlternativeSpreadsheetResults(Worksheet $worksheet, int $startRow, AgendaVoting $agendaVoting, IVotingItem $voteItem): int
    {
        return self::callHook('printVotingAlternativeSpreadsheetResults', [$worksheet, $startRow, $agendaVoting, $voteItem], 0);
    }
}
