<?php

/**
 * @var AntragController $this
 * @var Antrag $model
 * @var string $mode
 * @var int $force_type
 * @var Person $antragstellerIn
 * @var Veranstaltung $veranstaltung
 * @var array $unterstuetzerInnen
 * @var array $hiddens
 * @var int[] $tags_pre
 * @var bool $js_protection
 * @var bool $login_warnung
 * @var Sprache $sprache
 */

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/ckeditor/ckeditor.js');
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/bbcode/plugin.js');
//$app->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/bbcode/plugin.js');

$this->breadcrumbs         = array(
    CHtml::encode($model->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index", array("veranstaltung_id" => $model->veranstaltung->url_verzeichnis)),
    $sprache->get($model->id > 0 ? 'Antrag bearbeiten' : 'Neuer Antrag'),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$antrag_max_len = $this->veranstaltung->getAntragMaxLen($force_type);

if ($veranstaltung->veranstaltungsreihe->subdomain == "wiesbaden" && $veranstaltung->url_verzeichnis == "phase2") {
    if ($model->typ == Antrag::$TYP_ANTRAG) {
        echo '<h1>Fließtext</h1>';
    } else {
        echo '<h1>Beispielprojekt</h1>';
    }
} else {
    echo '<h1>' . $sprache->get($model->id > 0 ? $sprache->get('Antrag bearbeiten') : $sprache->get('Antrag stellen')) . '</h1>';
}

?>


    <div class="form content">
        <?php
        if ($veranstaltung->policy_antraege != "Alle") {
            ?>
            <fieldset>
                <legend><?php echo $sprache->get("Voraussetzungen für einen Antrag") ?></legend>
            </fieldset>

            <?php
            echo $veranstaltung->getPolicyAntraege()->getOnCreateDescription();
        }

        /** @var TbActiveForm $form */
        $form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
            'id'   => 'antrag_stellen_form',
            'type' => 'horizontal',
        ));

        foreach ($hiddens as $name => $value) {
            echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
        }

        if ($login_warnung) {
            Yii::app()->user->setFlash('error', '<strong>Achtung!</strong> Es ist zwar auch möglich, Anträge einzureichen, ohne eingeloggt zu sein. Allerdings kann man nur eingeloggt den Antrag später wieder bearbeiten, daher empfehlen wir sehr, <a href="' . CHtml::encode($this->createUrl("veranstaltung/login")) . '" style="font-weight: bold;">dich einzuloggen</a>.');
            $this->widget('bootstrap.widgets.TbAlert', array(
                'block' => true,
                'fade'  => true,
            ));
        }

        $veinstellungen = $veranstaltung->getEinstellungen();
        if ($js_protection) {
            ?>
            <div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.</div>
        <?php }

        $typen = array();
        if ($force_type !== null) {
            $typen[$force_type] = Antrag::$TYPEN[$force_type];
        } else {
            foreach (Antrag::$TYPEN as $id => $name) {
                if (!in_array($id, $veinstellungen->antrags_typen_deaktiviert)) {
                    $typen[$id] = $name;
                }
            }
        }
        if (count($typen) == 1) {
            $keys = array_keys($typen);
            echo '<input type="hidden" name="Antrag[typ]" value="' . $keys[0] . '">';
        } else {
            ?>
            <fieldset>
                <label class="legend">Antragstyp</label>
                <?php
                foreach (Antrag::$TYPEN as $id => $name) {
                    if (!in_array($id, $veinstellungen->antrags_typen_deaktiviert)) {
                        if (strtolower($this->veranstaltungsreihe->subdomain) == "bdk-hh-2014" && $id == Antrag::$TYP_ANTRAG) {
                            $name = "Grüne Werte: Freiheit und Selbstbestimmung";
                        }
                        echo '<label class="radio" style="margin-right: 10px;"><input name="Antrag[typ]" value="' . $id . '" type="radio" ';
                        if ($model->typ == $id) {
                            echo ' checked';
                        }
                        echo ' required> ' . CHtml::encode($name) . '</label>';
                    }
                }
                ?>
            </fieldset>
        <?php }

        if (count($veranstaltung->tags) > 0) {
            ?>
            <fieldset>
                <?php
                if ($veranstaltung->getEinstellungen()->antrag_hat_mehrere_tags) {
                    echo '<label class="legend">Themengebiet(e)</label>';
                    foreach ($veranstaltung->tags as $tag) {
                        echo '<label class="radio" style="margin-right: 10px;"><input name="tags[]" value="' . $tag->id . '" type="checkbox" ';
                        if (in_array($tag->id, $tags_pre)) {
                            echo ' checked';
                        }
                        echo '> ' . CHtml::encode($tag->name) . '</label>';
                    }
                } else {
                    if ($veranstaltung->tags[0]->istTagesordnungspunkt()) {
                        echo '<label class="legend">Tagesordnungspunkt</label>';
                    } else {
                        echo '<label class="legend">Themengebiet</label>';
                    }
                    foreach ($veranstaltung->tags as $tag) {
                        echo '<label class="radio" style="margin-right: 10px;"><input name="tag" value="' . $tag->id . '" type="radio" ';
                        if (in_array($tag->id, $tags_pre)) {
                            echo ' checked';
                        }
                        echo '> ' . CHtml::encode($tag->name) . '</label>';
                    }
                }
                ?>
            </fieldset>
        <?php

        }

        ?>

        <fieldset>

            <label class="legend" for="Antrag_name">Überschrift</label>
            <?
            if ($veinstellungen->titel_maxlength > 0) echo 'Maximale Länge: ' . $veinstellungen->titel_maxlength . ' Zeichen<br>';
            ?>
            <input name="Antrag[name]" id="Antrag_name" type="text" value="<?php echo CHtml::encode($model->name); ?>" <?php
            if ($veinstellungen->titel_maxlength > 0) echo 'maxlength="' . $veinstellungen->titel_maxlength . '"';
            ?>>

        </fieldset>

        <?

        $text2name = veranstaltungsspezifisch_text2_name($model->veranstaltung, $model->typ);
        if ($text2name) {
            $antrag_max_len2 = $this->veranstaltung->getAntragMaxLen($force_type, true);
            ?>

            <fieldset class="control-group textarea" <?php
            if ($antrag_max_len2 > 0) {
                echo " data-max_len=\"" . $antrag_max_len2 . "\"";
            }
            ?>>

                <legend><?php echo CHtml::encode($text2name); ?></legend>

                <?php if ($antrag_max_len > 0) {
                    echo '<div class="max_len_hint">';
                    echo '<div class="calm">Maximale Länge: ' . $antrag_max_len2 . ' Zeichen</div>';
                    echo '<div class="alert">Text zu lang - maximale Länge: ' . $antrag_max_len2 . ' Zeichen</div>';
                    echo '</div>';
                } ?>

                <div class="text_full_width">
                    <label style="display: none;" class="control-label required" for="Antrag_text">
                        <?php echo CHtml::encode($text2name); ?>
                        <span class="required">*</span>
                    </label>

                    <div class="controls">
                        <!--<a href="#" onClick="alert('TODO'); return false;">&gt; Text aus einem Pad kopieren</a><br>-->
					<textarea id="Antrag_text2" class="span8" name="Antrag[text2]" rows="5" cols="80"><?php
                        echo CHtml::encode($model->text2);
                        ?></textarea>
                    </div>

                </div>
            </fieldset>

        <?php
        }
        ?>

        <fieldset class="control-group textarea" <?php
        if ($antrag_max_len > 0) {
            echo " data-max_len=\"" . $antrag_max_len . "\"";
        }
        ?>>

            <legend><?php
                $text1name = veranstaltungsspezifisch_text1_name($model->veranstaltung, $model->typ);
                if ($text1name) {
                    echo CHtml::encode($text1name);
                } else {
                    echo $sprache->get("Antragstext");
                }
                ?></legend>

            <?php if ($antrag_max_len > 0) {
                echo '<div class="max_len_hint">';
                echo '<div class="calm">Maximale Länge: ' . $antrag_max_len . ' Zeichen</div>';
                echo '<div class="alert">Text zu lang - maximale Länge: ' . $antrag_max_len . ' Zeichen</div>';
                echo '</div>';
            } ?>

            <div class="text_full_width">
                <label style="display: none;" class="control-label required" for="Antrag_text">
                    <?php echo $sprache->get("Antragstext"); ?>
                    <span class="required">*</span>
                </label>

                <div class="controls">
                    <!--<a href="#" onClick="alert('TODO'); return false;">&gt; Text aus einem Pad kopieren</a><br>-->
					<textarea id="Antrag_text" class="span8" name="Antrag[text]" rows="5" cols="80"><?php
                        echo CHtml::encode($model->text);
                        ?></textarea>
                </div>

            </div>
        </fieldset>

        <?

        if ($model->veranstaltung->getEinstellungen()->antrag_begruendungen) {
            $begruendung_max_len = veranstaltungsspezifisch_begruendung_maxlen($model->veranstaltung, $model->typ);
            ?>
			<fieldset class="control-group textarea" <?php
            if ($begruendung_max_len > 0) {
                echo " data-max_len=\"" . $begruendung_max_len . "\"";
            }
            if ($veranstaltung->url_verzeichnis == "phase2" && $veranstaltung->veranstaltungsreihe->subdomain == "wiesbaden" && $model->typ == Antrag::$TYP_ANTRAG) {
                echo " data-max_len_soft=\"1\"";
                $maxlen_hint = "Bitte maximale Länge:";
            } else {
                $maxlen_hint = "Maximale Länge:";
            }
            ?>>
                <legend><?php
            $bname = veranstaltungsspezifisch_begruendung_name($model->veranstaltung, $model->typ);
            if ($bname) {
                echo CHtml::encode($bname);
            } else {
                echo "Begründung";
            }
            ?></legend>

                    <?php if ($begruendung_max_len > 0) {
                echo '<div class="max_len_hint">';
                echo '<div class="calm">' . $maxlen_hint . ' ' . $begruendung_max_len . ' Zeichen</div>';
                echo '<div class="alert">Text zu lang - maximale Länge: ' . $begruendung_max_len . ' Zeichen</div>';
                echo '</div>';
            } ?>

				<div class="text_full_width">
					<label style="display: none;" class="control-label required" for="Antrag_begruendung">
						Begründung
						<span class="required">*</span>
					</label>

					<div class="controls">
						<textarea id="Antrag_begruendung" class="span8" name="Antrag[begruendung]" rows="5" cols="80"><?= CHtml::encode($model->begruendung) ?></textarea>
						<input type="hidden" id="Antrag_begruendung_html" name="Antrag[begruendung_html]"
							   value="<?php echo $model->veranstaltung->getEinstellungen()->begruendung_in_html; ?>">
					</div>

				</div>
			</fieldset>
		<?php
        }

        if (!$this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts && !veranstaltungsspezifisch_erzwinge_login($this->veranstaltung)) {
            $this->renderPartial($model->veranstaltung->getPolicyAntraege()->getAntragstellerInView(), array(
                "form"               => $form,
                "mode"               => $mode,
                "antrag"             => $model,
                "antragstellerIn"    => $antragstellerIn,
                "unterstuetzerInnen" => $unterstuetzerInnen,
                "veranstaltung"      => $veranstaltung,
                "hiddens"            => $hiddens,
                "js_protection"      => $js_protection,
                "login_warnung"      => Yii::app()->user->isGuest,
                "sprache"            => $model->veranstaltung->getSprache(),
            ));
        }
        ?>

        <div style="float: right;">
            <?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => 'Weiter')); ?>
        </div>
        <br>
    </div>


    <script>
        $(function () {
            ckeditor_bbcode("Antrag_text");
            <?php if ($model->veranstaltung->getEinstellungen()->antrag_begruendungen) { ?>
            if ($("#Antrag_begruendung_html").val() == "1") {
                ckeditor_simplehtml("Antrag_begruendung");
            } else {
                ckeditor_bbcode("Antrag_begruendung");
            }
            <?php }
            if ($text2name) { ?>
            ckeditor_bbcode("Antrag_text2");
            <? } ?>
        });
    </script>

<?php $this->endWidget(); ?>