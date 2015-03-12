<?php

class VeranstaltungenController extends GxController
{

	public function actionUpdate($veranstaltungsreihe_id = "", $veranstaltung_id)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$model = $this->veranstaltung;
		if (!$model->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

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
			$einstellungen->saveForm($_REQUEST["VeranstaltungsEinstellungen"]);
			if (isset($_REQUEST["VeranstaltungsEinstellungen"]["ae_nummerierung"])) switch ($_REQUEST["VeranstaltungsEinstellungen"]["ae_nummerierung"]) {
				case 0:
					$einstellungen->ae_nummerierung_nach_zeile = false;
					$einstellungen->ae_nummerierung_global     = false;
					break;
				case 1:
					$einstellungen->ae_nummerierung_nach_zeile = false;
					$einstellungen->ae_nummerierung_global     = true;
					break;
				case 2:
					$einstellungen->ae_nummerierung_nach_zeile = true;
					$einstellungen->ae_nummerierung_global     = false;
					break;
			}
			$model->setEinstellungen($einstellungen);

			$relatedData = array();

			if ($model->saveWithRelated($relatedData)) {
				$model->resetLineCache();
				$this->redirect(array('update'));
			}
		}

		$this->render('update', array(
			'model' => $model,
		));
	}

	public function actionUpdate_extended($veranstaltungsreihe_id = "", $veranstaltung_id)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$model = $this->veranstaltung;
		if (!$model->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Die angegebene Veranstaltungen wurde nicht gefunden.");
			$this->redirect($this->createUrl("admin/veranstaltungen"));
		}

		if (AntiXSS::isTokenSet("del_tag")) {
			foreach ($model->tags as $tag) if ($tag->id == AntiXSS::getTokenVal("del_tag")) {
				$tag->delete();
				$model->refresh();
			}
		}

		if (isset($_POST['Veranstaltung'])) {
			$model->setAttributes($_POST['Veranstaltung']);

			$einstellungen = $model->getEinstellungen();
			$einstellungen->saveForm($_REQUEST["VeranstaltungsEinstellungen"]);
			$model->setEinstellungen($einstellungen);

			$relatedData = array();

			if ($model->saveWithRelated($relatedData)) {

				$reihen_einstellungen                                     = $model->veranstaltungsreihe->getEinstellungen();
				$reihen_einstellungen->antrag_neu_nur_namespaced_accounts = (isset($_REQUEST["antrag_neu_nur_namespaced_accounts"]));
				$reihen_einstellungen->antrag_neu_nur_wurzelwerk          = (isset($_REQUEST["antrag_neu_nur_wurzelwerk"]));
				$model->veranstaltungsreihe->setEinstellungen($reihen_einstellungen);
				$model->veranstaltungsreihe->save();

				if (!$model->getEinstellungen()->admins_duerfen_aendern) {
					foreach ($model->antraege as $ant) {
						$ant->text_unveraenderlich = 1;
						$ant->save(false);
						foreach ($ant->aenderungsantraege as $ae) {
							$ae->text_unveraenderlich = 1;
							$ae->save(false);
						}
					}
				}

				if (isset($_REQUEST["tag_neu"]) && trim($_REQUEST["tag_neu"]) != "") {
					$max_id    = 0;
					$duplicate = false;
					foreach ($model->tags as $tag) {
						if ($tag->position > $max_id) $max_id = $tag->position;
						if (mb_strtolower($tag->name) == mb_strtolower($_REQUEST["tag_neu"])) $duplicate = true;
					}
					if (!$duplicate) {
						Yii::app()->db->createCommand()->insert("tags", array("veranstaltung_id" => $model->id, "name" => $_REQUEST["tag_neu"], "position" => ($max_id + 1)));
					}
				}

                if (isset($_REQUEST["TagSort"]) && is_array($_REQUEST["TagSort"])) {
                    foreach ($_REQUEST["TagSort"] as $i => $tagId) {
                        $tag = Tag::model()->findByPk($tagId);
                        if ($tag->veranstaltung_id == $this->veranstaltung->id) {
                            $tag->position = $i;
                            $tag->save();
                        }
                    }
                }

				$model->resetLineCache();
				$this->redirect(array('update_extended'));
			}
		}

		$this->render('update_extended', array(
			'model' => $model,
		));
	}


}