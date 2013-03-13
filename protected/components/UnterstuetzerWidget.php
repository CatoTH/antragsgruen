<?php

class UnterstuetzerWidget
{


    /**
     * @static
     * @param $antrag Antrag|Aenderungsantrag
     * @param $unterstuetzer_rel string
     * @return string
     */
    public static function printUnterstuetzerWidget($antrag, $unterstuetzer_rel)
    {
        /*
        $neustr = '<div>';
        $neustr .= '<span style="display: inline-block; width: 250px; overflow: hidden;">%name%</span>';
        $neustr .= '<select name="' . get_class($antrag) . '[unterstuetzer][rolle][]">';
        foreach (IUnterstuetzer::$ROLLEN as $key=>$val) {
            $neustr .= '<option value="' . $key . '">' . CHtml::encode($val) . '</option>\n';
        }
        $neustr .= '</select>';
        $neustr .= '</div>';
        */

        $neustr = '<div class="unterstuetzerwidget_adder">';
        $neustr .= '<select name="' . get_class($antrag) . '[unterstuetzer_neu][person][]" class="person_selector">';
        $neustr .= '<option value="neu"> - neue Person alegen -</option>';

        $pers = Person::model()->findAllAttributes("name", true, array("order" => "name"));
        foreach ($pers as $p) {
            /* @var $p Person */
            $neustr .= '<option value="' . $p->id . '">' . CHtml::encode($p->name) . '</option>';
        }
        $neustr .= "</select>";

        $neustr .= '<select name="' . get_class($antrag) . '[unterstuetzer_neu][rolle][]">';
        $neustr .= '<option value=""> - </option>';
        foreach (IUnterstuetzer::$ROLLEN as $key => $val) {
            $neustr .= '<option value="' . $key . '">' . CHtml::encode($val) . "</option>\n";
        }
        $neustr .= '</select><div class="unterstuetzer_neu_holder">';

		$neustr .= "<select name='" . get_class($antrag) . "[unterstuetzer_neu][person_typ][]'>";
        foreach (Person::$TYPEN as $key => $val) {
            $neustr .= "<option value='$key'>" . GxHtml::encode($val) . "</option>\n";
        }
		$neustr .= "</select>";

        $neustr .= "<br>Name: <input name='" . get_class($antrag) . "[unterstuetzer_neu][person_name][]' value=''>";

        $neustr .= '</div></div>';


        $str = '<div style="display: inline-block; width: 500px;" class="unterstuetzerwidget" data-neutemplate="' . CHtml::encode($neustr) . '">';
        $unterstuetzer = $antrag->$unterstuetzer_rel;
        foreach ($unterstuetzer as $unt) {
            $str .= '<div>';
            $str .= '<span style="display: inline-block; width: 250px; overflow: hidden;" class="sort_handle">' . CHtml::encode($unt->unterstuetzer->name) . '</span>';
            $str .= '<input type="hidden" name="' . get_class($antrag) . '[unterstuetzer][person_id][]" value="' . $unt->unterstuetzer->id . '">';
            $str .= '<select name="' . get_class($antrag) . '[unterstuetzer][rolle][]">';
            $str .= '<option value="del"> - ' . Yii::t('app', 'löschen') . ' - </option>';
            foreach (IUnterstuetzer::$ROLLEN as $key => $val) {
                $str .= '<option value="' . $key . '" ';
                if ($unt->rolle == $key) $str .= "selected='selected'";
                $str .= ">" . CHtml::encode($val) . "</option>\n";
            }
            $str .= '</select>';
            $str .= '</div>';
        }

        $str .= '<div class="unterstuetzerwidget_add_caller"><a href="#">Neue hinzufügen</a></div>';

        $str .= "</div>";
        return $str;
    }

    /**
     * @param Antrag|Aenderungsantrag $model
     * @param $messages array
     * @param AntragUnterstuetzer|AenderungsantragUnterstuetzer $unterstuetzer_class
     * @param $unterstuetzer_pk int
     * @param int $id
     */
    public static function saveUnterstuetzerWidget(&$model, &$messages, $unterstuetzer_class, $unterstuetzer_pk, $id) {
        $unterstuetzer_class::model()->deleteAllByAttributes(array($unterstuetzer_pk => $id));

        if (isset($_REQUEST[get_class($model)]["unterstuetzer"])) {
            $unterstuetzer = $_REQUEST[get_class($model)]["unterstuetzer"];
            for ($i = 0; $i < count($unterstuetzer["person_id"]); $i++) if ($unterstuetzer["rolle"][$i] != "del") {
				/** @var AntragUnterstuetzer $unt */
                $unt = new $unterstuetzer_class;
                $unt->$unterstuetzer_pk = $id;
                $unt->unterstuetzer_id = IntVal($unterstuetzer["person_id"][$i]);
                $unt->rolle = $unterstuetzer["rolle"][$i];
				$unt->position = $i;
                $unt->save();
            }
        }
        if (isset($_REQUEST[get_class($model)]["unterstuetzer_neu"])) {
            $unterstuetzer_neu = $_REQUEST[get_class($model)]["unterstuetzer_neu"];

            for ($i = 0; $i < count($unterstuetzer_neu["person"]); $i++) if ($unterstuetzer_neu["rolle"][$i] != "") {
                if (is_numeric($unterstuetzer_neu["person"][$i])) {
                    $unt = new $unterstuetzer_class;
                    $unt->$unterstuetzer_pk = $id;
                    $unt->unterstuetzer_id = IntVal($unterstuetzer_neu["person"][$i]);
                    $unt->rolle = $unterstuetzer_neu["rolle"][$i];
                    try {
                        $unt->save();
                    } catch (CDbException $e) {
                        $messages[] = $e->getMessage();
                    }
                } elseif ($unterstuetzer_neu["person"][$i] == "neu") {
                    $person = new Person;
                    $person->name = $unterstuetzer_neu["person_name"][$i];
                    $person->typ = $unterstuetzer_neu["person_typ"][$i];
                    $person->status = Person::$STATUS_UNCONFIRMED;
                    $person->angelegt_datum = "NOW()";
                    $person->admin = 0;

                    if ($person->save()) {
                        $unt = new $unterstuetzer_class();
                        $unt->$unterstuetzer_pk = $id;
                        $unt->unterstuetzer_id = $person->id;
                        $unt->rolle = $unterstuetzer_neu["rolle"][$i];
						$unt->position = $i;
                        $unt->save();
                    }
                }
            }
        }

    }


}
