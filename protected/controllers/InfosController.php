<?php

class InfosController extends AntragsgruenController
{
	public function actionSelbstEinsetzen() {
		$this->layout = '//layouts/column2';
		$reihen = Veranstaltungsreihe::model()->findAllByAttributes(array("oeffentlich" => 1));
		$this->render('selbst_einsetzen', array(
			"reihen" => $reihen
		));
	}
}
