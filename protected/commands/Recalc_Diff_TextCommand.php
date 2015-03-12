<?php

class Recalc_Diff_TextCommand extends CConsoleCommand {

	/**
	 * @param int $id
	 * @param bool $verbose
	 */
	private function recalcAeText($id, $verbose) {
		/** @var Aenderungsantrag $aenderungsantrag */
		$aenderungsantrag = Aenderungsantrag::model()->findByPk($id);
		if (!$aenderungsantrag) die("Änderungsantrag nicht gefunden\n");

		$aenderungsantrag->antrag->text = HtmlBBcodeUtils::bbcode_normalize($aenderungsantrag->antrag->text);
		$aenderungsantrag->antrag->save();

		$aenderungsantrag->calcDiffText();
		$aenderungsantrag->save(false);

		if ($verbose) {
			echo "Erledigt. Neuer Änderungstext:\n";
			echo $aenderungsantrag->aenderung_text . "\n";
		}
	}

	public function run($args) {
		if (count($args) != 1) {
			echo "Aufruf: ./yiic recalc_diff_text [Änderungsantrags-ID|alle]\n";
			return;
		}

		if ($args[0] == "alle") {
			/** @var Aenderungsantrag[] $aenderungsantrag */
			$aenderungsantrag = Aenderungsantrag::model()->findAll(array("order" => "id DESC"));
			foreach ($aenderungsantrag as $ae) try {
				echo "Recalculating: " . $ae->id . "\n";
				$this->recalcAeText($ae->id, false);
			} catch (Exception $e) {
				echo "AE inkonsistent!\n";
			}
		} else {
			$this->recalcAeText($args[0], true);
		}
	}
}
