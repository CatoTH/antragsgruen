<?php

class HtmlBBcodeUtils
{

	public static $br_implicit, $br_explicit;

	public static $zeilen_counter = 0;

	public static $zeilenlaenge;

	/**
	 * @param int $init
	 */
	public static function initZeilenCounter($init = 1)
	{
		self::$zeilen_counter = $init;
	}

	/**
	 * @param string $html
	 * @return string
	 */
	public static function html_normalize($html)
	{
		$config = HTMLPurifier_Config::createDefault();

		$config->set('Cache.SerializerPath', "/tmp/");
		// Ermöglicht Prozentangaben
		$config->set('CSS.MaxImgLength', null);
		$config->set('HTML.MaxImgLength', null);

		$purifier   = new HTMLPurifier($config);
		$clean_html = $purifier->purify($html);
		return $clean_html;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function bbcode_normalize($text)
	{
		$text = preg_replace("/=[0-9]+\.[0-9]+pt/su", "", $text);
		$text = preg_replace("/\[(\/?)list([^\]]*)\]/siu", "[\\1LIST\\2]", $text);
		$text = preg_replace("/\[(\/?)quote\]/siu", "[\\1QUOTE]", $text);
		$text = preg_replace("/\[(\/?)b\]/siu", "[\\1B]", $text);
		$text = preg_replace("/\[(\/?)i\]/siu", "[\\1I]", $text);
		$text = preg_replace("/\[(\/?)u\]/siu", "[\\1U]", $text);
		$text = preg_replace("/\[(\/?)url([^\]]*)\]/siu", "[\\1URL\\2]", $text);
		$text = preg_replace("/\[p\](.*)\[\/p\]/siuU", "\\1", $text);

		$text = str_replace(chr(194) . chr(160), " ", $text);
		$text = preg_replace("/ {2,}/", " ", $text);

		$x = explode("\n", $text);
		foreach ($x as $i => $zeile) {
			$x[$i] = trim($zeile);
		}
		return implode("\n", $x);
	}


	/**
	 * @static
	 * @param string $text
	 * @param int $zeilenlaenge
	 * @return array|string[]
	 */
	static function bbcode2zeilen_absaetze($text, $zeilenlaenge)
	{
		HtmlBBcodeUtils::$zeilenlaenge = $zeilenlaenge;
		$text = static::bbNormalizeForAbsaetze($text);
		/*
		$text                          = str_replace("\r", "", $text);
		$text                          = preg_replace("/\[list(.*)\[\/list\]/siU", "\n\n[LIST\\1[/LIST]\n\n", $text);
		$text                          = preg_replace("/\[quote(.*)\[\/quote\]/siU", "\n\n[QUOTE\\1[/QUOTE]\n\n", $text);
		$text                          = preg_replace("/\\n( *\\n)+/", "\n\n", $text);

		$text   = preg_replace("/[\\n ]+\[\*\]/siU", "\n[*]", $text);
		$text   = trim($text, " \n");
		*/
		$x      = explode("\n\n", $text);
		$return = array();

		foreach ($x as $i => $y) {
			if (mb_stripos($y, "[list") === 0 || mb_stripos($y, "[ulist") === 0 || mb_stripos($y, "[quote") === 0) {
				$str_neu = preg_replace_callback("/(\[quote[^\]]*\])(.*)(\[\/quote\])/siU", function ($matches) {
					$out = $matches[1];

					$zeils     = explode("\n", $matches[2]);
					$zeils_neu = array();
					foreach ($zeils as $zeile) {
						$x           = HtmlBBcodeUtils::text2zeilen($zeile, HtmlBBcodeUtils::$zeilenlaenge - 10);
						$zeils_neu[] = "###ZEILENNUMMER###" . implode("\n###ZEILENNUMMER###", $x);
					}
					$out .= implode("\n", $zeils_neu);
					$out .= $matches[3];

					return $out;
				}, $y);

				$str_neu = preg_replace_callback("/(\[list[^\]]*\])(.*)(\[\/list\])/siuU", function ($matches) {
					$out = $matches[1];
					$x   = explode("[*]", $matches[2]);

					if (count($x) > 1) for ($i = 1; $i < count($x); $i++) if (trim($x[$i]) != "") {
						$zeils     = explode("\n", trim($x[$i]));
						$zeils_neu = array();
						foreach ($zeils as $zeile) {
							$z           = HtmlBBcodeUtils::text2zeilen($zeile, HtmlBBcodeUtils::$zeilenlaenge - 10);
							$zeils_neu[] = "###ZEILENNUMMER###" . implode("\n###ZEILENNUMMER###", $z);
						}
						$out .= "[*]" . implode("[*]", $zeils_neu);
					}
					$out .= $matches[3];

					return $out;
				}, $str_neu);

			} else {
				$zeils     = explode("\n", $y);
				$zeils_neu = array();
				foreach ($zeils as $zeile) {
					$x           = HtmlBBcodeUtils::text2zeilen($zeile, HtmlBBcodeUtils::$zeilenlaenge);
					$zeils_neu[] = "###ZEILENNUMMER###" . implode("\n###ZEILENNUMMER###", $x);
				}

				$str_neu = implode("\n", $zeils_neu);
			}

			$str_neu = preg_replace_callback("/###ZEILENNUMMER###/", function () {
				return "#ZEILE#";
			}, $str_neu);

			$str_neu = preg_replace("/([^\\n])#ZEILE#/siu", "\\1\n#ZEILE#", $str_neu);

			$return[] = $str_neu;
		}

		return $return;
	}

	/**
	 * @param string $text
	 * @param int $zeilenlaenge
	 * @return array|int[]
	 */
	static function getBBCodeStats($text, $zeilenlaenge)
	{
		static::initZeilenCounter(1);
		$absaetze = static::bbcode2html_absaetze($text, false, $zeilenlaenge);
		$strs     = $absaetze["html"];
		preg_match_all("/<span class='zeilennummer'>([0-9]+)<\/span>/siu", $strs[count($strs) - 1], $matches);
		$anzahl_absaetze = count($strs);
		$anzahl_zeilen   = $matches[1][count($matches[1]) - 1];
		return array($anzahl_absaetze, $anzahl_zeilen);
	}

	/**
	 * @param string $text
	 * @return string
	 */
	static function bbNormalizeForAbsaetze($text)
	{
		$text = str_replace("\r", "", $text);
		$text = str_replace(chr(194) . chr(160), " ", $text);
		$text = str_replace("\n \n", "\n\n", $text);
		$text = preg_replace("/\[list(.*)\[\/list\]/siU", "\n\n[LIST\\1[/LIST]\n\n", $text);
		$text = preg_replace("/[ \\n\\r]*\[\/LIST\]/si", "[/LIST]", $text);
		$text = preg_replace("/\[quote(.*)\[\/quote\]/siU", "\n\n[QUOTE\\1[/QUOTE]\n\n", $text);
		$text = preg_replace("/\\n( *\\n)+/", "\n\n", $text);
		$text = preg_replace("/[\\n ]+\[\*\]/siU", "\n[*]", $text);
		$text = trim($text, " \n");
		return $text;
	}

	/**
	 * @static
	 * @param string $text
	 * @param bool $praesentations_hacks
	 * @param int $zeilenlaenge
	 * @return array|string[]
	 */
	static function bbcode2html_absaetze($text, $praesentations_hacks = false, $zeilenlaenge)
	{
		$text = static::bbNormalizeForAbsaetze($text);
		$x                   = explode("\n\n", $text);
		$absaetze_html       = array();
		$absaetze_bbcode     = array();
		$absaetze_html_plain = array();

		HtmlBBcodeUtils::$br_implicit  = ($praesentations_hacks ? " <br class='implicit'>" : "<br>"); // wird bei responsiver Ansicht manchmal ausgeblendet
		HtmlBBcodeUtils::$br_explicit  = "<br>";
		HtmlBBcodeUtils::$zeilenlaenge = $zeilenlaenge;

		foreach ($x as $i => $y) {
			$absaetze_bbcode[] = $y;
			$abs               = HtmlBBcodeUtils::bbcode2html($y);

			$absaetze_html_plain[] = $abs;

			if (mb_stripos($abs, "<ul") === 0 || mb_stripos($abs, "<ol") === 0 || mb_stripos($abs, "<blockquote") === 0) {
				$str_neu = str_ireplace("<ul", "<ul class='text'", $abs);
				$str_neu = str_ireplace("<ol", "<ol class='text'", $str_neu);
				$str_neu = str_ireplace("<blockquote", "<blockquote class='text'", $str_neu);
				$str_neu = preg_replace("/( |<br>)*<\/li>/", "</li>", $str_neu);
				$str_neu = preg_replace_callback("/(<(blockquote|li)[^>]*>)(.*)(<\/\\2>)/siU", function ($matches) {
					$out = $matches[1];

					$zeils     = explode("<br>", $matches[3]);
					$zeils_neu = array();
					foreach ($zeils as $zeile) {
						$x           = HtmlBBcodeUtils::text2zeilen($zeile, HtmlBBcodeUtils::$zeilenlaenge - 10);
						$zeils_neu[] = "###ZEILENNUMMER###" . implode(HtmlBBcodeUtils::$br_implicit . "###ZEILENNUMMER###", $x);
					}
					$out .= implode("<br>", $zeils_neu);
					$out .= $matches[4];

					return $out;
				}, $str_neu);
			} else {
				$zeils     = explode("<br>", $abs);
				$zeils_neu = array();
				foreach ($zeils as $zeile) {
					$x           = HtmlBBcodeUtils::text2zeilen($zeile, HtmlBBcodeUtils::$zeilenlaenge);
					$zeils_neu[] = "###ZEILENNUMMER###" . implode(HtmlBBcodeUtils::$br_implicit . "###ZEILENNUMMER###", $x);
				}

				$str_neu = "<div class='text'>";
				$str_neu .= implode("<br>", $zeils_neu);
				$str_neu .= "</div>";
			}

			$str_neu = preg_replace_callback("/###ZEILENNUMMER###/", function () {
				return "<span class='zeilennummer'>" . HtmlBBcodeUtils::$zeilen_counter++ . "</span>";
			}, $str_neu);

			$absaetze_html[] = $str_neu;
		}

		return array("html" => $absaetze_html, "html_plain" => $absaetze_html_plain, "bbcode" => $absaetze_bbcode);
	}

	static function wrapWithTextClass($text, $css_hack_width = 0)
	{
		if (mb_stripos($text, "<ul") === 0) $text = str_ireplace("<ul", "<ul class='text'", $text);
		if (mb_stripos($text, "<ol") === 0) $text = str_ireplace("<ol", "<ol class='text'", $text);

		if (mb_stripos($text, "<ul") !== 0 && mb_stripos($text, "<ol") !== 0 && mb_stripos($text, "<blockquote") !== 0) {
			// Testfälle: https://ltwby13-programm.antragsgruen.de/ltwby13-programm/antrag/85 Ä234: zu schmal bei 8.4
			$css  = ($css_hack_width > 0 ? "style='width: " . Ceil($css_hack_width * 8.43) . "px;'" : "");
			$text = "<div class='text' $css>" . $text . "</div>";
		}
		return $text;
	}

	/**
	 * @static
	 * @param string $text
	 * @param int $max_len
	 * @param bool $debug
	 * @param bool $nocache
	 * @return array|string[]
	 */
	static function text2zeilen($text, $max_len, $debug = false, $nocache = false)
	{

		//echo "<br><br>===<br>" . nl2br(htmlentities($text));

		$zeilen                     = array();
		$letztes_trennzeichen       = -1;
		$letztes_trennzeichen_count = 0;
		$in_html_modus              = false;
		$in_escaped_modus           = false;
		$aktuelle_zeile             = "";
		$aktuelle_zeile_count       = 0;

		if (!$nocache) {
			$cache_key = md5("text2zeilen11" . $max_len . $text);
			$cached    = Cache::getObject($cache_key);
			if (is_array($cached)) return $cached;
		}

		for ($i = 0; $i < mb_strlen($text); $i++) {
			$curr_char = mb_substr($text, $i, 1);
			if ($in_html_modus) {
				if ($curr_char == ">") $in_html_modus = false;
				$aktuelle_zeile .= $curr_char;
			} elseif ($in_escaped_modus) {
				if ($curr_char == ";") $in_escaped_modus = false;
				$aktuelle_zeile .= $curr_char;
			} else {
				$aktuelle_zeile .= $curr_char;

				if ($curr_char == "<") {
					$in_html_modus = true;
					continue;
				}
				if ($curr_char == "&") {
					$in_escaped_modus = true;
				}

				$aktuelle_zeile_count++;

				if ($debug) echo $aktuelle_zeile_count . ": " . $curr_char . "\n";

				if ($aktuelle_zeile_count > $max_len) {
					if ($debug) echo "Aktuelle Zeile: \"" . $aktuelle_zeile . "\"\n";
					if ($debug) echo "Count: \"" . $aktuelle_zeile_count . "\"\n";
					if ($debug) echo "Letztes Leerzeichen: \"" . $letztes_trennzeichen . "\"\n";

					if ($letztes_trennzeichen == -1) {
						if ($debug) echo "Umbruch forcieren\n";
						$zeilen[]             = mb_substr($aktuelle_zeile, 0, mb_strlen($aktuelle_zeile) - 1) . "-";
						$aktuelle_zeile       = $curr_char;
						$aktuelle_zeile_count = 1;
					} else {
						if ($debug) echo "Aktuelles Zeichen: \"" . mb_substr($text, $i, 1) . "\"\n";
						if (mb_substr($text, $i, 1) == " ") {
							$zeilen[] = mb_substr($aktuelle_zeile, 0, mb_strlen($aktuelle_zeile) - 1);

							$aktuelle_zeile       = "";
							$aktuelle_zeile_count = 0;
						} else {
							$ueberhang = mb_substr($aktuelle_zeile, $letztes_trennzeichen + 1);
							if ($debug) echo "Überhang: \"" . $ueberhang . "\"\n";
							$letztes_ist_leerzeichen = (mb_substr($aktuelle_zeile, $letztes_trennzeichen, 1) == " ");
							if ($debug) echo "Letztes ist Leerzeichen: " . $letztes_ist_leerzeichen . "\n";
							$zeilen[] = mb_substr($aktuelle_zeile, 0, $letztes_trennzeichen + ($letztes_ist_leerzeichen ? 0 : 1));

							$aktuelle_zeile       = $ueberhang;
							$aktuelle_zeile_count = $max_len - $letztes_trennzeichen_count + 1;
						}

						$letztes_trennzeichen       = -1;
						$letztes_trennzeichen_count = 0;
					}
					if ($debug) echo "Neue aktuelle Zeile: \"" . $aktuelle_zeile . "\"\n";
					if ($debug) echo "Count: \"" . $aktuelle_zeile_count . "\"\n\n";
				} elseif (in_array($curr_char, array(" ", "-"))) {
					$letztes_trennzeichen       = mb_strlen($aktuelle_zeile) - 1;
					$letztes_trennzeichen_count = $aktuelle_zeile_count;
				}

			}
		}
		if (mb_strlen(trim($aktuelle_zeile)) > 0) $zeilen[] = $aktuelle_zeile;

		if (!$nocache) {
			Cache::setObject($cache_key, $zeilen);
		}

		return $zeilen;
	}


	/**
	 * @static
	 * @param string $text
	 * @param bool $allow_html
	 * @return string
	 */
	static function bbcode2html($text, $allow_html = false)
	{
		$debug = false;

		/*
		$text = preg_replace_callback("/(\[quote[^\]]*\])(.*)(\[\/o?quote\])/siuU", function ($matches) {
			$first_open  = mb_stripos($matches[2], "[LIST");
			$first_close = mb_stripos($matches[2], "[/LIST]");

			if ($first_close !== false && ($first_open === false || $first_close < $first_open)) $matches[2] = trim(mb_substr($matches[2], 0, $first_close) . "\n" . mb_substr($matches[2], $first_close + 7));
			return $matches[1] . $matches[2] . $matches[3];
		}, $text);
		*/

		if ($debug) {
			//require_once("/var/www/antragsgruen-v2/vendor/mjohnson/decoda/examples/list.php");
			echo "<br>IN========<br>";
			echo CHtml::encode($text);
		}

		$text = preg_replace_callback("/(\[quote[^\]]*\])(.*)(\[\/o?quote\])/siuU", function ($matches) {
			if (mb_stripos($matches[2], "[li]") === false && mb_stripos($matches[2], "[*]") === false) return $matches[1] . $matches[2] . $matches[3];
			if (mb_stripos($matches[2], "[list]") === false) {
				return "[list]\n[*]" . $matches[2] . "\n[/list]";
			} else {
				return $matches[2];
			}
		}, $text);
		$text = preg_replace_callback("/(\[o?list[^\]]*\])(.*)(\[\/o?list\])/siuU", function ($matches) use ($debug) {
			$parts = explode("[*]", trim($matches[2]));
			$str   = $matches[1];
			foreach ($parts as $part) if ($part != "") $str .= "[LI]" . trim($part) . "[/LI]";
			$str .= $matches[3];
			return $str;
		}, $text);

		$code  = new \Decoda\Decoda();
		$code->setEscaping(!$allow_html);
		$code->addFilter(new AntraegeBBCodeFilter());
		$code->addFilter(new \Decoda\Filter\UrlFilter());

		if ($debug) {
			//require_once("/var/www/antragsgruen-v2/vendor/mjohnson/decoda/examples/list.php");
			echo "<br>IN========<br>";
			echo CHtml::encode($text);
		}

		$code->reset($text);
		$text = $code->parse();
		if ($debug) {
			echo "<br>OUT========<br>";
			echo CHtml::encode($text);
		}

		$text = str_replace("<br>\n", "<br>", $text);
		$text = str_replace("\n", "<br>", $text);
		$text = preg_replace("/<br *\/>/siu", "<br>", $text);

		$text = preg_replace("/<ul>[<br>\\n]*<li>/siu", "<ul>\n<li>", $text);
		$text = preg_replace("/<\/li>[<br>\\n]*<li>/siu", "</li>\n<li>", $text);
		$text = preg_replace("/<\/li>[<br>\\n]*<\/ul>/siu", "</li>\n</ul>", $text);

		return $text;
	}


	/**
	 * @static
	 * @param array $matches
	 * @return string
	 */
	static function convert_html2bbcode_ul($matches)
	{
		$text = "[[NEWLINE]][UL]\n";

		$x = preg_split("/<li.*>/siU", $matches[1]);
		if (count($x) > 1) for ($i = 1; $i < count($x); $i++) {
			$p   = mb_stripos($x[$i], "</li>");
			$str = ($p === false ? $x[$i] : mb_substr($x[$i], 0, $p));
			$str = preg_replace("/(\\n|\[\[NEWLINE\]\])?\[(LEFT|RIGHT|CENTER|JUSTIFY)\](.*)\[\/(LEFT|RIGHT|CENTER|JUSTIFY)\](\\n|\[\[NEWLINE\]\])?/si", "\\3", $str);
			$str = trim(str_replace("\n", "", str_replace("\r", "", $str)));
			$text .= "[LI]${str}[/LI]\n";
		}
		$text = str_replace("[LI][[NEWLINE]]", "[LI]", $text);
		$text = str_replace("[[NEWLINE]][/LI]", "[/LI]", $text);
		$text .= "[/UL][[NEWLINE]]";


		return $text;
	}

	/**
	 * @static
	 * @param array $matches
	 * @return string
	 */
	static function convert_html2bbcode_ol($matches)
	{
		$text = "[[NEWLINE]][OL]\n";

		$x = preg_split("/<li.*>/siU", $matches[1]);
		if (count($x) > 1) for ($i = 1; $i < count($x); $i++) {
			$p   = mb_stripos($x[$i], "</li>");
			$str = ($p === false ? $x[$i] : mb_substr($x[$i], 0, $p));
			$str = preg_replace("/(\\n|\[\[NEWLINE\]\])?\[(LEFT|RIGHT|CENTER|JUSTIFY)\](.*)\[\/(LEFT|RIGHT|CENTER|JUSTIFY)\](\\n|\[\[NEWLINE\]\])?/si", "\\3", $str);
			$str = trim(str_replace("\n", "", str_replace("\r", "", $str)));
			$text .= "[LI]${str}[/LI]\n";
		}
		$text = str_replace("[LI][[NEWLINE]]", "[LI]", $text);
		$text = str_replace("[[NEWLINE]][/LI]", "[/LI]", $text);
		$text .= "[/OL][[NEWLINE]]";


		return $text;
	}

	/**
	 * @static
	 * @param string $text
	 * @param string $operator
	 * @param string $value
	 * @return string
	 */
	private static function convert_html2bbcode_wraptext_by_css_inner($text, $operator, $value)
	{
		switch ($operator) {
			case "text-align":
				switch ($value) {
					case "left":
						$text = "[LEFT]${text}[/LEFT]";
						break;
					case "right":
						$text = "[RIGHT]${text}[/RIGHT]";
						break;
					case "center":
						$text = "[CENTER]${text}[/CENTER]";
						break;
					case "justify":
						$text = "[JUSTIFY]${text}[/JUSTIFY]";
						break;
				}
				break;
			case "font-weight":
				switch ($value) {
					case "bold":
						$text = "[B]${text}[/B]";
						break;
				}
				break;
			case "font-style":
				switch ($value) {
					case "italic":
						$text = "[I]${text}[/I]";
						break;
				}
				break;
			case "text-decoration":
				switch ($value) {
					case "underline":
						$text = "[U]${text}[/U]";
						break;
				}
				break;
			case "margin-left":
				if ($value > 10) $text = "[QUOTE]${text}[/QUOTE]";
				break;
				break;
		}
		return $text;
	}

	/**
	 * @static
	 * @param string $text
	 * @param string $unclean_attribute
	 * @return string
	 */
	private static function convert_html2bbcode_wraptext_by_css_outer($text, $unclean_attribute)
	{
		$unclean_attribute = mb_strtolower($unclean_attribute);
		if (preg_match("/style *= *[\"'](.*)[\"']/sU", $unclean_attribute, $styleangabe)) {
			$cssproperties = explode(";", $styleangabe[1]);
			foreach ($cssproperties as $cssausdruck) {
				$x = explode(":", $cssausdruck);
				if (count($x) == 2) {
					$operator = trim($x[0]);
					$value    = trim($x[1]);
					$text     = self::convert_html2bbcode_wraptext_by_css_inner($text, $operator, $value);
				}
			}
		}
		if (preg_match("/align *= *[\"'](.*)[\"']/sU", $unclean_attribute, $matches)) {
			$text = self::convert_html2bbcode_wraptext_by_css_inner($text, "text-align", trim($matches[1]));
		}
		return $text;
	}

	/**
	 * @static
	 * @param array $matches
	 * @return string
	 */
	private static function convert_html2bbcode_div($matches)
	{
		$text = "[[NEWLINE]]";
		$t    = trim($matches[2]);
		$text .= self::convert_html2bbcode_wraptext_by_css_outer($t, $matches[1]);
		$text .= "[[NEWLINE]]";
		return $text;
	}

	/**
	 * @static
	 * @param string $text
	 */
	static function debug_vardump_lines($text)
	{
		$x = explode("\n", $text);
		foreach ($x as $y) echo "\"$y\"\n";
	}


	/**
	 * @static
	 * @param string $html
	 * @param array $optionen
	 * @return string
	 */
	static function html2bbcode($html, $optionen = array())
	{
		$html = str_replace("\n", " ", $html);
		$html = str_replace("\r", " ", $html);
		$html = str_replace("\t", " ", $html);
		$html = str_replace(chr(13), "", $html);
		$html = str_replace(" />", ">", $html);

		$text = $html;

		$text = preg_replace("/<!\-\-(.*)\-\->/siU", "", $text);
		$text = preg_replace("/<br> +/si", "<br>", $text);

		$text = preg_replace("/<img.*>/siU", "", $text);
		$text = preg_replace_callback(
			"/<a [^>]*href=[\"'](.*)[\"'].*>(.*)<\/a>/siU", function ($matches) {
			return "[URL=" . $matches[1] . "]" . $matches[2] . "[/URL]";
		}, $text);
		$text = preg_replace("/<a [^>]*>(.*)<\/a>/siU", "\\1", $text);

		$text = preg_replace_callback(
			"/<i( [^>]*)?>(.*)<\/i>/siU", function ($matches) {
			return "[I]" . $matches[2] . "[/I]";
		}, $text);
		$text = preg_replace_callback(
			"/<em( [^>]*)?>(.*)<\/em>/siU", function ($matches) {
			return "[I]" . $matches[2] . "[/I]";
		}, $text);

		$text = preg_replace_callback(
			"/<b( [^>]*)?>(.*)<\/b>/siU", function ($matches) {
			return "[B]" . $matches[2] . "[/B]";
		}, $text);
		$text = preg_replace_callback(
			"/<strong( [^>]*)?>(.*)<\/strong>/siU", function ($matches) {
			return "[B]" . $matches[2] . "[/B]";
		}, $text);

		$text = preg_replace_callback(
			"/<u( [^>]*)?>(.*)<\/u>/siU", function ($matches) {
			return "[U]" . $matches[2] . "[/U]";
		}, $text);

		$text = preg_replace_callback(
			"/<s( [^>]*)?>(.*)<\/s>/siU", function ($matches) {
			return "[S]" . $matches[2] . "[/S]";
		}, $text);
		$text = preg_replace_callback(
			"/<strike( [^>]*)?>(.*)<\/strike>/siU", function ($matches) {
			return "[S]" . $matches[2] . "[/S]";
		}, $text);

		$text = preg_replace_callback(
			"/<h([1-6])( [^>]*)?>(.*)<\/h[1-6]>/siU", function ($matches) {
			return "[h" . $matches[1] . "]" . $matches[3] . "[/h" . $matches[1] . "]";
		}, $text);

		$text_old = "";
		while ($text != $text_old) {
			$text_old = $text;
			$text     = preg_replace_callback("/<div( [^>]*)?>(.*)<\/div>/siU", "convert_html2bbcode_div", $text);
		}
		$text_old = "";
		while ($text != $text_old) {
			$text_old = $text;
			$text     = preg_replace_callback(
				"/<span( [^>]*)?>(.*)<\/span>/siU", function ($matches) {
				return self::convert_html2bbcode_wraptext_by_css_outer($matches[2], $matches[1]);
			}, $text);
		}
		$text_old = "";
		while ($text != $text_old) {
			$text_old = $text;
			$text     = preg_replace_callback("/<font( [^>]*)?>(.*)<\/font>/siU", "convert_html2bbcode_font", $text);
		}
		$text_old = "";
		while ($text != $text_old) {
			$text_old = $text;
			$text     = preg_replace_callback("/<p( [^>]*)?>(.*)<\/p>/siU", "convert_html2bbcode_div", $text);
		}

		$text = preg_replace_callback("/<ul.*>(.*)<\/ul>/siU", "convert_html2bbcode_ul", $text);
		$text = preg_replace_callback("/<ol.*>(.*)<\/ol>/siU", "convert_html2bbcode_ol", $text);

		$text = preg_replace("/<style.*>.*<\/style>/siU", "", $text);
		$text = preg_replace("/<script.*>.*<\/script>/siU", "", $text);

		$text = preg_replace("/\[\[NEWLINE\]\]<br.*\/?>/siU", "<br>", $text);
		$text = preg_replace("/<br.*\/?>\\n?/siU", "\n", $text);
		//$text = strip_tags($text);

		$text = preg_replace("/\[\[NEWLINE\]\] +\[\[NEWLINE\]\]/si", "[[NEWLINE]][[NEWLINE]]", $text);

		$text = preg_replace("/\\n?(\[\[NEWLINE\]\])+ */si", "\n", $text); // Greedy!

		preg_match("/(\[\/(LEFT|RIGHT|CENTER|JUSTIFY)\])(\\n{2,})(\[(LEFT|RIGHT|CENTER|JUSTIFY)\])/siU", $text, $matches);

		$text = preg_replace("/(\[\/(LEFT|RIGHT|CENTER|JUSTIFY)\])(\\n{2,})(\[(LEFT|RIGHT|CENTER|JUSTIFY)\])/siU", "\\1\\3\n\\4", $text);

		$text = html_entity_decode($text, ENT_COMPAT, "UTF-8");

		return trim($text);
	}


	/**
	 * @static
	 * @param string $mysqldate
	 * @return string
	 */
	static function formatMysqlDate($mysqldate)
	{
		if (strlen($mysqldate) == 0) return "-";
		if (substr($mysqldate, 0, 10) == date("Y-m-d")) return "Heute";
		if (substr($mysqldate, 0, 10) == date("Y-m-d" - 3600 * 24)) return "Gestern";
		$date = explode("-", substr($mysqldate, 0, 10));
		return sprintf("%02d.%02d.%04d", $date[2], $date[1], $date[0]);
	}

	/**
	 * @static
	 * @param string $mysqlDate
	 * @return string
	 */
	static function formatMysqlDateTime($mysqlDate)
	{
		if (strlen($mysqlDate) == 0) return "-";
		return self::formatMysqlDate($mysqlDate) . ", " . substr($mysqlDate, 11, 5) . " Uhr";
	}


	static function removeBBCode($text)
	{
		return str_ireplace(array("[b]", "[/b]", "[quote]", "[/quote]", "[*]", "[i]", "[/i]", "[list]", "[/list]", "[u]", "[/u]", "[s]", "[/s]"), "", $text);
	}


}