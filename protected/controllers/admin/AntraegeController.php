<?php

class AntraegeController extends GxController
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

		/** @var $model Antrag */
		$model = Antrag::model()->with("antragUnterstuetzerInnen", "antragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));
		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("admin/antraege"));
		}
		if ($model->veranstaltung_id != $this->veranstaltung->id) return;

		$this->performAjaxValidation($model, 'antrag-form');

		$messages = array();

		if (AntiXSS::isTokenSet("antrag_freischalten")) {
			$newvar = AntiXSS::getTokenVal("antrag_freischalten");
			$model->revision_name = $newvar;
			if ($model->status == IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT) $model->status = IAntrag::$STATUS_EINGEREICHT_GEPRUEFT;
			$model->save();
			Yii::app()->user->setFlash("success", "Der Antrag wurde freigeschaltet.");
		}

		if (isset($_POST['Antrag'])) {
			$model->setAttributes($_POST['Antrag'], false);
			$model->text = HtmlBBcodeUtils::bbcode_normalize($model->text);
			$model->begruendung = HtmlBBcodeUtils::bbcode_normalize($model->begruendung);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum_einreichung = EDateTimePicker::parseInput($_POST["Antrag"], "datum_einreichung");
			$model->datum_beschluss = EDateTimePicker::parseInput($_POST["Antrag"], "datum_beschluss");

			$relatedData = array(
			);

			if ($model->saveWithRelated($relatedData)) {
				UnterstuetzerInnenWidget::saveUnterstuetzerInnenWidget($model, $messages, "AntragUnterstuetzerInnen", "antrag_id", $id);

				$model = Antrag::model()->with("antragUnterstuetzerInnen", "antragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));
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

		/** @var Antrag $antrag */
		$antrag = $this->loadModel($id, 'Antrag');
		if ($antrag->veranstaltung_id != $this->veranstaltung->id) return;

		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$antrag->status = IAntrag::$STATUS_GELOESCHT;
			$antrag->save();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$dataProvider = new CActiveDataProvider('Antrag');
		$dataProvider->sort->defaultOrder = "datum_einreichung DESC";
		$dataProvider->criteria->condition = "status != " . IAntrag::$STATUS_GELOESCHT . " AND veranstaltung_id = " . IntVal($this->veranstaltung->id);

		$this->render('index', array(
			'dataProvider' => $dataProvider,
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

		$model = new Antrag('search');
		$model->unsetAttributes();

		if (isset($_GET['Antrag']))
			$model->setAttributes($_GET['Antrag']);

		$model->veranstaltung_id = $this->veranstaltung->id;
		$model->veranstaltung = $this->veranstaltung;

		$this->render('admin', array(
			'model' => $model,
		));
	}

}
