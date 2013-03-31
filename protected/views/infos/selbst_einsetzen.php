<?php
/**
 * @var InfosController $this
 * @var array|Veranstaltungsreihe[] $reihen
 */

include(__DIR__ . "/sidebar.php");

?>
<h1 class="well">Antragsgrün selbst einsetzen</h1>

<div class="well well_first">

	<div class="content">
		<p>Antragsgrün ist <strong>Open Source</strong>, jede und jeder kann die Software herunterladen, für die eigenen Zwecke anpassen und verwenden (siehe <a href="#opensource">weiter unten</a>).</p>

		<p><strong>Für Mitglieder der Grünen ist es noch einfacher</strong>: einfach weiter unten <a href="#selbst_nutzen">mit dem Wurzelwerk-BenutzerInnennamen einloggen</a>, ein paar Angaben zum Einsatzzweck machen, bei Bedarf noch einige Feineinstellungen vornehmen, und los!</p>

		<p>Antragsgrün wird von der <a href="http://www.netzbegruenung.de/"><strong>Netzbegrünung</strong></a> betrieben und kann kostenlos genutzt werden. Um einen freiwilligen Beitrag für den Betrieb wird aber sehr gebeten.</p>

		<p>Falls Antragsgrün noch nicht alle Funktionen erfüllt, die benötigt werden, können wir es auf Auftrag auch für deine Zwecke <strong>anpassen</strong>. <a href="#wer">Einfach fragen!</a></p>
	</div>

	<h2 id="funktionen">Welche Funktionen bietet Antragsgrün?</h2>

	<div class="content">
		<strong>Das kann Antragsgrün:</strong>
		<ul style="margin-bottom: 15px;">
			<li style="margin-top: 7px;">Anträge, Änderungsanträge, Kommentare dazu, Unterstützen von (Änderungs-)Anträgen, Bewertung von Kommentaren. Alles außer die Anträge ist auch deaktivierbar.</li>
			<li style="margin-top: 7px;">Änderungsanträge und Kommentare beziehen sich grundsätzlich immer auf ganze Absätze.</li>
			<li style="margin-top: 7px;"><strong>Berechtigungen:</strong> Wer Anträge, Änderungsanträge und Kommentare verfassen darf, lässt sich jeweils festlegen. Niemand / nur Admins, Alle, oder nur Eingeloggte NutzerInnen.</li>
			<li style="margin-top: 7px;">Auf Wunsch: (Änderungs-)Anträge oder Kommentare erscheinen erst nach expliziter <strong>Freischaltung</strong> durch einen Admin.</li>
			<li style="margin-top: 7px;">Beliebige <strong>Textformatierungen</strong> in redaktionellen Texten (u.a. auch YouTube/Vimeo-Videos, Grafiken etc.). Bei Anträgen und Änderungsanträgen sind einige Standard-Textformatierungen möglich.</li>
			<li style="margin-top: 7px;">Automatisch erzeugte <strong>PDF-Versionen</strong> der Anträge und Änderungsanträge.</li>
			<li style="margin-top: 7px;">Es ist einstellbar, ob im Frontend von "Anträgen" und "Änderungsanträgen" die Rede ist (ausgelegt auf Parteitage), oder von "Kapiteln" und "Änderungswünschen" (ausgelegt auf die Diskussion von Wahlprogrammen).</li>
			<li style="margin-top: 7px;"><strong>RSS-Feeds</strong>, damit alle Interessierte über neu eingereichte (Änderungs-)Anträge oder Kommentare auf dem Laufenden bleiben.</li>
			<li style="margin-top: 7px;"><strong>Veranstaltungsreihen</strong> werden unterstützt - wenn Antragsgrün also für eine regelmäßig stattfindende Veranstaltung wiederholt eingesetzt werden soll (oder es mehrere Iterationen bei der Ausarbeitung eines Wahlprogramms geben soll), muss nicht
				jedes Mal alles aufs Neue eingerichtet werden.
			</li>
		</ul>

		<strong>Geplant ist außerdem:</strong>
		<ul style="margin-bottom: 15px;">
			<li style="margin-top: 7px;">AntragsstellerInnen sollen Anträge überarbeiten, Änderungsanträge übernehmen oder den Antrag ganz zurückziehen können.</li>
			<li style="margin-top: 7px;">...</li>
		</ul>

		<strong>Das kann Antragsgrün nicht</strong> (und ist auch nicht geplant):
		<ul style="margin-bottom: 15px;">
			<li style="margin-top: 7px;"><strong>Vor-Ort-Präsentationen</strong>. Auf Parteitagen selbst bietet sich der Einsatz von Tools an, die speziell dafür ausgelegt sind - wir empfehlen hier <a href="http://openslides.org/de/">OpenSlides</a>.</li>
			<li style="margin-top: 7px;"><strong>Wahlen / Abstimmungen</strong>.</li>
		</ul>


	</div>

	<h2 id="selbst_nutzen">Antragsgrün selbst nutzen</h2>

	<div class="content">
		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm', array(
			"htmlOptions" => array(
				"class" => "well well_first",
			),
		));
		?>
		Um dir sofort eine eigene Version von Antragsgrün einzurichten, logge dich zunächst mit deinem Wurzelwerk-Account ein. Falls du die Zugangsdaten zurzeit nicht hast, <a href="#wer">schreib uns einfach an</a>.
		<div style="overflow: auto; margin-top: 25px;">
			<div style="float: left;">
				<label for="OAuthLoginForm_wurzelwerk">Wurzelwerk-BenutzerInnenname</label>
				<input class="span3" name="OAuthLoginForm[wurzelwerk]" id="OAuthLoginForm_wurzelwerk" type="text" style="margin-bottom: 0; "/><br>
				<a href="https://www.netz.gruene.de/passwordForgotten.form" target="_blank" style="font-size: 0.8em; margin-top: -7px; display: inline-block; margin-bottom: 10px;">Wurzelwerk-Zugangsdaten vergessen?</a>
				<span class="help-block error" id="OAuthLoginForm_wurzelwerk_em_" style="display: none"></span>
			</div>
			<div style="float: left; margin-left: 20px;">
				<label>&nbsp;</label>
				<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'icon' => 'ok', 'label' => 'Einloggen')); ?>
			</div>
		</div>

		<?php $this->endWidget(); ?>
	</div>


	<h2 id="wer">Von wem stammt Antragsgrün?</h2>

	<div class="content">
		...
	</div>


	<h2 id="opensource">Open Source</h2>

	<div class="content">
		...
	</div>

</div>
