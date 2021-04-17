<?php

namespace app\plugins\antragsgruen_sites\controllers;

use app\models\settings\AntragsgruenApp;
use app\models\settings\Consultation;
use app\components\{Tools, yii\MessageSource, UrlHelper};
use app\controllers\Base;
use app\models\db\{IComment, Site, User};
use app\models\exceptions\FormError;
use app\models\forms\SiteCreateForm;
use yii\web\Response;

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
        foreach ($user->adminSites as $adminSite) {
            $addSiteTo($adminSites, $siteIds, $adminSite);
        }
        foreach ($user->consultationPrivileges as $adminConsultation) {
            $addSiteTo($adminSites, $siteIds, $adminConsultation->consultation->site);
        }

        $userSites = [];
        foreach ($user->motionSupports as $motionSupport) {
            $addSiteTo($userSites, $siteIds, $motionSupport->motion->getMyConsultation()->site);
        }
        foreach ($user->amendmentSupports as $amendmentSupport) {
            $addSiteTo($userSites, $siteIds, $amendmentSupport->amendment->getMyConsultation()->site);
        }
        foreach ($user->motionComments as $motionComment) {
            if ($motionComment->status !== IComment::STATUS_DELETED) {
                $addSiteTo($userSites, $siteIds, $motionComment->motion->getMyConsultation()->site);
            }
        }
        foreach ($user->amendmentComments as $amendmentComment) {
            if ($amendmentComment->status !== IComment::STATUS_DELETED) {
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
        if (\Yii::$app->user->isGuest) {
            $this->addAnonymousSidebar();
        } else {
            $this->addUserSidebar($showAll);
        }
    }

    public function actionIndex(): string
    {
        $this->layout = '@app/views/layouts/column2';
        $this->addSidebar($this->canSeeAllSites());
        if (\Yii::$app->language == 'de') {
            return $this->render('index_de');
        } else {
            return $this->render('index_en');
        }
    }

    public function actionCheckSubdomain(string $test): string
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $available = Site::isSubdomainAvailable($test);
        return json_encode([
            'available' => $available,
            'subdomain' => $test,
        ]);
    }

    private function eligibleToCreateUser(): ?User
    {
        if (\Yii::$app->user->isGuest) {
            return null;
        }

        /** @var User $user */
        $user = \Yii::$app->user->identity;
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

    /**
     * @return string
     */
    public function actionCreatesite()
    {
        $this->requireEligibleToCreateUser();

        $language = $this->getRequestValue('language');
        if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
            \Yii::$app->language = $language;
        }

        $model  = new SiteCreateForm();
        $errors = [];

        $post = \Yii::$app->request->post();
        if (isset($post['create'])) {
            try {
                $model->setAttributes($post['SiteCreateForm']);
                if ($model->validate()) {
                    if ($this->getParams()->mode == 'sandbox') {
                        $user = $model->createSandboxUser();
                    } else {
                        $user = User::getCurrentUser();
                    }
                    $model->create($user);
                    return $this->render('created', ['form' => $model]);
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

        return $this->render(
            'createsite',
            [
                'model'  => $model,
                'errors' => $errors
            ]
        );
    }

    public function actionHelp(): string
    {
        if (\Yii::$app->language === 'de') {
            return $this->render('help_de');
        } else {
            return $this->render('help_en');
        }
    }

    /**
     * @return Site[]
     */
    public static function getSidebarSites()
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

    public function actionAllsites(): string
    {
        if (!$this->canSeeAllSites()) {
            $this->showErrorpage(403, 'No access');
            return '';
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

        return $this->render('allsites', ['sites' => $sitesCurrent]);
    }

    public function actionLegal(): string
    {
        return $this->renderContentPage('legal');
    }

    public function actionPrivacy(): string
    {
        return $this->renderContentPage('privacy');
    }
}
