<?php

namespace app\plugins\antragsgruen_sites\controllers;

use app\models\http\{HtmlErrorResponse, HtmlResponse, JsonResponse, ResponseInterface};
use app\models\settings\{AntragsgruenApp, Consultation};
use app\components\{Tools, yii\MessageSource, UrlHelper};
use app\controllers\Base;
use app\models\db\{IComment, Site, User};
use app\models\exceptions\FormError;
use app\models\forms\SiteCreateForm;

class ManagerController extends Base
{
    private function addAnonymousSidebar(): void
    {
        $this->renderPartial('sidebar_anonymous');
    }

    /**
     * @return Site[]
     */
    private function getMySites(): array
    {
        $addSiteTo = function(array &$sites, array &$siteIds, Site $site): void {
            if ($site->dateDeletion || $site->subdomain === null) {
                return;
            }
            if (in_array($site->id, $siteIds)) {
                return;
            }
            $sites[] = $site;
            $siteIds[] = $site->id;
        };

        $sortSites = function(Site $site1, Site $site2) {
            return $site2->id <=> $site1->id;
        };

        $user = User::getCurrentUser();
        $siteIds = [];

        $adminSites = [];
        foreach ($user->userGroups as $userGroup) {
            if ($userGroup->siteId && $userGroup->site) {
                $addSiteTo($adminSites, $siteIds, $userGroup->site);
            } elseif ($userGroup->consultationId && $userGroup->consultation) {
                $addSiteTo($adminSites, $siteIds, $userGroup->consultation->site);
            }
        }

        $userSites = [];
        foreach ($user->motionSupports as $motionSupport) {
            if ($motionSupport->motion && $motionSupport->motion->getMyConsultation()) {
                $addSiteTo($userSites, $siteIds, $motionSupport->motion->getMyConsultation()->site);
            }
        }
        foreach ($user->amendmentSupports as $amendmentSupport) {
            if ($amendmentSupport->amendment && $amendmentSupport->amendment->getMyConsultation()) {
                $addSiteTo($userSites, $siteIds, $amendmentSupport->amendment->getMyConsultation()->site);
            }
        }
        foreach ($user->motionComments as $motionComment) {
            if ($motionComment->status !== IComment::STATUS_DELETED && $motionComment->motion && $motionComment->motion->getMyConsultation()) {
                $addSiteTo($userSites, $siteIds, $motionComment->motion->getMyConsultation()->site);
            }
        }
        foreach ($user->amendmentComments as $amendmentComment) {
            if ($amendmentComment->status !== IComment::STATUS_DELETED && $amendmentComment->amendment && $amendmentComment->amendment->getMyConsultation()) {
                $addSiteTo($userSites, $siteIds, $amendmentComment->amendment->getMyConsultation()->site);
            }
        }

        usort($adminSites, $sortSites);
        usort($userSites, $sortSites);

        return array_merge($adminSites, $userSites);
    }

    private function addUserSidebar(bool $showAll): void
    {
        $sites = $this->getMySites();
        $this->renderPartial('sidebar_user', ['sites' => $sites, 'showAll' => $showAll]);
    }

    private function addSidebar(bool $showAll): void
    {
        if (User::getCurrentUser()) {
            $this->addUserSidebar($showAll);
        } else {
            $this->addAnonymousSidebar();
        }
    }

    public function actionIndex(): HtmlResponse
    {
        $this->layout = '@app/views/layouts/column2';
        $this->addSidebar($this->canSeeAllSites());
        if (\Yii::$app->language == 'de') {
            return new HtmlResponse($this->render('index_de'));
        } else {
            return new HtmlResponse($this->render('index_en'));
        }
    }

    public function actionCheckSubdomain(string $test): JsonResponse
    {
        $available = Site::isSubdomainAvailable($test);
        return new JsonResponse([
            'available' => $available,
            'subdomain' => $test,
        ]);
    }

    private function eligibleToCreateUser(): ?User
    {
        if (!User::getCurrentUser()) {
            return null;
        }

        $user = User::getCurrentUser();
        if ($user->status === User::STATUS_CONFIRMED) {
            return $user;
        } else {
            return null;
        }
    }

