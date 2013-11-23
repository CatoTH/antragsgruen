<?php
/**
 * @var InfosController $this
 * @var array|Veranstaltungsreihe[] $reihen
 * @var CInstanzAnlegenForm $anlegenformmodel
 * @var string $error_string
 */

include(__DIR__ . "/sidebar.php");

$assets_base = $this->getAssetsBase();

/** @var CWebApplication $app */
$app = Yii::app();

$app->getClientScript()->registerCssFile($assets_base . '/css/formwizard.css');

?>
<h1>Antragsgrün-Instanz anlegen</h1>

<div class="fuelux">

	<?php
	/** @var CActiveForm $form  */
	$form=$this->beginWidget('CActiveForm', array(
		'id'=>'instanz-anlegen-form',
		'enableAjaxValidation'=>true,
		'enableClientValidation'=>true,
	));
	?>

	<div id="AnlegenWizard" class="wizard">
		<ul class="steps">
			<li data-target="#step1" class="active"><span class="badge badge-info">1</span>Einsatzzweck<span class="chevron"></span></li>
			<li data-target="#step2"><span class="badge">2</span>Details<span class="chevron"></span></li>
			<li data-target="#step3"><span class="badge">3</span>Kontaktdaten<span class="chevron"></span></li>
		</ul>
		<div class="actions">
			<!--<button class="btn btn-mini btn-prev"><i class="icon-arrow-left"></i>Zurück</button>-->
			<!--<button class="btn btn-mini btn-next" data-last="Finish">Next<i class="icon-arrow-right"></i></button>-->
		</div>
	</div>
	<div class="content step-content">
		<div class="step-pane active" id="step1">
			<?php
			if ($error_string != "") echo '<div class="alert alert-error">' . $error_string . '</div>';
			?>

			<label class="einsatzzweck"><?php echo CHtml::activeRadioButton($anlegenformmodel, "typ", array("value" => Veranstaltung::$TYP_PROGRAMM, "uncheckValue" => null)); ?> <div>Programmdiskussion</div></label>
			<div class="einsatzzweck_erkl">
				Folgendes ist hier voreingestellt <sup>1</sup>:
				<ul>
					<li>Es heißt "Kapitel" und "Änderungswünsche"</li>
					<li>Die Zeilennummerierung ist fortlaufend durch alle Kapitel</li>
					<li>Nummerierung der Änderungswünsche fortlaufend über die Kapitel hinweg.</li>
					<li>Keine Freischaltung von Änderungswünschen nötig</li>
				</ul>
			</div>

			<label class="einsatzzweck"><?php echo CHtml::activeRadioButton($anlegenformmodel, "typ", array("value" => Veranstaltung::$TYP_PARTEITAG, "uncheckValue" => null)); ?> <div>Parteitag</div></label>
			<div class="einsatzzweck_erkl">
				Folgendes ist hier voreingestellt <sup>1</sup>:
				<ul>
					<li>Es heißt "Anträge" und "Änderungsanträge"</li>
					<li>Neue Zeilennummerierung bei jedem Antrag</li>
					<li>Nummerierung der Änderungsanträge pro Antrag separat</li>
					<li>Freischaltung von Anträgen und Änderungsanträgen nötig</li>
				</ul>
			</div>

			<div class="weiter">
				<button class="btn btn-primary" id="weiter-1"><span class='icon-chevron-right'></span> Weiter</button>
			</div>
			<br><br>
			<strong><sup>1</sup> Hinweis:</strong> alle genannten Voreinstellungen können nachträglich unabhängig voneinander angepasst werden.

		</div>
		<div class="step-pane" id="step2">
			<br><br>
			<div class="name">
				<?php echo CHtml::activeLabelEx($anlegenformmodel,'name', array('label' => '<strong>Name der Veranstaltung / des Programms:</strong>')); ?>
				<div class="alert alert-error" style="display: none;">Bitte gib einen Namen ein.</div>
				<?php echo CHtml::activeTextField($anlegenformmodel,'name') ?>
			</div>

			<br><br>
			<div class="url">
				<?php echo CHtml::activeLabelEx($anlegenformmodel,'subdomain', array('label' => '<strong>Unter folgender Adresse soll es erreichbar sein:</strong>')); ?>
				<div class="alert alert-error" style="display: none;">Bitte gib eine Subdomain ein.</div>
				<span class="domain">http://<?php echo CHtml::activeTextField($anlegenformmodel,'subdomain', array('placeholder' => 'Subdomain')) ?>.antragsgruen.de</span>
			</div>

			<br><br>
			<div class="email">
				<?php echo CHtml::activeLabelEx($anlegenformmodel,'admin_email', array('label' => '<strong>Benachrichtigungen</strong> über neue Kommentare und Änderungsanträge sollen an diese E-Mail-Adresse geschickt werden: <small>(leer = keine Benachrichtigung)</small>')); ?>
				<?php echo CHtml::activeEmailField($anlegenformmodel,'admin_email', array('placeholder' => 'irgendeine@email.de')) ?>
			</div>

			<br><br>

			<label class="policy"><?php echo CHtml::activeCheckBox($anlegenformmodel, "kommentare_moeglich"); ?> BenutzerInnen können (Änderungs-)Anträge kommentieren</label>
			<br>
			<label class="policy"><?php echo CHtml::activeCheckBox($anlegenformmodel, "aenderungsantraege_moeglich"); ?> BenutzerInnen können Änderungsanträge stellen</label>
			<br>
			<br>

			<div class="weiter">
				<button class="btn btn-primary" id="weiter-2"><span class='icon-chevron-right'></span> Weiter</button>
			</div>

		</div>
		<div class="step-pane" id="step3">
			<br>
			<div class="kontakt">
				<?php echo CHtml::activeLabelEx($anlegenformmodel,'kontakt', array("label" => "<strong>Kontaktadresse:</strong> (postalisch + E-Mail; wird standardmäßig im Impressum genannt)")); ?>
				<?php echo CHtml::activeTextArea($anlegenformmodel,'kontakt') ?>
			</div>
			<br><br>

			<div class="zahlung">
				<strong>Wärest du bereit, einen freiwilligen Beitrag über 20€ an die Netzbegrünung zu leisten?</strong><br>(Wenn ja, schicken wir dir eine Rechnung an die oben eingegebene Adresse)<br>
				<?php
				echo CHtml::activeRadioButtonList($anlegenformmodel, "zahlung", VeranstaltungsreihenEinstellungen::$BEREIT_ZU_ZAHLEN);
				?>
			</div>
			<br>

			<div class="weiter">
				<?php
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'success', 'label' => 'Anlegen', 'icon' => 'icon-ok', 'htmlOptions' => array('name' => AntiXSS::createToken('anlegen'))));
				?>
			</div>

		</div>
	</div>
	<script>
		$(function() { instanz_neu_anlegen_init(); });
	</script>

	<?php $this->endWidget(); ?>
</div>