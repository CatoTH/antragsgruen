<?php

class UnterstuetzerInnenWidget
{


    /**
     * @static
     * @param $antrag Antrag|Aenderungsantrag
     * @param $unterstuetzerIn_rel string
     * @return string
     */
    public static function printUnterstuetzerInnenWidget($antrag, $unterstuetzerIn_rel)
    {
        $neustr = '<div class="unterstuetzerInnenwidget_adder">';
        $neustr .= '<select name="' . get_class($antrag) . '[unterstuetzerIn_neu][person][]" class="person_selector">';
        $neustr .= '<option value="neu"> - neue Person alegen -</option>';

        $pers = Person::model()->findAllAttributes("name", true, array("order" => "name"));
        foreach ($pers as $p) {
            /* @var $p Person */
            $neustr .= '<option value="' . $p->id . '">' . CHtml::encode($p->name) . '</option>';
        }
        $neustr .= "</select>";

        $neustr .= '<select name="' . get_class($antrag) . '[unterstuetzerIn_neu][rolle][]" required>';
        $neustr .= '<option value=""> - </option>';
        foreach (IUnterstuetzerInnen::$ROLLEN as $key => $val) {
            $neustr .= '<option value="' . $key . '">' . CHtml::encode($val) . "</option>\n";
        }
        $neustr .= '</select><div class="unterstuetzerIn_neu_holder">';

		$neustr .= "<select name='" . get_class($antrag) . "[unterstuetzerIn_neu][person_typ][]'>";
        foreach (Person::$TYPEN as $key => $val) {
            $neustr .= "<option value='$key'>" . GxHtml::encode($val) . "</option>\n";
        }
		$neustr .= "</select>";

        $neustr .= "<br>Name: <input name='" . get_class($antrag) . "[unterstuetzerIn_neu][person_name][]' value=''>";

        $neustr .= '</div></div>';


        $str = '<div style="display: inline-block; width: 500px;" class="unterstuetzerInnenwidget" data-neutemplate="' . CHtml::encode($neustr) . '">';
        $unterstuetzerInnen = $antrag->$unterstuetzerIn_rel;
        foreach ($unterstuetzerInnen as $unt) {
            $str .= '<div>';
            $str .= '<span style="display: inline-block; width: 250px; overflow: hidden;" class="sort_handle">' . CHtml::encode($unt->person->name) . '</span>';
            $str .= '<input type="hidden" name="' . get_class($antrag) . '[unterstuetzerInnen][person_id][]" value="' . $unt->person->id . '">';
            $str .= '<select name="' . get_class($antrag) . '[unterstuetzerInnen][rolle][]">';
            $str .= '<option value="del"> - ' . Yii::t('app', 'löschen') . ' - </option>';
            foreach (IUnterstuetzerInnen::$ROLLEN as $key => $val) {
                $str .= '<option value="' . $key . '" ';
                if ($unt->rolle == $key) $str .= "selected='selected'";
                $str .= ">" . CHtml::encode($val) . "</option>\n";
            }
            $str .= '</select>';
            $str .= '</div>';
        }

        $str .= '<div class="unterstuetzerInnenwidget_add_caller"><a href="#">Neue hinzufügen</a></div>';

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
                $unt->save();
            }
        }
        if (isset($_REQUEST[get_class($model)]["unterstuetzerIn_neu"])) {
            $unterstuetzerIn_neu = $_REQUEST[get_class($model)]["unterstuetzerIn_neu"];

            for ($i = 0; $i < count($unterstuetzerIn_neu["person"]); $i++) if ($unterstuetzerIn_neu["rolle"][$i] != "") {
                if (is_numeric($unterstuetzerIn_neu["person"][$i])) {
                    $unt = new $unterstuetzerInnen_class;
                    $unt->$unterstuetzerIn_pk = $id;
                    $unt->unterstuetzerIn_id = IntVal($unterstuetzerIn_neu["person"][$i]);
                    $unt->rolle = $unterstuetzerIn_neu["rolle"][$i];
                    try {
                        $unt->save();
                    } catch (CDbException $e) {
                        $messages[] = $e->getMessage();
                    }
                } elseif ($unterstuetzerIn_neu["person"][$i] == "neu") {
                    $person = new Person;
                    $person->name = $unterstuetzerIn_neu["person_name"][$i];
                    $person->typ = $unterstuetzerIn_neu["person_typ"][$i];
                    $person->status = Person::$STATUS_UNCONFIRMED;
                    $person->angelegt_datum = "NOW()";
                    $person->admin = 0;

                    if ($person->save()) {
                        $unt = new $unterstuetzerInnen_class();
                        $unt->$unterstuetzerIn_pk = $id;
                        $unt->unterstuetzerIn_id = $person->id;
                        $unt->rolle = $unterstuetzerIn_neu["rolle"][$i];
						$unt->position = $i;
                        $unt->save();
                    }
                }
            }
        }

    }


}
