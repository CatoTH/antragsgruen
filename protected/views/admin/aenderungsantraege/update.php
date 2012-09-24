<?php
/* @var $this AenderungsantraegeController */
/* @var $model Aenderungsantrag */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	$model->label(2)                => array('index'),
	Yii::t('app', 'Update'),
);

$this->menu = array(
	array('label' => "Änderungsanträge", 'url'=> array('index'), "icon" => "home"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'Create'), 'url'=> array('create'), "icon" => "plus-sign"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'View'), 'url'=> "/aenderungsantrag/anzeige/?id=" . $model->id, "icon" => "eye-open"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'Delete'), 'url' => '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm' => 'Are you sure you want to delete this item?'), "icon" => "remove"),
	array('label' => "Durchsuchen", 'url'=> array('admin'), "icon" => "th-list"),
);
?>
<div class="well well_first">
<h1><?php echo Yii::t('app', 'Update') . ': ' . GxHtml::encode($model->label()) ?></h1>
<br>
<?php
	if ($model->status == Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT) {
		$form = $this->beginWidget('GxActiveForm');
		$max_rev = 0;

		$andereantrs = $model->antrag->aenderungsantraege;
		foreach ($andereantrs as $antr) {
			// Etwas messy, wg. "Ä" und UTF-8. Alternative Implementierung: auf mbstring.func_overload testen und entsprechend vorgehen
			$index = -1;
			for ($i = 0; $i < strlen($antr->revision_name) && $index == -1; $i++) {
				if (is_numeric(substr($antr->revision_name, $i, 1))) $index = $i;
			}
			$revs = substr($antr->revision_name, $index);
			$revnr = IntVal($revs);
			if ($revnr > $max_rev) $max_rev = $revnr;
		}
		$new_rev = "Ä" . ($max_rev + 1);
		$new_rev_long = $new_rev . " zu " . $model->antrag->revision_name;
		echo '<input type="hidden" name="' . AntiXSS::createToken("antrag_freischalten") . '" value="' . CHtml::encode($new_rev). '">';
		echo "<div style='text-align: center;'>";
		$this->widget('bootstrap.widgets.TbButton', array('buttonType'=>'submit', 'type' => 'primary', 'icon'=>'ok white', 'label'=>'Freischalten als ' . $new_rev_long));
		echo "</div>";
		$this->endWidget();
		echo "<br>";
	}

	//if (count($messages) > 0) echo "<strong>" . GxHtml::encode(implode("<br>", $messages)) . "</strong><br><br>";


	$this->renderPartial('_form', array(
	'model' => $model));
?>
	</div>