<?php

class AntraegeController extends AdminControllerBase
{

	/*
	public function actionCreate($veranstaltung_id)
	{
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		$model = new Antrag;
		$model->veranstaltung = $this->veranstaltung->id;

		$this->performAjaxValidation($model, 'antrag-form');

		if (isset($_POST['Antrag'])) {
			$model->setAttributes($_POST['Antrag']);
			$model->text = HtmlBBcodeUtils::bbcode_normalize($model->text);
			$model->begruendung = HtmlBBcodeUtils::bbcode_normalize($model->begruendung);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum_einreichung = EDateTimePicker::parseInput($_POST["Antrag"], "datum_einreichung");
			$model->datum_beschluss = EDateTimePicker::parseInput($_POST["Antrag"], "datum_beschluss");
			$relatedData = array(
			);

			if ($model->saveWithRelated($relatedData)) {
				UnterstuetzerWidget::saveUnterstuetzerWidget($model, $messages, "AntragUnterstuetzer", "antrag_id", $model->id);

				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('update',
						'id' => $model->id));
			}
		}

		$this->render('create', array('model' => $model));
	}
	*/

	public function actionUpdate($veranstaltung_id, $id)
	{
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		/** @var $model Antrag */
		$model = Antrag::model()->with("antragUnterstuetzer", "antragUnterstuetzer.unterstuetzer")->findByPk($id, '', array("order" => "`unterstuetzer`.`name"));
		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
			$this->redirect("/admin/antraege/");
		}
		if ($model->veranstaltung != $this->veranstaltung->id) return;

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
			$model->setAttributes($_POST['Antrag']);
			$model->text = HtmlBBcodeUtils::bbcode_normalize($model->text);
			$model->begruendung = HtmlBBcodeUtils::bbcode_normalize($model->begruendung);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum_einreichung = EDateTimePicker::parseInput($_POST["Antrag"], "datum_einreichung");
			$model->datum_beschluss = EDateTimePicker::parseInput($_POST["Antrag"], "datum_beschluss");

			$relatedData = array(
			);

			if ($model->saveWithRelated($relatedData)) {
				UnterstuetzerWidget::saveUnterstuetzerWidget($model, $messages, "AntragUnterstuetzer", "antrag_id", $id);

				$model = Antrag::model()->with("antragUnterstuetzer", "antragUnterstuetzer.unterstuetzer")->findByPk($id, '', array("order" => "`unterstuetzer`.`name"));
			}
		}

		$this->render('update', array(
			'model'    => $model,
			'messages' => $messages,
		));
	}

	public function actionDelete($veranstaltung_id, $id)
	{
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		/** @var Antrag $antrag  */
		$antrag = $this->loadModel($id, 'Antrag');
		if ($antrag->veranstaltung != $this->veranstaltung->id) return;

		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$antrag->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex($veranstaltung_id)
	{
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		$dataProvider = new CActiveDataProvider('Antrag');
		$dataProvider->sort->defaultOrder = "datum_einreichung DESC";
		$dataProvider->criteria->condition = "status != " . IAntrag::$STATUS_GELOESCHT . " AND veranstaltung = " . IntVal($this->veranstaltung->id);

		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}

	public function actionAdmin()
	{
		$model = new Antrag('search');
		$model->unsetAttributes();

		if (isset($_GET['Antrag']))
			$model->setAttributes($_GET['Antrag']);

		$this->render('admin', array(
			'model' => $model,
		));
	}

}
