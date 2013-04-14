<?php

class AenderungsantraegeController extends GxController {

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int $id
	 */
	public function actionUpdate($veranstaltungsreihe_id = "", $veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

        /** @var $model Aenderungsantrag */
        $model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzerInnen", "aenderungsantragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));

		if (is_null($model)) {
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


				UnterstuetzerInnenWidget::saveUnterstuetzerInnenWidget($model, $messages, "AenderungsantragUnterstuetzerInnen", "aenderungsantrag_id", $id);

                $model = Aenderungsantrag::model()->with("aenderungsantragUnterstuetzerInnen", "aenderungsantragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));
            }
        }

        $this->render('update', array(
            'model' => $model,
            'messages' => $messages,
        ));
    }

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int $id
	 * @throws CHttpException
	 */
	public function actionDelete($veranstaltungsreihe_id = "", $veranstaltung_id, $id) {
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
                $this->redirect(array('admin'));
        } else
            throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
    }

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$aenderungsantraege = Aenderungsantrag::model()->with(array(
			"antrag" => array('condition'=>'antrag.veranstaltung_id=' . IntVal($this->veranstaltung->id))
		))->findAll();
        $dataProvider = new CActiveDataProvider('Aenderungsantrag', array(
			"data" => $aenderungsantraege
		));
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionAdmin($veranstaltungsreihe_id = "", $veranstaltung_id) {
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