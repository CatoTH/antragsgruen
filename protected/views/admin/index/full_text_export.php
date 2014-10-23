<?php
/**
 * @var IndexController $this
 * @var Antrag[] $antraege
 * @var Aenderungsantrag[] $aenderungsantraege
 */

Header("Content-Type: text/plain; charset=UTF-8");

foreach ($antraege as $antrag) {
	echo $antrag->name . "\n";
	echo HtmlBBcodeUtils::removeBBCode($antrag->text) . "\n\n";
	echo HtmlBBcodeUtils::removeBBCode($antrag->begruendung) . "\n\n\n";
}

foreach ($aenderungsantraege as $ae) {
	$diff = $ae->getDiffParagraphs();
	foreach ($diff as $line) if ($line != "") echo HtmlBBcodeUtils::removeBBCode($line) . "\n\n";
	echo HtmlBBcodeUtils::removeBBCode($ae->aenderung_begruendung) . "\n\n\n";

}