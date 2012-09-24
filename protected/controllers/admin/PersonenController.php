<?php

class PersonenController extends AdminControllerBase {

    public function actionCreate() {
        $model = new Person;

        $this->performAjaxValidation($model, 'person-form');

        if (isset($_POST['Person'])) {
            $model->setAttributes($_POST['Person']);
            $relatedData = array(
                'aenderungsantraege' => $_POST['Person']['aenderungsantraege'] === '' ? null : $_POST['Person']['aenderungsantraege'],
                'antraege' => $_POST['Person']['antraege'] === '' ? null : $_POST['Person']['antraege'],
                'veranstaltungen' => $_POST['Person']['veranstaltungen'] === '' ? null : $_POST['Person']['veranstaltungen'],
            );

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
		/** @var Person $model */
		$model = $this->loadModel($id, 'Person');

        $this->performAjaxValidation($model, 'person-form');

        if (isset($_POST['Person'])) {
            $model->setAttributes($_POST['Person']);
            $relatedData = array(
                'aenderungsantraege' => $_POST['Person']['aenderungsantraege'] === '' ? null : $_POST['Person']['aenderungsantraege'],
                'antraege' => $_POST['Person']['antraege'] === '' ? null : $_POST['Person']['antraege'],
                'veranstaltungen' => $_POST['Person']['veranstaltungen'] === '' ? null : $_POST['Person']['veranstaltungen'],
            );

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
            $this->loadModel($id, 'Person')->delete();

            if (!Yii::app()->getRequest()->getIsAjaxRequest())
                $this->redirect(array('admin'));
        } else
            throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
    }

    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Person');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionAdmin() {
        $model = new Person('search');
        $model->unsetAttributes();

        if (isset($_GET['Person']))
            $model->setAttributes($_GET['Person']);

        $this->render('admin', array(
            'model' => $model,
        ));
    }

}