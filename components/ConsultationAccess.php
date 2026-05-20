<?php

declare(strict_types=1);

namespace app\components;

use app\controllers\ConsultationController;
use app\models\db\{Consultation, User};
use app\models\settings\{AntragsgruenApp, Privileges};
use app\plugins\ModuleBase;

class ConsultationAccess
{
    public function __construct(
        private readonly ?Consultation $consultation,
    ) {
    }

    /**
     * @return array{denied: bool, deniedRedirect?: string, limitedAccessBecauseOfOverride?: bool}
     */
    public function testForDenyReason(?string $actionId): array
    {
        $maintenance = $this->testMaintenanceMode($actionId);
        if ($maintenance['denied']) {
            return $maintenance;
        }

        $forcedLogin = $this->testSiteForcedLogin();
        if ($forcedLogin['denied']) {
            return $forcedLogin;
        }

        return $this->testConsultationPwd();
    }

    /**
     * @return array{denied: bool, deniedRedirect?: string, limitedAccessBecauseOfOverride?: bool}
     */
    public function testConsultationPwd(): array
    {
        if (!RequestContext::getYiiUser()->getIsGuest()) {
            return ['denied' => false];
        }
        if (!$this->consultation || !$this->consultation->getSettings()->accessPwd) {
            return ['denied' => false];
        }
        $pwdChecker = new ConsultationAccessPassword($this->consultation);
        if (!$pwdChecker->isCookieLoggedIn()) {
            $loginUrl = UrlHelper::createUrl([
                '/user/login',
                'backUrl'   => RequestContext::getWebRequest()->url,
                'passConId' => $this->consultation->urlPath,
            ]);
            return ['denied' => true, 'deniedRedirect' => $loginUrl];
        } else {
            return ['denied' => false];
        }
    }

    /**
     * @return array{allowed: bool, limitedAccessBecauseOfOverride?: bool}
     */
    private function allowAccessToProtectedPage(?User $user): array
    {
        if (User::havePrivilege($this->consultation, Privileges::PRIVILEGE_SITE_ADMIN, null)) {
            return ['allowed' => true];
        }

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            /** @var ModuleBase $plugin */
            $access = $plugin::canAccessConsultationAsUnprivilegedUser($user, $this->consultation, get_class($this), $this->action->id);
            if ($access !== null) {
                return ['allowed' => $access, 'limitedAccessBecauseOfOverride' => $access];
            }
        }

        return ['allowed' => false];
    }

    /**
     * @return array{denied: bool, deniedRedirect?: string, limitedAccessBecauseOfOverride?: bool}
     */
    public function testSiteForcedLogin(): array
    {
        if ($this->consultation === null) {
            return ['denied' => false];
        }
        if (!$this->consultation->getSettings()->forceLogin) {
            return ['denied' => false];
        }

        $user = User::getCurrentUser();
        if ($user === null || RequestContext::getYiiUser()->getIsGuest()) {
            return ['denied' => true, 'deniedRedirect' => UrlHelper::createUrl(['/user/login', 'backUrl' => $_SERVER['REQUEST_URI']])];
        }

        if ($this->consultation->getSettings()->managedUserAccounts) {
            if (count($user->getUserGroupsForConsultation($this->consultation)) === 0) {
                // Allow plugins to grant limited access for specific sub-pages to users even if they are not regularily allowed to access.
                $restrictedAccess = $this->allowAccessToProtectedPage($user);
                if ($restrictedAccess['allowed']) {
                    return ['denied' => false, 'limitedAccessBecauseOfOverride' => $restrictedAccess['limitedAccessBecauseOfOverride']];
                }

                return ['denied' => true, 'deniedRedirect' => UrlHelper::createUrl('/user/consultationaccesserror', $this->consultation)];
            }
        }

        $site = $this->consultation->site;
        if ($site && !in_array($user->getAuthType(), $site->getSettings()->loginMethods)) {
            return ['denied' => true, 'deniedRedirect' => UrlHelper::createUrl('/user/consultationaccesserror', $this->consultation)];
        }

        return ['denied' => false];
    }

    /**
     * @return array{denied: bool, deniedRedirect?: string}
     */
    public function testMaintenanceMode(?string $actionId): array
    {
        if ($this->consultation === null) {
            return ['denied' => false];
        }

        if (get_class($this) === ConsultationController::class && $actionId === ConsultationController::VIEW_ID_INDEX) {
            // On home, the actual error is shown on the regular page
            return ['denied' => false];
        }
        $settings = $this->consultation->getSettings();
        $admin = User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null);
        if ($settings->maintenanceMode && !$admin) {
            return ['denied' => true, 'deniedRedirect' => UrlHelper::createUrl(['/consultation/index'])];
        }
        return ['denied' => false];
    }
}
