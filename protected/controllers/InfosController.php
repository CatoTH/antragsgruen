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


		$anlegenformmodel = new CInstanzAnlegenForm();
		$error_str = "";

		if (AntiXSS::isTokenSet("anlegen")) {
			$anlegenformmodel->setAttributes($_REQUEST["CInstanzAnlegenForm"]);

			$reihe = new Veranstaltungsreihe();
			$reihe->subdomain = $anlegenformmodel->subdomain;
			$reihe->name = $reihe->name_kurz = $anlegenformmodel->name;
			$reihe->offiziell = false;
			$reihe->oeffentlich = true;
			$reihe->kontakt_intern = $anlegenformmodel->kontakt;
			if ($reihe->save()) {
				$veranstaltung = new Veranstaltung();
				$veranstaltung->veranstaltungsreihe = $reihe->id;
				$veranstaltung->name = $veranstaltung->name_kurz = $anlegenformmodel->name;
				$veranstaltung->antragsschluss = $anlegenformmodel->antragsschluss;
				if ($anlegenformmodel->typ == Veranstaltung::$TYP_PROGRAMM) {

				}
				if ($anlegenformmodel->typ == Veranstaltung::$TYP_PARTEITAG) {

				}
			} else {
				foreach ($reihe->errors as $err) $error_str .= $err . "<br>\n";
			}
		}

		$reihen = Veranstaltungsreihe::model()->findAllByAttributes(array("oeffentlich" => 1));
		$this->render('neu_anlegen', array(
			"reihen" => $reihen,
			"anlegenformmodel" => $anlegenformmodel,
			"error_string" => $error_str
		));
	}
}
