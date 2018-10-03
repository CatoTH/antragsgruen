<?php

namespace app\plugins\antragsgruen_sites\controllers;

use app\components\HTMLTools;
use app\components\yii\MessageSource;
use app\components\Tools;
use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\forms\SiteCreateForm;
use app\models\settings\Consultation;
use yii\helpers\Html;
use yii\web\Response;

class ManagerController extends Base
{
    /**
     *
     */
    protected function addSidebar()
    {
        $sites        = Site::getSidebarSites();
        $sitesCurrent = [];
        $sitesOld     = [];
        foreach ($sites as $site) {
            if ($site->status !== Site::STATUS_ACTIVE) {
                continue;
            }
            if (!$site->currentConsultation) {
                continue;
            }
            $consultation = $site->currentConsultation;
            if ($consultation->getSettings()->robotsPolicy === Consultation::ROBOTS_NONE) {
                continue;
            }
            
            $url      = UrlHelper::createUrl(['/consultation/home', 'subdomain' => $site->subdomain]);
            $siteData = [
                'title'        => $consultation->title,
                'organization' => $site->organization,
                'url'          => $url,
            ];
            $age      = time() - Tools::dateSql2timestamp($consultation->dateCreation);
            if ($age < 4 * 30 * 24 * 3600) {
                $sitesCurrent[] = $siteData;
            } else {
                $sitesOld[] = $siteData;
            }
        }

        $html         = '<ul class="nav nav-list current-uses-list">';
        $html         .= '<li class="nav-header">' . \Yii::t('manager', 'sidebar_curr_uses') . '</li>';
        foreach ($sitesCurrent as $data) {
            $html .= '<li>';
            if ($data['organization'] != '') {
                $html .= '<span class="orga">' . HTMLTools::encodeAddShy($data['organization']) . '</span>';
            }
            $html .= Html::a(HTMLTools::encodeAddShy($data['title']), $data['url']) . '</li>' . "\n";
        }
        $html                            .= '</ul>';
        $this->layoutParams->menusHtml[] = $html;


        $html     = '<ul class="nav nav-list current-uses-list old-uses-list">';
        $html     .= '<li class="nav-header">' . \Yii::t('manager', 'sidebar_old_uses') . '</li>';
        $html     .= '<li class="shower"><a href="#" onClick="$(\'.old-uses-list .hidden\').removeClass(\'hidden\');
            $(\'.old-uses-list .shower\').addClass(\'hidden\'); return false;" style="font-style: italic;">' .
            \Yii::t('manager', 'sidebar_old_uses_show') . '</a></li>';

        foreach ($sitesOld as $data) {
            $html .= '<li class="hidden">';
            if ($data['organization'] != '') {
                $html .= '<span class="orga">' . HTMLTools::encodeAddShy($data['organization']) . '</span>';
            }
            $html .= Html::a(HTMLTools::encodeAddShy($data['title']), $data['url']) . '</li>' . "\n";
        }
        $html                            .= '</ul>';
        $this->layoutParams->menusHtml[] = $html;
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        if (\Yii::$app->language == 'de') {
            $this->layout = '@app/views/layouts/column2';
            $this->addSidebar();
            return $this->render('index_de');
        } else {
            $this->layout = '@app/views/layouts/column1';
            return $this->render('index_en');
        }
    }

    /**
     * @param string $test
     * @return string
     */
    public function actionCheckSubdomain($test)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $available = Site::isSubdomainAvailable($test);
        return json_encode([
            'available' => $available,
            'subdomain' => $test,
        ]);
    }

    /**
     * @return null|User
     */
    protected function eligibleToCreateUser()
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

    /**
     */
    protected function requireEligibleToCreateUser()
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

    /**
     * @return string
     */
    public function actionHelp()
    {
        if (\Yii::$app->language == 'de') {
            return $this->render('help_de');
        } else {
            return $this->render('help_en');
        }
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionLegal()
    {
        return $this->renderContentPage('legal');
    }

    /**
     * @return string
     */
    public function actionPrivacy()
    {
        return $this->renderContentPage('privacy');
    }
}
