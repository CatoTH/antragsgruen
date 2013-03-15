<?php

class AenderungsantraegeController extends GxController {

	/*
    public function actionCreate() {
        $model = new Aenderungsantrag;

        $this->performAjaxValidation($model, 'aenderungsantrag-form');

        if (isset($_POST['Aenderungsantrag'])) {
            $model->setAttributes($_POST['Aenderungsantrag']);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum_einreichung = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_einreichung");
			$model->datum_beschluss = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_beschluss");
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
	*/

    public function actionUpdate($veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

        /** @var $model Aenderungsantrag */
        $model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzer", "aenderungsantragUnterstuetzer.unterstuetzer")->findByPk($id, '', array("order" => "`unterstuetzer`.`name"));

		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("/admin/aenderungsantraege"));
		}
		if ($model->antrag->veranstaltung != $this->veranstaltung->id) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag gehört nicht zu dieser Veranstaltung.");
			$this->redirect($this->createUrl("/admin/aenderungsantraege"));
		}

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
            $model->setAttributes($_POST['Aenderungsantrag'], false);
			Yii::import('ext.datetimepicker.EDateTimePicker');
			$model->datum_einreichung = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_einreichung");
			$model->datum_beschluss = EDateTimePicker::parseInput($_POST["Aenderungsantrag"], "datum_beschluss");

			if ($model->save()) {


				UnterstuetzerWidget::saveUnterstuetzerWidget($model, $messages, "AenderungsantragUnterstuetzer", "aenderungsantrag_id", $id);

                $model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzer", "aenderungsantragUnterstuetzer.unterstuetzer")->findByPk($id, '', array("order" => "`unterstuetzer`.`name"));
            }
        }

        $this->render('update', array(
            'model' => $model,
            'messages' => $messages,
        ));
    }

    public function actionDelete($veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		/** @var $model Aenderungsantrag */
		$model = $this->loadModel($id, 'Aenderungsantrag');
		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("admin/aenderungsantraege"));
		}
		if ($model->antrag->veranstaltung != $this->veranstaltung->id) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag gehört nicht zu dieser Veranstaltung.");
			$this->redirect($this->createUrl("admin/aenderungsantraege"));
		}

		if (Yii::app()->getRequest()->getIsPostRequest()) {
            $model->status = IAntrag::$STATUS_GELOESCHT;
			$model->save();

            if (!Yii::app()->getRequest()->getIsAjaxRequest())
                $this->redirect(array('admin'));
        } else
            throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
    }

    public function actionIndex($veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		$aenderungsantraege = Aenderungsantrag::model()->with(array(
			"antrag" => array('condition'=>'antrag.veranstaltung=' . IntVal($this->veranstaltung->id))
		))->findAll();
        $dataProvider = new CActiveDataProvider('Aenderungsantrag', array(
			"data" => $aenderungsantraege
		));
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionAdmin($veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

        $model = new Aenderungsantrag('search');
        $model->unsetAttributes();

        if (isset($_GET['Aenderungsantrag']))
            $model->setAttributes($_GET['Aenderungsantrag']);

        $this->render('admin', array(
            'model' => $model,
        ));
    }

}