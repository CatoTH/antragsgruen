<?php

class AenderungsantraegeController extends AdminControllerBase {

    public function actionCreate() {
        $model = new Aenderungsantrag;

        $this->performAjaxValidation($model, 'aenderungsantrag-form');

        if (isset($_POST['Aenderungsantrag'])) {
            $model->setAttributes($_POST['Aenderungsantrag']);
            $relatedData = array();

            if ($model->saveWithRelated($relatedData)) {
                $id = $model->id;
                UnterstuetzerWidget::saveUnterstuetzerWidget($model, $messages, "AenderungsantragUnterstuetzer", "aenderungsantrag_id", $id);

                if (Yii::app()->getRequest()->getIsAjaxRequest())
                    Yii::app()->end();

                $this->redirect(array('update', 'id' => $model->id));
            }
        }

        $this->render('create', array( 'model' => $model));
    }

    public function actionUpdate($id) {
        /** @var $model Aenderungsantrag */
        $model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzer", "aenderungsantragUnterstuetzer.unterstuetzer")->findByPk($id, '', array("order" => "`unterstuetzer`.`name"));

        $this->performAjaxValidation($model, 'aenderungsantrag-form');

        $messages = array();

		if (AntiXSS::isTokenSet("antrag_freischalten")) {
			$newvar = AntiXSS::getTokenVal("antrag_freischalten");
			$model->revision_name = $newvar;
			if ($model->status == IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT) $model->status = IAntrag::$STATUS_EINGEREICHT_GEPRUEFT;
			$model->save();
			Yii::app()->user->setFlash("success", "Der Änderungsantrag wurde freigeschaltet.");
		}

		if (isset($_POST['Aenderungsantrag'])) {
            $model->setAttributes($_POST['Aenderungsantrag']);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum_einreichung = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_einreichung");
			$model->datum_beschluss = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_beschluss");

			$relatedData = array();

            if ($model->saveWithRelated($relatedData)) {
                UnterstuetzerWidget::saveUnterstuetzerWidget($model, $messages, "AenderungsantragUnterstuetzer", "aenderungsantrag_id", $id);

                $model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzer", "aenderungsantragUnterstuetzer.unterstuetzer")->findByPk($id, '', array("order" => "`unterstuetzer`.`name"));
            }
        }

        $this->render('update', array(
            'model' => $model,
            'messages' => $messages,
        ));
    }

    public function actionDelete($id) {
        if (Yii::app()->getRequest()->getIsPostRequest()) {
            $this->loadModel($id, 'Aenderungsantrag')->delete();

            if (!Yii::app()->getRequest()->getIsAjaxRequest())
                $this->redirect(array('admin'));
        } else
            throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
    }

    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Aenderungsantrag');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionAdmin() {
        $model = new Aenderungsantrag('search');
        $model->unsetAttributes();

        if (isset($_GET['Aenderungsantrag']))
            $model->setAttributes($_GET['Aenderungsantrag']);

        $this->render('admin', array(
            'model' => $model,
        ));
    }

}