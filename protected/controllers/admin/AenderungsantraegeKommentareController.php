<?php

class AenderungsantraegeKommentareController extends AdminControllerBase {

    public function actionCreate() {
        $model = new AenderungsantragKommentar;

        $this->performAjaxValidation($model, 'aenderungsantrag-kommentar-form');

        if (isset($_POST['AenderungsantragKommentar'])) {
            $model->setAttributes($_POST['AenderungsantragKommentar'], false);

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
		/** @var AenderungsantragKommentar $model  */
        $model = $this->loadModel($id, 'AenderungsantragKommentar');

		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Kommentar wurde nicht gefunden.");
			$this->redirect("/admin/aenderungsantraegeKommentare/");
		}

        $this->performAjaxValidation($model, 'aenderungsantrag-kommentar-form');

        if (isset($_POST['AenderungsantragKommentar'])) {
            $model->setAttributes($_POST['AenderungsantragKommentar'], false);
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
            $this->loadModel($id, 'AenderungsantragKommentar')->delete();

            if (!Yii::app()->getRequest()->getIsAjaxRequest())
                $this->redirect(array('admin'));
        } else
            throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
    }

    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('AenderungsantragKommentar');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionAdmin() {
        $model = new AenderungsantragKommentar('search');
        $model->unsetAttributes();

        if (isset($_GET['AenderungsantragKommentar']))
            $model->setAttributes($_GET['AenderungsantragKommentar']);

        $this->render('admin', array(
            'model' => $model,
        ));
    }

}