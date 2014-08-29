<?php

class AntragController extends AntragsgruenController
{

    /**
     * @param Antrag $antrag
     * @param int $kommentar_id
     */
    private function performAnzeigeActions($antrag, $kommentar_id)
    {
        if (AntiXSS::isTokenSet("komm_del")) {
            /** @var AntragKommentar $komm */
            $komm = AntragKommentar::model()->findByPk(AntiXSS::getTokenVal("komm_del"));
            if ($komm->antrag_id == $antrag->id && $komm->kannLoeschen(Yii::app()->user) && $komm->status == IKommentar::$STATUS_FREI) {
                $komm->status = IKommentar::$STATUS_GELOESCHT;
                $komm->save();
                Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");
            } else {
                Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
            }
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }

        if (AntiXSS::isTokenSet("komm_freischalten") && $kommentar_id > 0) {
            /** @var AntragKommentar $komm */
            $komm = AntragKommentar::model()->findByPk($kommentar_id);
            if ($komm->antrag_id == $antrag->id && $komm->status == IKommentar::$STATUS_NICHT_FREI && $antrag->veranstaltung->isAdminCurUser()) {
                $komm->status = IKommentar::$STATUS_FREI;
                $komm->save();
                Yii::app()->user->setFlash("success", "Der Kommentar wurde freigeschaltet.");

                $benachrichtigt = array();
                foreach ($antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) if ($abo->kommentare && !in_array($abo->person_id, $benachrichtigt)) {
                    $abo->person->benachrichtigenKommentar($komm);
                    $benachrichtigt[] = $abo->person_id;
                }
            } else {
                Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
            }
        }

        if (AntiXSS::isTokenSet("komm_nicht_freischalten") && $kommentar_id > 0) {
            /** @var AntragKommentar $komm */
            $komm = AntragKommentar::model()->findByPk($kommentar_id);
            if ($komm->antrag_id == $antrag->id && $komm->status == IKommentar::$STATUS_NICHT_FREI && $antrag->veranstaltung->isAdminCurUser()) {
                $komm->status = IKommentar::$STATUS_GELOESCHT;
                $komm->save();
                Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");
            } else {
                Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
            }
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }

        if (AntiXSS::isTokenSet("komm_dafuer") && $this->veranstaltung->getEinstellungen()->kommentare_unterstuetzbar) {
            $meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($kommentar_id);
            if ($meine_unterstuetzung === null) {
                $unterstuetzung = new AntragKommentarUnterstuetzerInnen();
                $unterstuetzung->setIdentityParams();
                $unterstuetzung->dafuer              = 1;
                $unterstuetzung->antrag_kommentar_id = $kommentar_id;

                if ($unterstuetzung->save()) Yii::app()->user->setFlash("success", "Du hast den Kommentar positiv bewertet.");
                else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
                $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
            }
        }
        if (AntiXSS::isTokenSet("komm_dagegen") && $this->veranstaltung->getEinstellungen()->kommentare_unterstuetzbar) {
            $meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($kommentar_id);
            if ($meine_unterstuetzung === null) {
                $unterstuetzung = new AntragKommentarUnterstuetzerInnen();
                $unterstuetzung->setIdentityParams();
                $unterstuetzung->dafuer              = 0;
                $unterstuetzung->antrag_kommentar_id = $kommentar_id;
                if ($unterstuetzung->save()) Yii::app()->user->setFlash("success", "Du hast den Kommentar negativ bewertet.");
                else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
                $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
            }
        }
        if (AntiXSS::isTokenSet("komm_dochnicht") && $this->veranstaltung->getEinstellungen()->kommentare_unterstuetzbar) {
            $meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($kommentar_id);
            if ($meine_unterstuetzung !== null) {
                $meine_unterstuetzung->delete();
                Yii::app()->user->setFlash("success", "Du hast die Bewertung des Kommentars zurückgenommen.");
                $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
            }
        }


        if (AntiXSS::isTokenSet("mag") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($antrag->antragUnterstuetzerInnen as $unt) if ($unt->unterstuetzerIn_id == $userid) $unt->delete();
            $unt                     = new AntragUnterstuetzerInnen();
            $unt->antrag_id          = $antrag->id;
            $unt->unterstuetzerIn_id = $userid;
            $unt->rolle              = "mag";
            $unt->kommentar          = "";
            if ($unt->save()) Yii::app()->user->setFlash("success", "Du unterstützt diesen Antrag nun.");
            else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }

        if (AntiXSS::isTokenSet("magnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($antrag->antragUnterstuetzerInnen as $unt) if ($unt->unterstuetzerIn_id == $userid) $unt->delete();
            $unt                     = new AntragUnterstuetzerInnen();
            $unt->antrag_id          = $antrag->id;
            $unt->unterstuetzerIn_id = $userid;
            $unt->rolle              = "magnicht";
            $unt->kommentar          = "";
            $unt->save();
            if ($unt->save()) Yii::app()->user->setFlash("success", "Du lehnst diesen Antrag nun ab.");
            else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }

        if (AntiXSS::isTokenSet("dochnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($antrag->antragUnterstuetzerInnen as $unt) if ($unt->unterstuetzerIn_id == $userid) $unt->delete();
            Yii::app()->user->setFlash("success", "Du stehst diesem Antrag wieder neutral gegenüber.");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }
    }


    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $kommentar_id
     */
    public function actionAnzeige($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $kommentar_id = 0)
    {
        $antrag_id = IntVal($antrag_id);
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->with("antragKommentare", "antragKommentare.unterstuetzerInnen")->findByPk($antrag_id);
        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        $this->layout = '//layouts/column2';

        $this->performAnzeigeActions($antrag, $kommentar_id);


        $kommentare_offen = array();

        if (AntiXSS::isTokenSet("kommentar_schreiben") && $antrag->veranstaltung->darfEroeffnenKommentar()) {
            $zeile = IntVal($_REQUEST["absatz_nr"]);

            $person        = $_REQUEST["Person"];
            $person["typ"] = Person::$TYP_PERSON;

            if ($antrag->veranstaltung->getEinstellungen()->kommentar_neu_braucht_email && trim($person["email"]) == "") {
                Yii::app()->user->setFlash("error", "Bitte gib deine E-Mail-Adresse an.");
                $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
            }
            $model_person = static::getCurrenPersonOrCreateBySubmitData($person, Person::$STATUS_UNCONFIRMED, false);

            $kommentar                 = new AntragKommentar();
            $kommentar->attributes     = $_REQUEST["AntragKommentar"];
            $kommentar->absatz         = $zeile;
            $kommentar->datum          = new CDbExpression('NOW()');
            $kommentar->verfasserIn    = $model_person;
            $kommentar->verfasserIn_id = $model_person->id;
            $kommentar->antrag         = $antrag;
            $kommentar->antrag_id      = $antrag_id;
            $kommentar->status         = ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare ? IKommentar::$STATUS_NICHT_FREI : IKommentar::$STATUS_FREI);

            $kommentare_offen[] = $zeile;

            if ($kommentar->save()) {
                $add = ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare ? " Er wird nach einer kurzen Prüfung freigeschaltet und damit sichtbar." : "");
                Yii::app()->user->setFlash("success", "Der Kommentar wurde gespeichert." . $add);

                if ($this->veranstaltung->admin_email != "" && $kommentar->status == IKommentar::$STATUS_NICHT_FREI) {
                    $kommentar_link = $kommentar->getLink(true);
                    $mails          = explode(",", $this->veranstaltung->admin_email);
                    $mail_text      = "Es wurde ein neuer Kommentar zum Antrag \"" . $antrag->name . "\" verfasst (nur eingeloggt sichtbar):\n" .
                        "Link: " . $kommentar_link;

                    foreach ($mails as $mail) if (trim($mail) != "") {
                        AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN, trim($mail), null, "Neuer Kommentar - bitte freischalten.", $mail_text);
                    }
                }

                if ($kommentar->status == IKommentar::$STATUS_FREI) {
                    $benachrichtigt = array();
                    foreach ($antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) if ($abo->kommentare && !in_array($abo->person_id, $benachrichtigt)) {
                        $abo->person->benachrichtigenKommentar($kommentar);
                        $benachrichtigt[] = $abo->person_id;
                    }
                }

                $this->redirect($kommentar->getLink());
            } else {
                foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
            }
        }
        if ($kommentar_id > 0) {
            $abs = $antrag->getParagraphs();
            foreach ($abs as $ab) {
                /** @var AntragAbsatz $ab */
                foreach ($ab->kommentare as $komm) if ($komm->id == $kommentar_id) $kommentare_offen[] = $ab->absatz_nr;
            }
        }

