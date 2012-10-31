<?php

class VeranstaltungenController extends AdminControllerBase {

	public function actionCreate() {
		$model = new Veranstaltung;

		$this->performAjaxValidation($model, 'veranstaltung-form');

		if (isset($_POST['Veranstaltung'])) {
			$model->setAttributes($_POST['Veranstaltung']);
			$relatedData = array();

			if ($model->saveWithRelated($relatedData)) {
				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('update', 'id' => $model->id));
			}
		}

		$this->render('create', array( 'model' => $model));
	}

	public function actionUpdate($id) {
		/** @var Veranstaltung $model  */
		$model = $this->loadModel($id, 'Veranstaltung');

		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Die angegebene Veranstaltungen wurde nicht gefunden.");
			$this->redirect("/admin/veranstaltungen/");
		}

		$this->performAjaxValidation($model, 'veranstaltung-form');

		if (isset($_POST['Veranstaltung'])) {
			$model->setAttributes($_POST['Veranstaltung']);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->antragsschluss = EDateTimePicker::parseInput($_POST["Veranstaltung"], "antragsschluss");
			$relatedData = array();

			if ($model->saveWithRelated($relatedData)) {
				$this->redirect(array('update', 'id' => $model->id));
			}
		}

		$this->render('update', array(
				'model' => $model,
				));
	}

	public function actionDelete($id) {
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'Veranstaltung')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() {
		$dataProvider = new CActiveDataProvider('Veranstaltung');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
		$this->layout = "bootstrap";
	}

	public function actionAdmin() {
		$model = new Veranstaltung('search');
		$model->unsetAttributes();

		if (isset($_GET['Veranstaltung']))
			$model->setAttributes($_GET['Veranstaltung']);

		$this->render('admin', array(
			'model' => $model,
		));
	}

}