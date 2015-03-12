<?php

$GLOBALS["TESTS"] = array(
	array(
		"src"                => "Ein [URL=\"https://www.antragsgruen.de/\"]BBCode-Link[/URL] ohne Anführungszeichen",
		"expected_html"      => array(
			"<div class='text'><span class='zeilennummer'>1</span>Ein <a href=\"https://www.antragsgruen.de/\">BBCode-Link</a> ohne Anf&uuml;hrungszeichen</div>",
		),
		"expected_plain"     => array(
			"Ein <a href=\"https://www.antragsgruen.de/\">BBCode-Link</a> ohne Anf&uuml;hrungszeichen",
		),
		"line_length"        => 80,
		"presentation_hacks" => true,
	),
	array(
		"src"                => "Ein [url=https://www.antragsgruen.de/]BBCode-Link[/URL] ohne Anführungszeichen",
		"expected_html"      => array(
			"<div class='text'><span class='zeilennummer'>1</span>Ein <a href=\"https://www.antragsgruen.de/\">BBCode-Link</a> ohne Anf&uuml;hrungszeichen</div>",
		),
		"expected_plain"     => array(
			"Ein <a href=\"https://www.antragsgruen.de/\">BBCode-Link</a> ohne Anf&uuml;hrungszeichen",
		),
		"line_length"        => 80,
		"presentation_hacks" => true,
	),
);


class Test_BBCodeCommand extends CConsoleCommand
{
	public function run($args)
	{
		foreach ($GLOBALS["TESTS"] as $test) {
			HtmlBBcodeUtils::initZeilenCounter();
			$res          = HtmlBBcodeUtils::bbcode2html_absaetze($test["src"], $test["presentation_hacks"], $test["line_length"]);
			$allesrichtig = true;
			if (count($res["html"]) != count($test["expected_html"])) $allesrichtig = false;
			if (count($res["html_plain"]) != count($test["expected_plain"])) $allesrichtig = false;
			if ($allesrichtig) {
				foreach ($res["html"] as $i => $line) if ($test["expected_html"][$i] !== $line) $allesrichtig = false;
				foreach ($res["html_plain"] as $i => $line) if ($test["expected_plain"][$i] !== $line) $allesrichtig = false;
			}
			if ($allesrichtig) {
				echo "Korrekt\n";
			} else {
				echo "FEHLER!\n";
				echo "Erwartet (HTML):\n";
				var_dump($test["expected_html"]);
				echo "Bekommen (HTML):\n";
				var_dump($res["html"]);
				echo "Erwartet (Plain): \n";
				var_dump($test["expected_plain"]);
				echo "Bekommen (Plain):\n";
				var_dump($res["html_plain"]);
				echo "\n";
			}
		}
	}
}