        $aenderungsantraege = array();
        foreach ($antrag->aenderungsantraege as $antr) if (!in_array($antr->status, IAntrag::$STATI_UNSICHTBAR)) $aenderungsantraege[] = $antr;

        $hiddens       = array();
        $js_protection = Yii::app()->user->isGuest;
        if ($js_protection) {
            $hiddens["form_token"] = AntiXSS::createToken("kommentar_schreiben");
        } else {
            $hiddens[AntiXSS::createToken("kommentar_schreiben")] = "1";
        }

        if (Yii::app()->user->isGuest) $kommentar_person = new Person();
        else $kommentar_person = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
        $kommentar_person->setEmailRequired($antrag->veranstaltung->getEinstellungen()->kommentar_neu_braucht_email);

        $support_status = "";
        if (!Yii::app()->user->isGuest) {
            foreach ($antrag->antragUnterstuetzerInnen as $unt) if ($unt->person->id == Yii::app()->user->getState("person_id")) $support_status = $unt->rolle;
        }

        $this->render("anzeige", array(
            "antrag"             => $antrag,
            "aenderungsantraege" => $aenderungsantraege,
            "edit_link"          => $antrag->kannUeberarbeiten(),
            "kommentare_offen"   => $kommentare_offen,
            "kommentar_person"   => $kommentar_person,
            "admin_edit"         => (Yii::app()->user->getState("role") == "admin" ? "/admin/antraege/update/id/" . $antrag_id : null),
            "komm_del_link"      => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id, AntiXSS::createToken("komm_del") => "#komm_id#")),
            "hiddens"            => $hiddens,
            "js_protection"      => $js_protection,
            "support_status"     => $support_status,
            "sprache"            => $antrag->veranstaltung->getSprache(),
        ));
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     */
    public function actionPdf($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id)
    {
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);
        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        $this->renderPartial("pdf", array(
            'model'   => $antrag,
            "sprache" => $antrag->veranstaltung->getSprache(),
        ));
    }


    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     */
    public function actionOdt($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id)
    {
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);
        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        $this->renderPartial("odt", array(
            'model'   => $antrag,
            "sprache" => $antrag->veranstaltung->getSprache(),
        ));
    }


    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     */
    public function actionPlainHtml($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id)
    {
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);
        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        /** @var $antragstellerInnen array|Person[] $antragstellerInnen */
        $antragstellerInnen = array();
        $unterstuetzerInnen = array();
        $zustimmung_von     = array();
        $ablehnung_von      = array();
        if (count($antrag->antragUnterstuetzerInnen) > 0) foreach ($antrag->antragUnterstuetzerInnen as $relatedModel) {
            if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $antragstellerInnen[] = $relatedModel->person;
            if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $unterstuetzerInnen[] = $relatedModel->person;
            if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_MAG) $zustimmung_von[] = $relatedModel->person;
            if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_MAG_NICHT) $ablehnung_von[] = $relatedModel->person;
        }

        $this->renderPartial("plain_html", array(
            'antrag'             => $antrag,
            "sprache"            => $antrag->veranstaltung->getSprache(),
            "antragstellerInnen" => $antragstellerInnen,
            "unterstuetzerInnen" => $unterstuetzerInnen,
        ));
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     */
    public function actionAes_Einpflegen($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id)
    {
        $this->layout = '//layouts/column2';

        $antrag_id = IntVal($antrag_id);

        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);
        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        if (!$antrag->kannUeberarbeiten()) {
            Yii::app()->user->setFlash("error", "Kein Zugriff auf den Antrag");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
        }

        if (AntiXSS::isTokenSet("ueberarbeiten")) {
            $neuer                   = new Antrag();
            $neuer->veranstaltung_id = $antrag->veranstaltung_id;
            $neuer->abgeleitet_von   = $antrag->id;
            $neuer->typ              = $antrag->typ;
            switch ($_REQUEST["titel_typ"]) {
                case "original":
                    $neuer->name = $antrag->name;
                    break;
                case "neu":
                    $neuer->name = $_REQUEST["titel_neu"];
                    break;
                default:
                    $ae = $antrag->getAenderungsAntragById($_REQUEST["titel_typ"]);
                    if (!$ae) die("ÄA nicht gefunden: " . $_REQUEST["titel_typ"]);
                    $neuer->name = $ae->name_neu;
            }

            $absatz_mapping = array();
            $neue_absaetze  = array();
            $absae          = $antrag->getParagraphs(true, false);
            $neu_count      = 0;
            for ($i = 0; $i < count($absae); $i++) {
                $absatz_mapping[$i] = $neu_count;
                switch ($_REQUEST["absatz_typ"][$i]) {
                    case "original":
                        $neuer_text = $absae[$i]->str_bbcode;
                        break;
                    case "neu":
                        $neuer_text = HtmlBBcodeUtils::bbcode_normalize($_REQUEST["neu_text"][$i]);
                        break;
                    default:
                        $aes = $absae[$i]->aenderungsantraege;
                        foreach ($aes as $ae) if ($ae->id == $_REQUEST["absatz_typ"][$i]) {
                            $par        = $ae->getDiffParagraphs();
                            $neuer_text = $par[$i];
                        }
                        if (!isset($neuer_text)) die("ÄA nicht gefunden");
                }
                $neu = HtmlBBcodeUtils::bbcode2html_absaetze($neuer_text, false, $antrag->veranstaltung->getEinstellungen()->zeilenlaenge);
                foreach ($neu["bbcode"] as $line) $neue_absaetze[] = $line;
                $neu_count += count($neu["bbcode"]);
            }
            $neuer->text              = implode("\n\n", $neue_absaetze);
            $neuer->revision_name     = $_REQUEST["rev_neu"];
            $neuer->datum_einreichung = new CDbExpression('NOW()');
            switch ($_REQUEST["begruendung_typ"]) {
                case "original":
                    $neuer->begruendung = $antrag->begruendung;
                    break;
                case "neu":
                    $neuer->begruendung = $_REQUEST["begruendung_neu"];
                    break;
                default:
                    die("Ungültige Eingabe");
            }
            $neuer->status = ($antrag->status == IAntrag::$STATUS_EINGEREICHT_GEPRUEFT ? IAntrag::$STATUS_EINGEREICHT_GEPRUEFT : IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT);
            if ($neuer->save()) {
                foreach ($antrag->antragUnterstuetzerInnen as $init) if ($init->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
                    $in                     = new AntragUnterstuetzerInnen();
                    $in->rolle              = IUnterstuetzerInnen::$ROLLE_INITIATORIN;
                    $in->position           = $init->position;
                    $in->antrag_id          = $neuer->id;
                    $in->unterstuetzerIn_id = $init->unterstuetzerIn_id;
                    $in->kommentar          = "";
                    $in->save();
                }

                $antrag->status          = IAntrag::$STATUS_MODIFIZIERT;
                $antrag->datum_beschluss = new CDbExpression('NOW()');
                $antrag->save();

                foreach ($antrag->aenderungsantraege as $ae) if (!in_array($ae->status, IAntrag::$STATI_UNSICHTBAR)) {
                    switch ($_REQUEST["ae"][$ae->id]) {
                        case IAntrag::$STATUS_ANGENOMMEN:
                        case IAntrag::$STATUS_MODIFIZIERT_ANGENOMMEN:
                        case IAntrag::$STATUS_ABGELEHNT:
                            $ae->status = $_REQUEST["ae"][$ae->id];
                            $ae->save();
                            break;
                        case IAntrag::$STATUS_EINGEREICHT_GEPRUEFT:
                            $ae->aufrechterhaltenBeiNeuemAntrag($neuer, $neu_count, $absatz_mapping);
                            break;
                    }
                }

                $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $neuer->id)));
            } else {
                die("Ein Fehler ist aufgetreten");
            }
            die();
        }

        $aenderungsantraege = array();
        foreach ($antrag->aenderungsantraege as $antr) if (!in_array($antr->status, IAntrag::$STATI_UNSICHTBAR)) $aenderungsantraege[] = $antr;

        $this->render("aes_einpflegen", array(
            "antrag"             => $antrag,
            "aenderungsantraege" => $aenderungsantraege,
            "sprache"            => $antrag->veranstaltung->getSprache(),
        ));

    }


    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     */
    public function actionBearbeiten($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id)
    {
        $this->layout = '//layouts/column2';

        $antrag_id = IntVal($antrag_id);

        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);
        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        if (!$antrag->binInitiatorIn()) {
            Yii::app()->user->setFlash("error", "Kein Zugriff auf den Antrag");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
        }

        if (AntiXSS::isTokenSet("antrag_del")) {
            $antrag->status = Antrag::$STATUS_ZURUECKGEZOGEN;
            if ($antrag->save()) {
                Yii::app()->user->setFlash("success", "Der Antrag wurde zurückgezogen.");
                $this->redirect($this->createUrl("veranstaltung/index"));
            } else {
                Yii::app()->user->setFlash("error", "Der Antrag konnte nicht zurückgezogen werden.");
            }
        }

        $this->render("bearbeiten_start", array(
            "antrag"  => $antrag,
            "sprache" => $antrag->veranstaltung->getSprache(),
        ));
    }


    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     */
    public function actionAendern($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id)
    {
        $this->layout = '//layouts/column2';

        $antrag_id = IntVal($antrag_id);
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);
        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        if (!$antrag->kannUeberarbeiten()) {
            Yii::app()->user->setFlash("error", "Kein Zugriff auf den Antrag");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
        }

        if (AntiXSS::isTokenSet("antragbearbeiten")) {
            $antrag->attributes        = $_REQUEST["Antrag"];
            $antrag->text              = HtmlBBcodeUtils::bbcode_normalize($antrag->text);
            $antrag->begruendung       = HtmlBBcodeUtils::bbcode_normalize($antrag->begruendung);
            $antrag->datum_einreichung = new CDbExpression('NOW()');
            if (!in_array($antrag->status, array(IAntrag::$STATUS_UNBESTAETIGT, IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT))) $antrag->status = IAntrag::$STATUS_UNBESTAETIGT;

            $goon = true;

            $model_unterstuetzerInnen_int = array();
            /** @var array|AntragUnterstuetzerInnen[] $model_unterstuetzerInnen_obj */
            $model_unterstuetzerInnen_obj = array();
            if (isset($_REQUEST["UnterstuetzerInnenTyp"])) foreach ($_REQUEST["UnterstuetzerInnenTyp"] as $key => $typ) if ($typ != "" && $_REQUEST["UnterstuetzerInnenName"][$key] != "") {
                $name = trim($_REQUEST["UnterstuetzerInnenName"][$key]);
                // Man soll keinen bestätigten Nutzer eintragen können, das kann der dann selbvst machen
                $p = Person::model()->findByAttributes(array("typ" => $typ, "name" => $name, "status" => Person::$STATUS_UNCONFIRMED));
                if (!$p) {
                    $p                 = new Person();
                    $p->name           = $name;
                    $p->typ            = $typ;
                    $p->angelegt_datum = new CDbExpression('NOW()');
                    $p->admin          = 0;
                    $p->status         = Person::$STATUS_UNCONFIRMED;
                    $p->save();

                }
                $model_unterstuetzerInnen_int[] = $p;
                $model_unterstuetzerInnen[]     = array("typ" => $typ, "name" => $name);

                $init                           = new AntragUnterstuetzerInnen();
                $init->rolle                    = AntragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN;
                $init->unterstuetzerIn_id       = $p->id;
                $init->person                   = $p;
                $init->antrag_id                = $antrag->id;
                $model_unterstuetzerInnen_obj[] = $init;
            }

            if (!$antrag->veranstaltung->getPolicyAntraege()->checkAntragSubmit()) {
                Yii::app()->user->setFlash("error", "Nicht genügend UnterstützerInnen");
                $goon = false;
            }

            if ($goon && $antrag->save()) {

                foreach ($antrag->antragUnterstuetzerInnen as $unt)
                    if ($unt->rolle == AntragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN && $unt->person->status == Person::$STATUS_UNCONFIRMED) $unt->delete();
                foreach ($model_unterstuetzerInnen_obj as $unt) $unt->save();

                $this->redirect($this->createUrl("antrag/neuConfirm", array("antrag_id" => $antrag_id, "next_status" => $antrag->status, "from_mode" => "aendern")));
            } else {
                foreach ($antrag->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Antrag konnte nicht geändert werden: $key: " . $val2);
            }

        }

        $hiddens = array();

        $js_protection = Yii::app()->user->isGuest;
        if ($js_protection) {
            $hiddens["form_token"] = AntiXSS::createToken("antragbearbeiten");
        } else {
            $hiddens[AntiXSS::createToken("antragbearbeiten")] = "1";
        }

        $antragstellerIn          = null;
        $model_unterstuetzerInnen = array();

        foreach ($antrag->antragUnterstuetzerInnen as $unt) {
            if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $antragstellerIn = $unt->person;
            if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $model_unterstuetzerInnen[] = $unt->person;
        }

        $this->render('bearbeiten_form', array(
            "mode"                     => "bearbeiten",
            "model"                    => $antrag,
            "hiddens"                  => $hiddens,
            "antragstellerIn"          => $antragstellerIn,
            "model_unterstuetzerInnen" => $model_unterstuetzerInnen,
            "veranstaltung"            => $antrag->veranstaltung,
            "js_protection"            => $js_protection,
            //"login_warnung"            => Yii::app()->user->isGuest,
            "login_warnung"            => false,
            "sprache"                  => $antrag->veranstaltung->getSprache(),
        ));


    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     */
    public function actionNeuConfirm($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id)
    {
        $this->layout = '//layouts/column2';

        $antrag_id = IntVal($antrag_id);
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByAttributes(array("id" => $antrag_id, "status" => Antrag::$STATUS_UNBESTAETIGT));

        if (is_null($antrag)) {
            Yii::app()->user->setFlash("error", "Antrag nicht gefunden oder bereits bestätigt.");
            $this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);
        $this->testeWartungsmodus();

        if (AntiXSS::isTokenSet("antragbestaetigen")) {

            $freischaltung  = $antrag->veranstaltung->getEinstellungen()->freischaltung_antraege;
            $antrag->status = ($freischaltung ? Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT : Antrag::$STATUS_EINGEREICHT_GEPRUEFT);
            if (!$freischaltung && $antrag->revision_name == "") {
                $antrag->revision_name = $antrag->veranstaltung->naechsteAntragRevNr($antrag->typ);
            }
            $antrag->save();

            if ($antrag->veranstaltung->admin_email != "") {
                $mails     = explode(",", $antrag->veranstaltung->admin_email);
                $mail_text = "Es wurde ein neuer Antrag \"" . $antrag->name . "\" eingereicht.\n" .
                    "Link: " . yii::app()->getBaseUrl(true) . $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id));

                foreach ($mails as $mail) if (trim($mail) != "") {
                    AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN, trim($mail), null, "Neuer Antrag", $mail_text);
                }
            }

            if ($antrag->status == Antrag::$STATUS_EINGEREICHT_GEPRUEFT) {
                $benachrichtigt = array();
                foreach ($antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) if ($abo->antraege && !in_array($abo->person_id, $benachrichtigt)) {
                    $abo->person->benachrichtigenAntrag($antrag);
                    $benachrichtigt[] = $abo->person_id;
                }
            }

            $this->render("neu_submitted", array(
                "antrag"  => $antrag,
                "sprache" => $antrag->veranstaltung->getSprache(),
            ));

        } else {

            $model_unterstuetzerInnen = array();
            for ($i = 0; $i < 15; $i++) $model_unterstuetzerInnen[] = array("typ" => Person::$TYP_PERSON, "name" => "");

            $this->render('neu_confirm', array(
                "antrag"                   => $antrag,
                "model_unterstuetzerInnen" => $model_unterstuetzerInnen,
                "sprache"                  => $antrag->veranstaltung->getSprache(),
            ));

        }

    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     */
    public function actionNeu($veranstaltungsreihe_id = "", $veranstaltung_id)
    {
        $this->layout = '//layouts/column2';

        $model         = new Antrag();
        $model->status = Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT;
        $model->typ    = Antrag::$TYP_ANTRAG;

        /** @var Veranstaltung $veranstaltung */
        $this->veranstaltung     = $veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
        $model->veranstaltung_id = $veranstaltung->id;
        $model->veranstaltung    = $veranstaltung;
        $this->testeWartungsmodus();

        if (!$veranstaltung->getPolicyAntraege()->checkCurUserHeuristically()) {
            Yii::app()->user->setFlash("error", "Es kann kein Antrag angelegt werden.");
            $this->redirect($this->createUrl("veranstaltung/index"));
        }

        $model_unterstuetzerInnen = array();

        if (AntiXSS::isTokenSet("antragneu")) {
            $model->attributes        = $_REQUEST["Antrag"];
            $model->text              = HtmlBBcodeUtils::bbcode_normalize($model->text);
            $model->begruendung       = HtmlBBcodeUtils::bbcode_normalize($model->begruendung);
            $model->datum_einreichung = new CDbExpression('NOW()');
            $model->status            = Antrag::$STATUS_UNBESTAETIGT;
            $goon                     = true;

            if (!$this->veranstaltung->getPolicyAntraege()->checkAntragSubmit()) {
                Yii::app()->user->setFlash("error", "Keine Berechtigung zum Anlegen von Anträgen.");
                $goon = false;
            }

            if ($goon) {
                if ($model->save()) {
                    $this->veranstaltung->getPolicyAntraege()->submitAntragsstellerInView_Antrag($model);
                    /* $next_status = $_REQUEST["Antrag"]["status"] */
                    $next_status = Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT;
                    $this->redirect($this->createUrl("antrag/neuConfirm", array("antrag_id" => $model->id, "next_status" => $next_status, "from_mode" => "neu")));
                } else {
                    foreach ($model->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Antrag konnte nicht angelegt werden: $key: " . $val2);
                }
            }
        }

        $hiddens       = array();
        $js_protection = Yii::app()->user->isGuest;
        if ($js_protection) {
            $hiddens["form_token"] = AntiXSS::createToken("antragneu");
        } else {
            $hiddens[AntiXSS::createToken("antragneu")] = "1";
        }

        if (Yii::app()->user->isGuest) {
            $antragstellerIn      = new Person();
            $antragstellerIn->typ = Person::$TYP_PERSON;
        } else {
            $antragstellerIn = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
        }

        $this->render('bearbeiten_form', array(
            "mode"                     => "neu",
            "model"                    => $model,
            "antragstellerIn"          => $antragstellerIn,
            "model_unterstuetzerInnen" => $model_unterstuetzerInnen,
            "veranstaltung"            => $veranstaltung,
            "hiddens"                  => $hiddens,
            "js_protection"            => $js_protection,
            //"login_warnung"            => Yii::app()->user->isGuest,
            "login_warnung"            => false,
            "sprache"                  => $model->veranstaltung->getSprache(),
        ));
    }

} 