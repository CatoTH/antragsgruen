<?php

class UnterstuetzerInnenAdminWidget
{


    /**
     * @static
     * @param $antrag Antrag|Aenderungsantrag
     * @param $unterstuetzerIn_rel string
     * @return string
     */
    public static function printUnterstuetzerInnenWidget($antrag, $unterstuetzerIn_rel)
    {
        $neustr = '<div class="unterstuetzerInnenwidget_adder" style="margin-top: 20px;">';

        /*
        $neustr .= '<select name="' . get_class($antrag) . '[unterstuetzerIn_neu][person][]" class="person_selector">';
        $neustr .= '<option value="neu"> - neue Person anlegen -</option>';

        if ($antrag->getVeranstaltungsreihe()->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
            $pers = Person::model()->findAllByAttributes(array("veranstaltungsreihe_namespace" => $antrag->getVeranstaltungsreihe()->id), array("order" => "name"));
        } else {
            $pers = Person::model()->findAllAttributes("name", true, array("order" => "name"));
        }
        foreach ($pers as $p) {
            /* @var $p Person /
            $neustr .= '<option value="' . $p->id . '">' . CHtml::encode($p->name) . '</option>';
        }
        $neustr .= "</select>";
             * */
        $neustr .= '<input type="hidden" name="' . get_class($antrag) . '[unterstuetzerIn_neu][person][]" value="neu">';

        $neustr .= '<label style="display: inline-block; width: 220px;">';
        $neustr .= 'Rolle:<br>';
        $neustr .= '<select name="' . get_class($antrag) . '[unterstuetzerIn_neu][rolle][]" required>';
        $neustr .= '<option value=""> - </option>';
        foreach (IUnterstuetzerInnen::$ROLLEN as $key => $val) {
            $neustr .= '<option value="' . $key . '">' . CHtml::encode($val) . "</option>\n";
        }
        $neustr .= '</select></label>';

        $neustr .= '<label style="display: inline-block; width: 220px;" class="unterstuetzerIn_neu_holder">Personen-Typ:<br>';

		$neustr .= "<select name='" . get_class($antrag) . "[unterstuetzerIn_neu][person_typ][]'>";
        foreach (Person::$TYPEN as $key => $val) {
            $neustr .= "<option value='$key'>" . GxHtml::encode($val) . "</option>\n";
        }
		$neustr .= "</select></label>";

        $neustr .= "<label style='display: inline-block; width: 220px;'>Name:<br>";
        $neustr .= "<input type='text' style='width: 187px;' name='" . get_class($antrag) . "[unterstuetzerIn_neu][person_name][]' value='' placeholder='Name'>";
        $neustr .= '</label>';

        $neustr .= "<label style='display: inline-block; width: 220px;'>Organisation (bei nat. P.):<br>";
        $neustr .= "<input type='text' style='width: 187px;' name='" . get_class($antrag) . "[unterstuetzerIn_neu][person_organisation][]' value='' placeholder='KV, LAG, ...'>";
        $neustr .= '</label>';

        $neustr .= "<label style='display: inline-block; width: 220px;'>Beschlussdatum (bei jur. P.):<br>";
        $neustr .= "<input type='text' style='width: 187px;' name='" . get_class($antrag) . "[unterstuetzerIn_neu][beschlussdatum][]' value='' placeholder='TT.MM.YYYY'>";
        $neustr .= '</label>';

        $neustr .= '</div>';


        $str = '<div class="unterstuetzerInnenwidget" data-neutemplate="' . CHtml::encode($neustr) . '">';
        $unterstuetzerInnen = $antrag->$unterstuetzerIn_rel;
        foreach ($unterstuetzerInnen as $unt) {
			/** @var AntragUnterstuetzerInnen $unt */
            $str .= '<div style="vertical-align: top;">';
            $str .= '<span style="display: inline-block; width: 250px; overflow: hidden; vertical-align: top;" class="sort_handle">' . $unt->getNameMitBeschlussdatum(true) . '</span>';
            $str .= '<input type="hidden" name="' . get_class($antrag) . '[unterstuetzerInnen][person_id][]" value="' . $unt->person->id . '">';
			$str .= '<input type="hidden" name="' . get_class($antrag) . '[unterstuetzerInnen][beschlussdatum][]" value="' . CHtml::encode($unt->beschlussdatum) . '">';
            $str .= '<select name="' . get_class($antrag) . '[unterstuetzerInnen][rolle][]" style="vertical-align: top;">';
            $str .= '<option value="del"> - ' . Yii::t('app', 'löschen') . ' - </option>';
            foreach (IUnterstuetzerInnen::$ROLLEN as $key => $val) {
                $str .= '<option value="' . $key . '" ';
                if ($unt->rolle == $key) $str .= "selected='selected'";
                $str .= ">" . CHtml::encode($val) . "</option>\n";
            }
            $str .= '</select>';
            $str .= '</div>';
        }

        $str .= '<div class="unterstuetzerInnenwidget_add_caller" style="margin-top: 20px;"><a href="#">Neue hinzufügen</a></div>';

        $str .= "</div>";
        return $str;
    }

