<?php

class TexteController extends GxController {


	public function actionView($veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		/** @var Texte $text  */
		$text = $this->loadModel($id, 'Texte');
		if ($text->veranstaltung->id != $this->veranstaltung->id) return;

		$this->render('view', array(
			'model' => $this->loadModel($id, 'Texte'),
		));
	}

	public function actionCreate($veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		/** @var $model Texte */
		$model = new Texte;

		if (isset($_REQUEST["key"])) $model->text_id = $_REQUEST["key"];

		if (isset($_POST['Texte'])) {
			$model->setAttributes($_POST['Texte']);
			$model->veranstaltung = $this->veranstaltung;
			$model->veranstaltung_id = $this->veranstaltung->id;

			if ($model->save()) {
				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('view', 'id' => $model->id));
			}
		}

		$this->render('create', array( 'model' => $model));
	}

	public function actionUpdate($veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		/** @var Texte $model  */
		$model = $this->loadModel($id, 'Texte');
		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Text wurde nicht gefunden.");
			$this->redirect($this->createUrl("/admin/texte/"));
		}
		if (!$model->veranstaltung->id != $this->veranstaltung->id) {
			Yii::app()->user->setFlash("error", "Dieser Text gehört nicht zur Veranstaltung.");
			$this->redirect($this->createUrl("/admin/texte/"));
		}

		if (isset($_POST['Texte'])) {
			$model->setAttributes($_POST['Texte']);

			if ($model->save()) {
				$this->redirect(array('view', 'id' => $model->id));
			}
		}

		$this->render('update', array(
				'model' => $model,
				));
	}

	public function actionDelete($veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		/** @var Texte $text  */
		$text = $this->loadModel($id, 'Texte');
		if (!$text->veranstaltung->id != $this->veranstaltung->id) return;

		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$text->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex($veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		$criteria = new CDbCriteria;
		$criteria->compare('veranstaltung_id', $this->veranstaltung->id);
		$dataProvider = new CActiveDataProvider('Texte', array("criteria" => $criteria));
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}

	public function actionAdmin($veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		$model = new Texte('search');
		$model->unsetAttributes();

		if (isset($_GET['Texte']))
			$model->setAttributes($_GET['Texte']);

		$this->render('admin', array(
			'model' => $model,
		));
	}

}