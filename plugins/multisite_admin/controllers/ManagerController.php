<?php

declare(strict_types=1);

namespace app\plugins\multisite_admin\controllers;

use app\models\settings\Consultation;
use app\models\http\{HtmlErrorResponse, HtmlResponse, JsonResponse, ResponseInterface};
use app\components\{Tools, yii\MessageSource, UrlHelper};
use app\controllers\Base;
use app\models\db\{Site, User};
use app\models\exceptions\FormError;
use app\models\forms\SiteCreateForm;

class ManagerController extends Base
{
    private function eligibleToCreateUser(): ?User
    {
        if (User::currentUserIsSuperuser()) {
            return User::getCurrentUser();
        } else {
            return null;
        }
    }

    private function requireEligibleToCreateUser(): void
    {
        $user = $this->eligibleToCreateUser();
        if (!$user) {
            $this->redirect(UrlHelper::createUrl('/multisite_admin/manager/index'));
            \Yii::$app->end();
        }
    }

    public function actionIndex(): ResponseInterface
    {
        $this->layout = '@app/views/layouts/column2';
        if (!$this->eligibleToCreateUser()) {
            return new HtmlErrorResponse(403, 'No access');
        }

        return new HtmlResponse($this->render('index', [
            'sites' => $this->getAllSites(),
        ]));
    }

    public function actionCheckSubdomain(string $test): JsonResponse
    {
        $this->requireEligibleToCreateUser();

        $available = Site::isSubdomainAvailable($test);
        return new JsonResponse([
            'available' => $available,
            'subdomain' => $test,
        ]);
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
                    $user = User::getCurrentUser();
                    $model->create($user);
                    return new HtmlResponse($this->render('created', ['form' => $model]));
                } else {
                    throw new FormError($model->getErrors());
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return new HtmlResponse($this->render('createsite', [
            'model'  => $model,
            'errors' => $errors
        ]));
    }

    /**
     * @return Site[]
     */
    private function getAllSites(): array
    {
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
}