    /**
     * @param Antrag|Aenderungsantrag $model
     * @param $messages array
     * @param AntragUnterstuetzerInnen|AenderungsantragUnterstuetzerInnen $unterstuetzerInnen_class
     * @param $unterstuetzerIn_pk int
     * @param int $id
     */
    public static function saveUnterstuetzerInnenWidget(&$model, &$messages, $unterstuetzerInnen_class, $unterstuetzerIn_pk, $id) {
        $unterstuetzerInnen_class::model()->deleteAllByAttributes(array($unterstuetzerIn_pk => $id));

        if (isset($_REQUEST[get_class($model)]["unterstuetzerInnen"])) {
            $unterstuetzerIn = $_REQUEST[get_class($model)]["unterstuetzerInnen"];
            for ($i = 0; $i < count($unterstuetzerIn["person_id"]); $i++) if ($unterstuetzerIn["rolle"][$i] != "del") {
				/** @var AntragUnterstuetzerInnen $unt */
                $unt = new $unterstuetzerInnen_class;
                $unt->$unterstuetzerIn_pk = $id;
                $unt->unterstuetzerIn_id = IntVal($unterstuetzerIn["person_id"][$i]);
                $unt->rolle = $unterstuetzerIn["rolle"][$i];
				$unt->position = $i;
				$unt->beschlussdatum = $unterstuetzerIn["beschlussdatum"][$i];
                $unt->save();
            }
        }
        if (isset($_REQUEST[get_class($model)]["unterstuetzerIn_neu"])) {
            $unterstuetzerIn_neu = $_REQUEST[get_class($model)]["unterstuetzerIn_neu"];

            for ($i = 0; $i < count($unterstuetzerIn_neu["person"]); $i++) if ($unterstuetzerIn_neu["rolle"][$i] != "") {
                if (is_numeric($unterstuetzerIn_neu["person"][$i])) {
                    /*
                     * Kommt nicht mehr vor
                    $unt = new $unterstuetzerInnen_class;
                    $unt->$unterstuetzerIn_pk = $id;
                    $unt->unterstuetzerIn_id = IntVal($unterstuetzerIn_neu["person"][$i]);
                    $unt->rolle = $unterstuetzerIn_neu["rolle"][$i];
                    try {
                        $unt->save();
                    } catch (CDbException $e) {
                        $messages[] = $e->getMessage();
                    }
                    */
                } elseif ($unterstuetzerIn_neu["person"][$i] == "neu") {
                    $person = new Person;
                    $person->name = $unterstuetzerIn_neu["person_name"][$i];
                    $person->typ = $unterstuetzerIn_neu["person_typ"][$i];
                    $person->organisation = $unterstuetzerIn_neu["person_organisation"][$i];
                    $person->status = Person::$STATUS_UNCONFIRMED;
                    $person->angelegt_datum = "NOW()";

                    if ($person->save()) {
                        $unt = new $unterstuetzerInnen_class();
                        $unt->$unterstuetzerIn_pk = $id;
                        $unt->unterstuetzerIn_id = $person->id;
                        if (preg_match("/^(?<tag>[0-9]{2})\. *(?<monat>[0-9]{2})\. *(?<jahr>[0-9]{4})$/", $unterstuetzerIn_neu["beschlussdatum"][$i], $matches)) {
                            $unt->beschlussdatum = $matches["jahr"] . "-" . $matches["monat"] . "-" . $matches["tag"];
                        }
                        $unt->rolle = $unterstuetzerIn_neu["rolle"][$i];
						$unt->position = $i;
                        $unt->save();
                    }
                }
            }
        }

    }


}
