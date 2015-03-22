<?php

namespace app\controllers\admin;

use app\components\AntiXSS;
use app\components\Tools;
use app\controllers\Base;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;
use app\models\db\User;

class IndexController extends Base
{

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (\Yii::$app->user->isGuest) {
            $currUrl = \yii::$app->request->url;
            $this->redirect(UrlHelper::createLoginUrl($currUrl));
            return false;
        }
        if (!$this->consultation->isAdminCurUser()) {
            $this->showErrorpage(403, 'Kein Zugriff auf diese Seite');
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $todo = array( //			array("Text anlegen", array("admin/texte/update", array())),
        );

        if (!is_null($this->consultation) && false) {

            /** @var Motion[] $motions */
            $motions = Motion::findAll(
                [
                    "consultationId" => $this->consultation->id,
                    "status"         => Motion::STATUS_SUBMITTED_UNSCREENED
                ]
            );
            foreach ($motions as $motion) {
                $url    = UrlHelper::createUrl(['admin/motions/update', 'id' => $motion->id]);
                $todo[] = ["Antrag prüfen: " . $motion->titlePrefix . " " . $motion->title, $url];
            }

            /** @var array|Aenderungsantrag[] $aenderungs */
            $aenderungs = Aenderungsantrag::model()->with(array(
                "antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
            ))->findAllByAttributes(array("status" => Aenderungsantrag::$STATUS_EINGEREICHT_UNGEPRUEFT));
            foreach ($aenderungs as $ae) {
                $todo[] = array("Änderungsanträge prüfen: " . $ae->revision_name . " zu " . $ae->antrag->revision_name . " " . $ae->antrag->name, array("admin/aenderungsantraege/update", array("id" => $ae->id)));
            }

            $kommentare = AntragKommentar::model()->with(array(
                "antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
            ))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
            foreach ($kommentare as $komm) {
                $todo[] = array("Kommentar prüfen: " . $komm->verfasserIn->name . " zu " . $komm->antrag->revision_name, array("antrag/anzeige", array("antrag_id" => $komm->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
            }

            /** @var AenderungsantragKommentar[] $kommentare */
            $kommentare = AenderungsantragKommentar::model()->with(array(
                "aenderungsantrag"        => array("alias" => "aenderungsantrag"),
                "aenderungsantrag.antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
            ))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
            foreach ($kommentare as $komm) {
                $todo[] = array("Kommentar prüfen: " . $komm->verfasserIn->name . " zu " . $komm->aenderungsantrag->revision_name, array("aenderungsantrag/anzeige", array("aenderungsantrag_id" => $komm->aenderungsantrag->id, "antrag_id" => $komm->aenderungsantrag->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
            }

        }

        return $this->render(
            'index',
            [
                'todoList'     => $todo,
                'site'         => $this->site,
                'consultation' => $this->consultation
            ]
        );
    }


    public function actionAdmins()
    {
        /** @var User $myself */
        $myself = \Yii::$app->user->identity;

        if (isset($_POST['adduser'])) {
            /** @var User $newUser */
            if (strpos($_REQUEST['username'], '@') !== false) {
                $newUser = User::findOne(['auth' => 'email:' . $_REQUEST['username']]);
            } else {
                $newUser = User::findOne(['auth' => User::wurzelwerkId2Auth($_REQUEST["username"])]);
            }
            if ($newUser) {
                $this->site->link('admins', $newUser);
                $str = '%username% hat nun auch Admin-Rechte.';
                \Yii::$app->session->setFlash('success', str_replace('%username%', $_REQUEST['username'], $str));
            } else {
                $str = 'BenutzerIn %username% nicht gefunden. Der/Diejenige muss sich zuvor mindestens ' .
                    'einmal auf Antragsgrün eingeloggt haben, um als Admin hinzugefügt werden zu können.';
                \Yii::$app->session->setFlash('error', str_replace('%username%', $_REQUEST['username'], $str));
            }
        }
        if (AntiXSS::isTokenSet('removeuser')) {
            /** @var User $todel */
            $todel = User::findOne(AntiXSS::getTokenVal('removeuser'));
            $this->site->unlink('admins', $todel, true);
            \Yii::$app->session->setFlash('success', 'Die Admin-Rechte wurden entzogen.');
        }

        $delform        = AntiXSS::createToken('removeuser');
        $delUrlTemplate = UrlHelper::createUrl(['admin/index/admins', $delform => 'REMOVEID']);
        return $this->render(
            'admins',
            [
                'site'   => $this->site,
                'myself' => $myself,
                'delUrl' => $delUrlTemplate,
            ]
        );
    }


    public function actionConsultation()
    {
        $model = $this->consultation;

        $locale = Tools::getCurrentDateLocale();

        if (isset($_POST['save'])) {
            $data = $_POST['consultation'];
            $model->setAttributes($data);
            $model->deadlineMotions = Tools::dateBootstraptime2sql($data['deadlineMotions'], $locale);
            $model->deadlineAmendments = Tools::dateBootstraptime2sql($data['deadlineAmendments'], $locale);

            $settingsInput = (isset($_POST['settings']) ? $_POST['settings'] : []);
            $settings = $model->getSettings();
            $settings->saveForm($settingsInput, $_POST['settingsFields']);
            $model->setSettings($settings);

            /*

            if (isset($_REQUEST["VeranstaltungsEinstellungen"]["ae_nummerierung"])) {
                switch ($_REQUEST["VeranstaltungsEinstellungen"]["ae_nummerierung"]) {
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
            }
            $model->setEinstellungen($einstellungen);
            */

            if ($model->save()) {
                $model->flushCaches();
                \yii::$app->session->setFlash('success', 'Gespeichert.');
            } else {
                \yii::$app->session->setFlash('error', print_r($model->getErrors(), true));
            }
        }

        return $this->render('consultation_settings', ['consultation' => $this->consultation, 'locale' => $locale]);
    }

    public function actionConsultationexperts()
    {
        if (AntiXSS::isTokenSet("del_tag")) {
            foreach ($model->tags as $tag) {
                if ($tag->id == AntiXSS::getTokenVal("del_tag")) {
                    $tag->delete();
                    $model->refresh();
                }
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
                        if ($tag->position > $max_id) {
                            $max_id = $tag->position;
                        }
                        if (mb_strtolower($tag->name) == mb_strtolower($_REQUEST["tag_neu"])) {
                            $duplicate = true;
                        }
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
