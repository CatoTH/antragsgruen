<?php /** @noinspection PhpUnusedParameterInspection */

namespace app\plugins;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\models\db\{Amendment, Consultation, IMotion, Motion, Site, User, Vote, VotingBlock};
use app\components\LoginProviderInterface;
use app\models\AdminTodoItem;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\http\ResponseInterface;
use app\models\layoutHooks\Hooks;
use app\models\majorityType\IMajorityType;
use app\models\policies\IPolicy;
use app\models\quorumType\IQuorumType;
use app\models\settings\{IMotionStatus, Layout, Privilege, VotingData};
use yii\base\{Action, Module};
use yii\web\{AssetBundle, Controller, View};

class ModuleBase extends Module
{
    public function init(): void
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $ref = new \ReflectionClass($this);
            $this->controllerNamespace = $ref->getNamespaceName() . '\\commands';
        }
    }

    /**
     * @return AssetBundle[]
     */
    public static function getActiveAssetBundles(Controller $controller): array
    {
        return [];
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [];
    }

    protected static function getAmendmentUrlRoutes(): array
    {
        return [];
    }

    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        return [];
    }

    public static function getDefaultRouteOverride(): ?string
    {
        return null;
    }

    public static function getGeneratedRoute(array $routeParts, string $originallyGeneratedRoute): ?string
    {
        return null;
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        foreach (static::getMotionUrlRoutes() as $url => $route) {
            $urls[$dommotion . '/' . $url] = $route;
            $urls[$dommotionOld . '/' . $url] = $route;
        }
        foreach (static::getAmendmentUrlRoutes() as $url => $route) {
            $urls[$domamend . '/' . $url] = $route;
            $urls[$domamendOld . '/' . $url] = $route;
        }

        return $urls;
    }

    public static function getSessionCookieSettings(): array
    {
        return [];
    }

    /**
     * Return value needs to be a class name extending from \app\models\settings\Permissions
     */
    public static function getPermissionsClass(): ?string
    {
        return null;
    }

    /**
     * @param Privilege[] $origPrivileges
     *
     * @return Privilege[]
     */
    public static function addCustomPrivileges(Consultation $consultation, array $origPrivileges): array
    {
        return $origPrivileges;
    }

    /**
     * @return string[]|IPolicy[]
     */
    public static function getCustomPolicies(): array
    {
        return [];
    }

    /**
     * @return class-string<\app\models\forms\AdminMotionFilterForm>|null
     */
    public static function getFullMotionListClassOverride(): ?string
    {
        return null;
    }

    /**
     * Return values:
     * - null does not change default behavior
     * - true grants access, even if no access by default. (It does NOT however grant permissions to perform any actions)
     * - false denies access, even if the user has access by default. (It does NOT remove the permissions to perform any actions in the motion administration)
     */
    public static function canSeeFullMotionList(Consultation $consultation, User $user): ?bool
    {
        return null;
    }

    public static function canSeeContactDetails(IMotion $imotion, ?User $user): ?bool
    {
        return null;
    }

    /**
     * Called if the consultation is protected, and the user (if any) does not have privileges to access.
     * Can be used to give grant users read-only access to certain motions.
     */
    public static function canAccessConsultationAsUnprivilegedUser(?User $user, Consultation $consultation, string $controllerClass, string $actionId): ?bool
    {
        return null;
    }

    /**
     * @return AdminTodoItem[]|null
     */
    public static function getAdminTodoItems(Consultation $consultation, User $user): ?array
    {
        return null;
    }

    public static function preferConsultationSpecificHomeLink(): bool
    {
        return false;
    }

    public static function siteHomeIsAlwaysPublic(): bool
    {
        return false;
    }

    public static function hasSiteHomePage(): bool
    {
        return false;
    }

    public static function getSiteHomePage(): ?ResponseInterface
    {
        return null;
    }

    /**
     * @return class-string<\app\models\settings\Consultation>|null
     */
    public static function getConsultationSettingsClass(Consultation $consultation): ?string
    {
        return null;
    }

    public static function getConsultationExtraSettingsForm(Consultation $consultation): string
    {
        return '';
    }

    public static function createDefaultUserGroups(Consultation $consultation): void
    {
    }

    /** @return null|int[] */
    public static function getSelectableGroupsForUser(Consultation $consultation, User $user): ?array
    {
        return null;
    }

    /**
     * @phpstan-ignore-next-line
     * @return class-string<\app\models\settings\Site>|null
     */
    public static function getSiteSettingsClass(Site $site): ?string
    {
        return null;
    }

    /**
     * @return array<string, array{title: string, preview: string|null, bundle: class-string, hooks?: class-string<Hooks>, odtTemplate?: string}>
     */
    public static function getProvidedLayouts(?View $view = null): array
    {
        return [];
    }

    public static function overridesDefaultLayout(): ?string
    {
        return null;
    }

    public static function getProvidedPdfLayouts(array $default): array
    {
        return $default;
    }

    public static function getProvidedTranslations(): array
    {
        return [];
    }

    public static function getCustomMotionExports(Motion $motion): array
    {
        return [];
    }

    /**
     * @return Hooks[]
     */
    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [];
    }

    public static function getCustomEmailTemplate(): ?string
    {
        return null;
    }

    public static function getRobotsIndexOverride(?Consultation $consultation, Action $action, bool $default): ?bool
    {
        return null;
    }

    public static function getDefaultLogo(): ?array
    {
        return null;
    }

    public static function getExternalPasswordAuthenticator(): ?ExternalPasswordAuthenticatorInterface
    {
        return null;
    }

    public static function getDedicatedLoginProvider(): ?LoginProviderInterface
    {
        return null;
    }

    /**
     * @return array<class-string<IMajorityType>>
     */
    public static function getAdditionalMajorityTypes(): array
    {
        return [];
    }

    /**
     * @param Vote[] $votes
     *
     * @return array<int|string, array<string, int>>|null
     */
    public static function calculateVoteResultsForApi(VotingBlock $voting, array $votes): ?array
    {
        return null;
    }

    /**
     * @return class-string<VotingData>|null
     */
    public static function getVotingDataClass(Consultation $consultation): ?string
    {
        return null;
    }

    /**
     * @return IQuorumType[]|string[]
     */
    public static function getAdditionalQuorumTypes(): array
    {
        return [];
    }

    public static function userIsRelevantForQuorum(VotingBlock $votingBlock, User $user): ?bool
    {
        return null;
    }

    public static function getRelevantEligibleVotersCount(VotingBlock $votingBlock): ?int
    {
        return null;
    }

    public static function getMotionExtraSettingsForm(Motion $motion): string
    {
        return '';
    }

    public static function setMotionExtraSettingsFromForm(Motion $motion, array $post): void
    {
    }

    public static function getAmendmentExtraSettingsForm(Amendment $amendment): string
    {
        return '';
    }

    public static function setAmendmentExtraSettingsFromForm(Amendment $amendment, array $post): void
    {
    }

    /**
     * @return IMotionStatus[]
     */
    public static function getAdditionalIMotionStatuses(Consultation $consultation): array
    {
        return [];
    }

    /**
     * @return array<string, string>|null
     */
    public static function getResolutionStatusOptions(Consultation $consultation): ?array
    {
        return null;
    }

    public static function getResolutionProposer(IMotion $imotion): ?string
    {
        return null;
    }

    public static function getMotionVersions(Consultation $consultation): ?array
    {
        return null;
    }

    /**
     * @return string[]|IAmendmentNumbering[]
     */
    public static function getCustomAmendmentNumberings(): array
    {
        return [];
    }

    public static function getConsultationHomePage(Consultation $consultation): ?ResponseInterface
    {
        return null;
    }

    public static function onBeforeProposedProcedureStatusSave(IMotion $imotion): IMotion
    {
        return $imotion;
    }

    /**
     * @param IMotion[] $imotions
     *
     * @return IMotion[]|null
     *
     * Returns a sorted array, if a custom sorting is defined.
     * The first plugin to return an array takes precedence.
     * If no plugin returns an array (only null), the default sorting is applied.
     */
    public static function sortIMotions(array $imotions, int $sort): ?array
    {
        return null;
    }
}
