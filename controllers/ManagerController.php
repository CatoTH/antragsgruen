<?php

namespace app\controllers;

use app\models\Site;
use Yii;
use yii\helpers\Html;

class ManagerController extends Base
{

    protected function addSidebar() {
        $sites = Site::getSidebarSites();

        $html = "<ul class='nav nav-list einsatzorte-list'>";
        $html .= "<li class='nav-header'>Aktuelle Einsatzorte</li>";
        foreach ($sites as $site) {
            $html .= "<li>" . Html::a($site->title, $this->createUrl("consultation/index", array("site_id" => $site->subdomain))) . "</li>\n";
        }
        $html .= '</ul>';
        $this->menus_html[] = $html;

    }

    public function actionIndex()
    {
        $this->layout = 'column2';

        //$this->performLogin($this->createUrl("manager/index"));
        $this->addSidebar();
        return $this->render('index');

    }

    public function actionError($x) {
        echo $x;
    }

}
