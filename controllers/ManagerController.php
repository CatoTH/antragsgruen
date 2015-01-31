<?php

namespace app\controllers;

use app\models\db\Site;
use app\models\db\User;
use Yii;
use yii\helpers\Html;

class ManagerController extends Base
{
    /**
     *
     */
    protected function addSidebar()
    {
        $sites = Site::getSidebarSites();

        $html = "<ul class='nav nav-list einsatzorte-list'>";
        $html .= "<li class='nav-header'>Aktuelle Einsatzorte</li>";
        foreach ($sites as $site) {
            $url = $this->createUrl(['consultation/index', "site_id" => $site->subdomain]);
            $html .= "<li>" . Html::a($site->title, $url) . "</li>\n";
        }
        $html .= '</ul>';
        $this->layoutParams->menus_html[] = $html;

    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'column2';

        //$this->performLogin($this->createUrl("manager/index"));
        $this->addSidebar();
        return $this->render('index');

    }

    /**
     * @return string
     */
    public function actionCreatesite()
    {

        $this->layout = 'column2';

        //$this->performLogin($this->createUrl("manager/index"));
        $this->addSidebar();


        if (Yii::$app->user->isGuest) {
            $this->redirect($this->createUrl("manager/index"));
        }

        /** @var User $user */
        $user = yii::$app->user->identity;

        if (!$user->isWurzelwerkUser()) {
            $this->redirect($this->createUrl("manager/index"));
        }

        $model     = new \app\models\forms\SiteCreateForm();
        $error_str = "";

        return $this->render(
            'createsite',
            [
                "model"        => $model,
                "error_string" => $error_str
            ]
        );

    }

    /**
     * @param int $error_code
     * @return string
     */
    public function actionError($error_code = 0)
    {
        return $error_code;
    }
}
