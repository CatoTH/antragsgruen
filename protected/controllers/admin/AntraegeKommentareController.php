<?php

class AntraegeKommentareController extends AdminControllerBase {

    public function actionCreate() {
        $model = new AntragKommentar;

        $this->performAjaxValidation($model, 'antrag-kommentar-form');

        if (isset($_POST['AntragKommentar'])) {
            $model->setAttributes($_POST['AntragKommentar']);

            if ($model->save()) {
                if (Yii::app()->getRequest()->getIsAjaxRequest())
                    Yii::app()->end();
                else
                    $this->redirect(array('update', 'id' => $model->id));
            }
        }

        $this->render('create', array( 'model' => $model));
    }

    public function actionUpdate($id) {
		/** @var AntragKommentar $model  */
        $model = $this->loadModel($id, 'AntragKommentar');

		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Kommentar wurde nicht gefunden.");
			$this->redirect("/admin/antraegeKommentare/");
		}

        $this->performAjaxValidation($model, 'antrag-kommentar-form');

        if (isset($_POST['AntragKommentar'])) {
            $model->setAttributes($_POST['AntragKommentar'], false);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum = EDateTimePicker::parseInput($_POST["AntragKommentar"], "datum");

			if ($model->save()) {
                $this->redirect(array('update', 'id' => $model->id));
            }
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    public function actionDelete($id) {
        if (Yii::app()->getRequest()->getIsPostRequest()) {
            $this->loadModel($id, 'AntragKommentar')->delete();

            if (!Yii::app()->getRequest()->getIsAjaxRequest())
                $this->redirect(array('admin'));
        } else
            throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
    }

    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('AntragKommentar');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionAdmin() {
        $model = new AntragKommentar('search');
        $model->unsetAttributes();

        if (isset($_GET['AntragKommentar']))
            $model->setAttributes($_GET['AntragKommentar']);

        $this->render('admin', array(
            'model' => $model,
        ));
    }

}