<?php

class Recalc_Diff_TextCommand extends CConsoleCommand {
	public function run($args) {
		if (count($args) != 1) {
			echo "Aufruf: ./yiic recalc_diff_text [Änderungsantrags-ID]\n";
			return;
		}

		/** @var Aenderungsantrag $aenderungsantrag */
		$aenderungsantrag = Aenderungsantrag::model()->findByPk($args[0]);
		if (!$aenderungsantrag) die("Änderungsantrag nicht gefunden\n");

		$aenderungsantrag->antrag->text = HtmlBBcodeUtils::bbcode_normalize($aenderungsantrag->antrag->text);
		$aenderungsantrag->antrag->save();

		$aenderungsantrag->calcDiffText();
		$aenderungsantrag->save(false);

		echo "Erledigt. Neuer Änderungstext:\n";
		echo $aenderungsantrag->aenderung_text . "\n";
	}
}