    private function requireEligibleToCreateUser(): void
    {
        if ($this->getParams()->mode == 'sandbox') {
            // In sandbox mode, everyone is allowed to create a site
            return;
        }

        $user = $this->eligibleToCreateUser();
        if (!$user) {
            $this->redirect(UrlHelper::createUrl('/antragsgruen_sites/manager/index'));
            \Yii::$app->end();
        }
    }

    public function actionCreatesite(): HtmlResponse
    {
        $this->requireEligibleToCreateUser();

        $language = $this->getRequestValue('language');
        if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
            \Yii::$app->language = $language;
        }

        $model  = new SiteCreateForm();
        $errors = [];

        $post = $this->getHttpRequest()->post();
        if (isset($post['create'])) {
            try {
                $model->setAttributes($post['SiteCreateForm']);
                if ($model->validate()) {
                    if ($this->getParams()->mode === 'sandbox') {
                        $user = $model->createSandboxUser();
                    } else {
                        $user = User::getCurrentUser();
                    }
                    $model->create($user);
                    return new HtmlResponse($this->render('created', ['form' => $model]));
                } else {
                    throw new FormError($model->getErrors());
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($this->getParams()->mode == 'sandbox') {
            $model->setSandboxParams();
        }

        return new HtmlResponse($this->render(
            'createsite',
            [
                'model'  => $model,
                'errors' => $errors
            ]
        ));
    }

    public function actionHelp(): HtmlResponse
    {
        if (\Yii::$app->language === 'de') {
            return new HtmlResponse($this->render('help_de'));
        } else {
            return new HtmlResponse($this->render('help_en'));
        }
    }

    /**
     * @return Site[]
     */
    public static function getSidebarSites(): array
    {
        if (AntragsgruenApp::getInstance()->mode == 'sandbox') {
            return [];
        }

        $shownSites = [];
        /** @var Site[] $sites */
        $sites = Site::find()->with('currentConsultation')->all();
        foreach ($sites as $site) {
            if (!$site->public) {
                continue;
            }
            if (!$site->currentConsultation) {
                continue;
            }
            if ($site->status !== Site::STATUS_ACTIVE) {
                continue;
            }
            if ($site->currentConsultation->getSettings()->robotsPolicy === Consultation::ROBOTS_NONE) {
                continue;
            }
            $shownSites[] = $site;
        }

        usort($shownSites, function (Site $site1, Site $site2) {
            $date1 = $site1->currentConsultation->dateCreation;
            $date2 = $site2->currentConsultation->dateCreation;
            return -1 * Tools::compareSqlTimes($date1, $date2);
        });

        return $shownSites;
    }

    private function canSeeAllSites(): bool
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->isGruenesNetzUser();
    }

    public function actionAllsites(): ResponseInterface
    {
        if (!$this->canSeeAllSites()) {
            return new HtmlErrorResponse(403, 'No access');
        }
        $this->layout = '@app/views/layouts/column2';
        $this->addSidebar(false);

        $years = 1;

        $sites        = $this->getSidebarSites();
        $sitesCurrent = [];
        foreach ($sites as $site) {
            $consultation = $site->currentConsultation;
            $url      = UrlHelper::createUrl(['/consultation/home', 'subdomain' => $site->subdomain]);
            $siteData = [
                'title'        => $consultation->title,
                'organization' => $site->organization,
                'url'          => $url,
            ];
            $age      = time() - Tools::dateSql2timestamp($consultation->dateCreation);
            if ($age < $years * 365 * 24 * 3600) {
                $sitesCurrent[] = $siteData;
            }
        }

        return new HtmlResponse($this->render('allsites', ['sites' => $sitesCurrent]));
    }

    public function actionLegal(): HtmlResponse
    {
        return $this->renderContentPage('legal');
    }

    public function actionPrivacy(): HtmlResponse
    {
        return $this->renderContentPage('privacy');
    }
}
