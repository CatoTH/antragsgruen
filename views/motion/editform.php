<?php

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\MotionEditForm $form
 * @var \app\models\db\Consultation $consultation
 * @var array $hiddens
 * @var bool $jsProtection
 * @var bool $forceTag
 */
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsTag;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;
$wording    = $consultation->getWording();

$params->addJS('/js/ckeditor/ckeditor.js');
$params->breadcrumbs        = [
    UrlHelper::createUrl('consultation/index') => $consultation->titleShort,
    $wording->get($form->motionId > 0 ? 'Antrag bearbeiten' : 'Neuer Antrag'),
];
$params->breadcrumbsTopname = $wording->get("breadcrumb_top");

if ($mode == 'create') {
    echo '<h1>' . Html::encode($wording->get('Antrag stellen')) . '</h1>';
} else {
    echo '<h1>' . Html::encode($wording->get('Antrag bearbeiten')) . '</h1>';
}

echo '<div class="form content">';

$motionPolicy = $consultation->getMotionPolicy();
if ($motionPolicy::getPolicyID() != \app\models\policies\All::getPolicyID()) {
    echo '<fieldset>
                <legend><?php echo $sprache->get("Voraussetzungen für einen Antrag") ?></legend>
            </fieldset>';

    echo $motionPolicy->getOnCreateDescription();
}

echo Html::beginForm('', '', ['id' => 'motionCreateForm']);

foreach ($hiddens as $name => $value) {
    echo '<input type="hidden" name="' . Html::encode($name) . '" value="' . Html::encode($value) . '">';
}

if ($jsProtection) {
    echo '<div class="alert alert-warning" role="alert">';
    echo 'Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.';
    echo '</div>';
}

echo '<div class="form-group">
    <label for="motionTitle">Überschrift</label>
    <input type="text" class="form-control" id="motionTitle" value="' . Html::encode($form->title) . '">
  </div>';

/** @var ConsultationSettingsTag][] $tags */
$tags = array();
if ($forceTag !== null) {
    $tags[$forceTag] = ConsultationSettingsTag::findOne($forceTag);
} else {
    foreach ($consultation->tags as $tag) {
        $tags[$tag->id] = $tag;
    }
}

if (count($tags) == 1) {
    $keys = array_keys($tags);
    echo '<input type="hidden" name="tags[]" value="' . $keys[0] . '">';
} else {
    echo '<fieldset><label class="legend">Antragstyp</label>';
    foreach ($tags as $id => $tag) {
        echo '<label class="radio-inline"><input name="tags[]" value="' . $id . '" type="radio" ';
        if (in_array($id, $form->tags)) {
            echo ' checked';
        }
        echo ' required> ' . Html::encode($tag->name) . '</label>';
    }
    echo '</fieldset>';
}


?>

        <fieldset class="control-group textarea" <?php
if ($antrag_max_len > 0) {
    echo " data-max_len=\"" . $antrag_max_len . "\"";
}
?>>

            <legend><?php echo $sprache->get("Antragstext"); ?></legend>

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

        <?php
if ($model->veranstaltung->getEinstellungen()->antrag_begruendungen) {
    ?>

    <fieldset class="control-group">
        <legend>Begründung</legend>

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

if (!$this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts && veranstaltungsspezifisch_erzwinge_login($this->veranstaltung)) {
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
<?php } ?>
        });
    </script>

<?php $this->endWidget(); ?>