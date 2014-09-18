<?php

class AenderungsantraegeController extends GxController
{

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int $id
	 */
	public function actionUpdate($veranstaltungsreihe_id = "", $veranstaltung_id, $id)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		/** @var $model Aenderungsantrag */
		$model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzerInnen", "aenderungsantragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));

		if (is_null($model) || $model->status == IAntrag::$STATUS_GELOESCHT) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("/admin/aenderungsantraege"));
		}
		if ($model->antrag->veranstaltung_id != $this->veranstaltung->id) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag gehört nicht zu dieser Veranstaltung.");
			$this->redirect($this->createUrl("/admin/aenderungsantraege"));
		}

		$this->performAjaxValidation($model, 'aenderungsantrag-form');

		$messages = array();

		if (AntiXSS::isTokenSet("antrag_freischalten")) {
			$newvar               = AntiXSS::getTokenVal("antrag_freischalten");
			$model->revision_name = $newvar;
			if ($model->status == IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT) $model->status = IAntrag::$STATUS_EINGEREICHT_GEPRUEFT;
			$model->save();
			Yii::app()->user->setFlash("success", "Der Änderungsantrag wurde freigeschaltet.");

			if ($model->status == Antrag::$STATUS_EINGEREICHT_GEPRUEFT) {
				$benachrichtigt = array();
				foreach ($model->antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) if ($abo->aenderungsantraege && !in_array($abo->person_id, $benachrichtigt)) {
					$abo->person->benachrichtigenAenderungsantrag($model);
					$benachrichtigt[] = $abo->person_id;
				}
			}
		}

		if (isset($_POST['Aenderungsantrag'])) {
			if (!in_array($_POST['Aenderungsantrag']['status'], $model->getMoeglicheStati())) throw new Exception("Status-Übergang ungültig");

			$model->setAttributes($_POST['Aenderungsantrag'], false);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum_einreichung = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_einreichung");
			$model->datum_beschluss   = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_beschluss");

			if ($model->save()) {


				UnterstuetzerInnenAdminWidget::saveUnterstuetzerInnenWidget($model, $messages, "AenderungsantragUnterstuetzerInnen", "aenderungsantrag_id", $id);

				$model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzerInnen", "aenderungsantragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));
			}
		}

		$this->render('update', array(
			'model'    => $model,
			'messages' => $messages,
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int $id
	 * @throws CHttpException
	 */
	public function actionDelete($veranstaltungsreihe_id = "", $veranstaltung_id, $id)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		/** @var $model Aenderungsantrag */
		$model = $this->loadModel($id, 'Aenderungsantrag');
		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("admin/aenderungsantraege"));
		}
		if ($model->antrag->veranstaltung_id != $this->veranstaltung->id) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag gehört nicht zu dieser Veranstaltung.");
			$this->redirect($this->createUrl("admin/aenderungsantraege"));
		}

		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$model->status = IAntrag::$STATUS_GELOESCHT;
			$model->save();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect($this->createUrl("admin/aenderungsantraege"));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int|null $status
	 */
	public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id, $status = null)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		/** @var Aenderungsantrag[] $aenderungsantraege */
		$aenderungsantraege = Aenderungsantrag::model()->findAll(array(
			"with"      => "antrag",
			"alias"     => "a",
			"condition" => 'antrag.veranstaltung_id=' . IntVal($this->veranstaltung->id) . " AND a.status != " . IAntrag::$STATUS_GELOESCHT . " AND antrag.status != " . IAntrag::$STATUS_GELOESCHT
		));

		$stati      = array();
		$gesamtzahl = 0;
		foreach ($aenderungsantraege as $ae) {
			if ($ae->status == IAntrag::$STATUS_GELOESCHT) continue;
			if (!isset($stati[$ae->status])) $stati[$ae->status] = 0;
			$stati[$ae->status]++;
			$gesamtzahl++;
		}

		if ($status !== null) $aenderungsantraege = Aenderungsantrag::model()->findAll(array(
			"with"      => "antrag",
			"alias"     => "a",
			"condition" => 'antrag.veranstaltung_id=' . IntVal($this->veranstaltung->id) . " AND a.status = " . IntVal($status) . " AND a.status != " . IAntrag::$STATUS_GELOESCHT . " AND antrag.status != " . IAntrag::$STATUS_GELOESCHT
		));

		$dataProvider                            = new CActiveDataProvider('Aenderungsantrag', array(
			"data" => $aenderungsantraege
		));
		$dataProvider->getPagination()->pageSize = 50;
		$this->render('index', array(
			'dataProvider' => $dataProvider,
			'anzahl_stati'  => $stati,
			'anzahl_gesamt' => $gesamtzahl,
			'status_curr'   => $status,
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionAdmin($veranstaltungsreihe_id = "", $veranstaltung_id)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$model = new Aenderungsantrag('search');
		$model->unsetAttributes();

		if (isset($_GET['Aenderungsantrag']))
			$model->setAttributes($_GET['Aenderungsantrag']);

		$this->render('admin', array(
			'model' => $model,
		));
	}

}