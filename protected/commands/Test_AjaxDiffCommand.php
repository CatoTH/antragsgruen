<?php

$GLOBALS["TESTS"] = array(
	array(
		"antrag_id" => 1243,
		"absatz_nr" => 39,
		"text_neu"  => '[list]
[*]Die Legitimität des ukrainischen Parlaments und der Regierung muss durch Neuwahlen wiederhergestellt werden.
[*]Um die Gewalt in der Ostukraine zu beenden,
- müssen sich alle Akteure beharrlich für einen Waffenstillstand einsetzen;
- befürworten wir die Sicherung der Grenze durch russische und ukrainische Grenzschützer auf Basis von Kooperation unter Kontrolle der OSZE;
- müssen die irregulären Truppen schrittweise aufgelöst werden.
[*]Es müssen direkte Gespräche zwischen der ukrainischen Führung und den lokalen Aufständischen in der Ostukraine unter internationaler Vermittlung erfolgen. Die  Bereitschaft der Kiewer Regierung, mit den ostukrainischen Separatisten in  Verhandlungen zu treten, sollte seitens der Bundesregierung und der EU  massiv gefördert werden.
[*]Es muss ein neues Genfer Treffen unter Einbeziehung Russlands, der EU, der USA, der ukrainischen Regierung und von VertreterInnen der aufständischen Kräfte in der Ostukraine stattfinden.
[*]Gemäß der Genfer Erklärung müssen prorussische Milizen und nationalistische Paramilitärs in der Ukraine ihre Waffen abgeben; jegliche gewaltsame Auseinandersetzung muss beendet werden; Russland und die Kiewer Übergangsregierung müssen hierbei endlich ihrer Verantwortung gerecht werden und diese Maßnahmen aktiv unterstützen.
[*]Für entstandene Menschenrechtsverletzungen und eventuelle Kriegsverbrechen darf es keine Straffreiheit geben. Menschenrechtsorganisationen und der UN-Menschenrechtsrat werfen den pro-russischen Separatisten schwere Menschenrechtsverletzungen vor – darunter Folter, Entführungen, gezielte Tötungen und die Unterbindung medizinischer Hilfeleistung. Ukrainischen Regierungstruppen wird – ebenso wie in geringerem Umfang den Separatisten – unter anderem die Gefährdung und Tötung von Zivilisten vorgeworfen, insbesondere durch die wiederholte Nutzung unpräziser Grad-Raketen in dicht besiedelten Gebieten.
[*]Es braucht nun schnellstmöglich eine vollständige, unabhängige und unparteiliche internationale Untersuchung all dieser Vorwürfe – inklusive des Absturzes der MH17 und der Vorkommnisse in Odessa vom 2. Mai 2014 und den Toten auf dem Kiewer Maidan. Alle Versuche, Verletzungen der Menschenrechte und des Humanitären Völkerrechts in der Ukraine zu dokumentieren, müssen mit Blick auf eine zukünftige Strafverfolgung unterstützt werden. Die Verantwortlichen auf allen Seiten des Konfliktes müssen zur Rechenschaft gezogen werden.
[*]Alle Konfliktparteien sind aufgerufen, Menschenrechtsverletzungen weder zu verüben noch zuzulassen. Alle Entführungsopfer sind umgehend freizulassen. Minderheitenrechte müssen für alle Betroffenen gewährleistet werden. Der Schutz von Zivilisten und die Einhaltung der Menschenrechte müssen endlich absolute Priorität genießen.
[*]Die Versuche der Übergangsregierung in Kiew, die Konflikte in Teilen der Ukraine militärisch zu lösen, müssen sofort beendet werden, da die Konflikte dadurch nur noch weiter eskalieren; stattdessen müssen die Kampfhandlungen sofort eingestellt und es muss ein tragfähiger Waffenstillstand vereinbart werden, damit sodann zivile Formen der Konfliktbearbeitung zum Einsatz kommen können. Die deutsche Bundesregierung und die EU müssen sich dafür stark machen.
[*]Die NATO muss sowohl die verbale Eskalation als auch die tatsächlichen militärischen Maßnahmen beenden.  Sie zieht die zusätzlichen Truppen aus den direkten Nachbarstaaten ab und beginnt mit Russland einen erneuten Dialog über friedenssichernde Maßnahmen in Europa. Gleichzeitig muss Russland seine Truppen von der ukrainischen Grenze zurückziehen.
[*]Runde Tische können auf lokaler, regionaler und gesamtstaatlicher Ebene in der Ukraine – unter repräsentativer Einbeziehung von Frauen – zur Bearbeitung der Konflikte und zur Vorbereitung der Wahlen geeignet sein.
[*]Vermeidung einer neuen Blockkonfrontation zwischen Russland und den anderen Staaten des europäischen Kontinents.
[/list]',
	)
);


class Test_AjaxDiffCommand extends CConsoleCommand
{
	public function run($args)
	{
		foreach ($GLOBALS["TESTS"] as $test) {
			/** @var Antrag $antrag */
			$antrag = Antrag::model()->findByPk($test["antrag_id"]);
			$diffs  = array();
			/** @var array|AntragAbsatz[] $pars */
			$pars                      = $antrag->getParagraphs();
			$diffs[$test["absatz_nr"]] = DiffUtils::renderBBCodeDiff2HTML($pars[$test["absatz_nr"]]->str_bbcode, $test["text_neu"], false, 0, "", true);

			var_dump($diffs);
		}
	}
}