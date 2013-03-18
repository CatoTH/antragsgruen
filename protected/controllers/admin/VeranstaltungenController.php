<?php

class VeranstaltungenController extends GxController
{


	public function filters()
	{
		return array(
			'accessControl',
		);
	}

	public function accessRules()
	{
		return array(
			array('allow',
				'actions'    => array('minicreate', 'create', 'update', 'admin', 'delete', 'index', 'view'),
				//'roles'=>array('admin'),
				'expression' => function ($user, $rule) {
					/* @var $user CWebUser */
					return ($user->getState("role") === "admin");
				}
			),
			array('allow',
				'actions' => array('update'),
				//'roles'=>array('admin'),
				'users'   => array('*'),
			),
			array('deny',
				'users' => array('*'),
			),
		);
	}


	public function actionCreate()
	{
		$model = new Veranstaltung;

		$this->performAjaxValidation($model, 'veranstaltung-form');

		if (isset($_POST['Veranstaltung'])) {
			$model->setAttributes($_POST['Veranstaltung'], false);

			$einstellungen = $model->getEinstellungen();
			$model->setEinstellungen($einstellungen);

			$relatedData = array();

			if ($model->saveWithRelated($relatedData)) {
				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('update', 'id' => $model->id));
			}
		}

		$this->render('create', array('model' => $model));
	}

	public function actionUpdate($id)
	{
		/** @var Veranstaltung $model */
		$model = $this->loadModel($id, 'Veranstaltung');
		$this->loadVeranstaltung($id);
		if (!$model->isAdminCurUser()) return;

		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Die angegebene Veranstaltungen wurde nicht gefunden.");
			$this->redirect($this->createUrl("admin/veranstaltungen"));
		}

		$this->performAjaxValidation($model, 'veranstaltung-form');

		if (isset($_POST['Veranstaltung'])) {
			$model->setAttributes($_POST['Veranstaltung']);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->antragsschluss = EDateTimePicker::parseInput($_POST["Veranstaltung"], "antragsschluss");

			$einstellungen = $model->getEinstellungen();
			$model->setEinstellungen($einstellungen);
			
			$relatedData           = array();

			if ($model->saveWithRelated($relatedData)) {
				$this->redirect(array('update', 'id' => $model->id));
			}
		}

		$this->render('update', array(
			'model'      => $model,
			"superadmin" => (yii::app()->user->getState("role") === "admin"),
		));
	}

	public function actionDelete($id)
	{
		/** @var Veranstaltung $model */
		$model = $this->loadModel($id, 'Veranstaltung');
		if (!$model->isAdminCurUser()) return;

		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'Veranstaltung')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex()
	{


		$dataProvider = new CActiveDataProvider('Veranstaltung');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
		$this->layout = "bootstrap";
	}

	public function actionAdmin()
	{
		$model = new Veranstaltung('search');
		$model->unsetAttributes();

		if (isset($_GET['Veranstaltung']))
			$model->setAttributes($_GET['Veranstaltung']);

		$this->render('admin', array(
			'model' => $model,
		));
	}

}