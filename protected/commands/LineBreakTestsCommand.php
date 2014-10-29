<?php

$GLOBALS["TESTS"] = array(
	array(
		"is_bbcode" => false,
		"src"      => "Geschäftsordnung der Bundesversammlung geregelt. Antragsberechtigt sind die Orts- und Kreisverbände, die Landesversammlungen bzw. Landesdelegiertenkonferenzen,",
		"expected" => array(
			"Geschäftsordnung der Bundesversammlung geregelt. Antragsberechtigt sind die",
			"Orts- und Kreisverbände, die Landesversammlungen bzw.",
			"Landesdelegiertenkonferenzen,"
		),
	),
	array(
		"is_bbcode" => false,
		"src"      => "gut und richtig, wenn Eltern selbst eine Initiative für Kinderbetreuung gründen – besser ist",
		"expected" => array(
			"gut und richtig, wenn Eltern selbst eine Initiative für Kinderbetreuung gründen",
			"– besser ist"
		),
	),
	array(
		"is_bbcode" => false,
		"src"      => "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, wenn sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. Es ist gut und richtig,",
		"expected" => array(
			"angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, wenn",
			"sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. Es ist",
			"gut und richtig,"
		)
	),
	array(
		"is_bbcode" => false,
		"src"      => "angehen, ist von gro&szlig;em Wert f&uuml;r unser Land. Ver&auml;nderung kann nur gelingen, wenn sie von Vielen ",
		"expected" => array(
			"angehen, ist von gro&szlig;em Wert f&uuml;r unser Land. Ver&auml;nderung kann nur gelingen, wenn",
			"sie von Vielen "
		)
	),
	array(
		"is_bbcode" => true,
		"src" => "In unserem Grundsatzprogramm haben wir einen [B]emanzipatorischen[/B] Freiheitsbegriff definiert.",
		"expected" => array(
			"In unserem Grundsatzprogramm haben wir einen [B]emanzipatorischen[/B] Freiheitsbegriff",
			"definiert.",
		)
	)
);


class LineBreakTestsCommand extends CConsoleCommand
{
	public function run($args)
	{
		foreach ($GLOBALS["TESTS"] as $test) {
			$x       = HtmlBBcodeUtils::text2zeilen($test["src"], 80, $test["is_bbcode"], false, true);
			$correct = true;
			if (count($x) != count($test["expected"])) $correct = false;
			else for ($i = 0; $i < count($test["expected"]); $i++) if ($test["expected"][$i] != $x[$i]) $correct = false;
			if ($correct) echo "Korrekt\n";
			else {
				$x = HtmlBBcodeUtils::text2zeilen($test["src"], 80, $test["is_bbcode"], true, true);
				var_dump($x);
			}
			echo "\n";
		}
	}
}