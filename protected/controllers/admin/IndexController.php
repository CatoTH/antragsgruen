<?php

class IndexController extends AntragsgruenController
{
	public $layout = '//layouts/column1';

	public function actionIndex($veranstaltung_id = "")
	{
		if ($veranstaltung_id == "") $veranstaltung_id = Yii::app()->params['standardVeranstaltung'];
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		$this->render('index');
	}

}