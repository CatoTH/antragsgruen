<?php

class InfosController extends AntragsgruenController
{
	public function actionSelbstEinsetzen() {
		$this->layout = '//layouts/column2';

		$this->performLogin($this->createUrl("infos/neuAnlegen"));

		$reihen = Veranstaltungsreihe::model()->findAllByAttributes(array("oeffentlich" => 1));
		$this->render('selbst_einsetzen', array(
			"reihen" => $reihen
		));
	}

	public function actionImpressum() {
		$this->layout = '//layouts/column2';
		$reihen = Veranstaltungsreihe::model()->findAllByAttributes(array("oeffentlich" => 1));
		$this->render('impressum', array(
			"reihen" => $reihen
		));
	}

	public function actionNeuAnlegen() {
		$this->layout = '//layouts/column2';

		if (yii::app()->user->isGuest) $this->redirect($this->createUrl("infos/selbstEinsetzen"));
		/** @var Person $user */
		$user = Person::model()->findByAttributes(array("auth" => yii::app()->user->getId()));
		if (!$user->istWurzelwerklerIn()) $this->redirect($this->createUrl("infos/selbstEinsetzen"));

		$reihen = Veranstaltungsreihe::model()->findAllByAttributes(array("oeffentlich" => 1));
		$this->render('neu_anlegen', array(
			"reihen" => $reihen,
			"user" => $user
		));
	}
}
