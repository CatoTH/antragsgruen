<?php

require_once("HTML/BBCodeParser.php");


class HtmlBBcodeUtils
{

	public static $zeilen_counter = 0;

	/**
	 *
	 */
	public static function initZeilenCounter()
	{
		self::$zeilen_counter = 1;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	static function bbcode_normalize($text)
	{
		$text = preg_replace("/\[(\/?)list([^\]]*)\]/siu", "[\\1LIST\\2]", $text);
		$text = preg_replace("/\[(\/?)quote\]/siu", "[\\1QUOTE]", $text);
		$text = preg_replace("/\[(\/?)b\]/siu", "[\\1B]", $text);
		$text = preg_replace("/\[(\/?)i\]/siu", "[\\1I]", $text);
		$text = preg_replace("/\[(\/?)u\]/siu", "[\\1U]", $text);
		$text = preg_replace("/\[(\/?)url([^\]]*)\]/siu", "[\\1URL\\2]", $text);
		
		$text = str_replace(chr(194).chr(160), " ", $text);
		
		$x = explode("\n", $text);
		foreach ($x as $i=>$zeile) {
			$x[$i] = trim($zeile);
		}
		return implode("\n", $x);
	}

	/**
	 * @static
	 * @param string $text
	 * @return array|string[]
	 */
	static function bbcode2zeilen_absaetze($text)
	{
		$text = str_replace("\r", "", $text);
		$text = preg_replace("/\[list(.*)\[\/list\]/siU", "\n\n[LIST\\1[/LIST]\n\n", $text);
		$text = preg_replace("/\[quote(.*)\[\/quote\]/siU", "\n\n[QUOTE\\1[/QUOTE]\n\n", $text);
		$text = preg_replace("/\\n( *\\n)+/", "\n\n", $text);

		$text   = preg_replace("/[\\n ]+\[\*\]/siU", "\n[*]", $text);
		$text   = trim($text, " \n");
		$x      = explode("\n\n", $text);
		$return = array();

		foreach ($x as $y) {
			if (mb_stripos($y, "[list") === 0 || mb_stripos($y, "[ulist") === 0 || mb_stripos($y, "[quote") === 0) {
				$str_neu = preg_replace_callback("/(\[quote[^\]]*\])(.*)(\[\/quote\])/siU", function ($matches) {
					$out = $matches[1];

					$zeils     = explode("\n", $matches[2]);
					$zeils_neu = array();
					foreach ($zeils as $zeile) {
						$x           = HtmlBBcodeUtils::text2zeilen($zeile, 70);
						$zeils_neu[] = "###ZEILENNUMMER###" . implode("\n###ZEILENNUMMER###", $x);
					}
					$out .= implode("\n", $zeils_neu);
					$out .= $matches[3];

					return $out;
				}, $y);
				$str_neu = preg_replace_callback("/(\[list[^\]]*\])(.*)(\[\/list\])/siuU", function ($matches) {
					$out = $matches[1];
					$x = explode("[*]", $matches[2]);
					if (count($x) > 1) for ($i = 1; $i < count($x); $i++) {
						$zeils = explode("\n", trim($x[$i]));
						$zeils_neu = array();
						foreach ($zeils as $zeile) {
							$z           = HtmlBBcodeUtils::text2zeilen($zeile, 70);
							$zeils_neu[] = "###ZEILENNUMMER###" . implode("\n###ZEILENNUMMER###", $z);
						}
						$out .= implode("[*]", $zeils_neu);
						$out .= $matches[3];
					}
					return $out;
				}, $str_neu);

			} else {
				$zeils     = explode("\n", $y);
				$zeils_neu = array();
				foreach ($zeils as $zeile) {
					$x           = HtmlBBcodeUtils::text2zeilen($zeile, 80);
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
	 * @static
	 * @param string $text
	 * @return array|string[]
	 */
	static function bbcode2html_absaetze($text)
	{
		$text = str_replace("\r", "", $text);
		$text = preg_replace("/\[list(.*)\[\/list\]/siU", "\n\n[LIST\\1[/LIST]\n\n", $text);
		$text = preg_replace("/\[quote(.*)\[\/quote\]/siU", "\n\n[QUOTE\\1[/QUOTE]\n\n", $text);
		$text = preg_replace("/\\n( *\\n)+/", "\n\n", $text);

		$text                = preg_replace("/[\\n ]+\[\*\]/siU", "\n[*]", $text);
		$text                = trim($text, " \n");
		$x                   = explode("\n\n", $text);
		$absaetze_html       = array();
		$absaetze_bbcode     = array();
		$absaetze_html_plain = array();

		foreach ($x as $y) {
			$absaetze_bbcode[]     = $y;
			$abs                   = HtmlBBcodeUtils::bbcode2html($y);
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
						$x           = HtmlBBcodeUtils::text2zeilen($zeile, 70);
						$zeils_neu[] = "###ZEILENNUMMER###" . implode("<br>###ZEILENNUMMER###", $x);
					}
					$out .= implode("<br>", $zeils_neu);
					$out .= $matches[4];

					return $out;
				}, $str_neu);
			} else {
				$zeils     = explode("<br>", $abs);
				$zeils_neu = array();
				foreach ($zeils as $zeile) {
					$x           = HtmlBBcodeUtils::text2zeilen($zeile, 80);
					$zeils_neu[] = "###ZEILENNUMMER###" . implode("<br>###ZEILENNUMMER###", $x);
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


	static function bbcode2html_absaetze2_block($text, $maxlen)
	{
		echo $text . "\n\n";
		preg_match("/(<ul[^>]*>)(.*)<\/ul>/siu", $text, $matches, PREG_OFFSET_CAPTURE);
		var_dump($matches);
		preg_match("/(<ol[^>]*>)(.*)<\/ol>/siu", $text, $matches, PREG_OFFSET_CAPTURE);
		return $text;
	}

	/**
	 * @static
	 * @param string $text
	 * @return array|string[]
	 */
	static function bbcode2html_absaetze2($text)
	{
		$text = str_replace("\r", "", $text);
		$text = preg_replace("/\\n( *\\n)+/", "\n\n", $text);
		//$text                      = preg_replace("/[ \\n]*(\[\/?(BLOCKQUOTE|LIST)[^\]]*\][ \\n]*/siu", "\\1", $text);
		$text = preg_replace("/[\\n ]+\[\*\]/siU", "\n[*]", $text);
		$text = trim($text, " \n");
		echo $text . "\n----------------\n";
		$x                   = explode("\n\n", $text);
		$absaetze_html       = array();
		$absaetze_bbcode     = array();
		$absaetze_html_plain = array();

		foreach ($x as $y) {
			$absaetze_bbcode[] = $y;
			echo $y . "\n=================\n";
			$abs                   = HtmlBBcodeUtils::bbcode2html($y);
			$absaetze_html_plain[] = $abs;
			echo $abs . "\n=================\n";

			$str_neu = self::bbcode2html_absaetze2_block($abs, 80);

			$zeils     = explode("<br>", $abs);
			$zeils_neu = array();
			foreach ($zeils as $zeile) {
				$zeile = preg_replace("/<ul([^>]*)>/siu", "<ul\\1 class='text'>", $zeile);
				$zeile = preg_replace("/<ol([^>]*)>/siu", "<ol\\1 class='text'>", $zeile);
				$zeile = preg_replace("/<blockquote([^>]*)>/siu", "<blockquote\\1 class='text'>", $zeile);

				//$zeile = preg_replace("/(<ul([^>]*)>|<ol([^>]*)>|<blockquote([^>]*)>| )*(.*)/siu", "\\1###ZEILENNUMMER###"$zeile);
				/*
					$x           = HtmlBBcodeUtils::text2zeilen($zeile, 80);
				$zeils_neu[] = "###ZEILENNUMMER###" . implode("<br>###ZEILENNUMMER###", $x);
				*/
			}

			$str_neu = preg_replace_callback("/###ZEILENNUMMER###/", function () {
				return "<span class='zeilennummer'>" . HtmlBBcodeUtils::$zeilen_counter++ . "</span>";
			}, $str_neu);


			$absaetze_html[] = $str_neu;
		}

		return array("html" => $absaetze_html, "html_plain" => $absaetze_html_plain, "bbcode" => $absaetze_bbcode);
	}

	/**
	 * @static
	 * @param string $text
	 * @param int $max_len
	 * @param bool $debug
	 * @return array|string[]
	 */
	static function text2zeilen($text, $max_len = 80, $debug = false)
	{

		$zeilen                    = array();
		$letztes_leerzeichen       = -1;
		$letztes_leerzeichen_count = 0;
		$in_html_modus             = false;
		$aktuelle_zeile            = "";
		$aktuelle_zeile_count      = 0;

		$cache_key = md5("text2zeilen" . $max_len . $text);
		$cached = Cache::getObject($cache_key);
		if (is_array($cached)) return $cached;

		for ($i = 0; $i < mb_strlen($text); $i++) {
			$curr_char = mb_substr($text, $i, 1);
			if ($in_html_modus) {
				if ($curr_char == ">") $in_html_modus = false;
				$aktuelle_zeile .= $curr_char;
			} else {
				$aktuelle_zeile .= $curr_char;

				if ($curr_char == "<") {
					$in_html_modus = true;
					continue;
				}

				$aktuelle_zeile_count++;
				if (in_array($curr_char, array(" ", "-"))) {
					$letztes_leerzeichen       = mb_strlen($aktuelle_zeile) - 1;
					$letztes_leerzeichen_count = $aktuelle_zeile_count;
				}
				if ($aktuelle_zeile_count == $max_len) {
					if ($debug) echo "Aktuelle Zeile: \"" . htmlentities($aktuelle_zeile, ENT_COMPAT, "UTF-8") . "\"<br>";
					if ($debug) echo "Count: \"" . $aktuelle_zeile_count . "\"<br>";
					if ($debug) echo "Letztes Leerzeichen: \"" . $letztes_leerzeichen . "\"<br>";

					if ($letztes_leerzeichen == -1) {
						if ($debug) echo "Umbruch forcieren<br>";
						$zeilen[]             = mb_substr($aktuelle_zeile, 0, mb_strlen($aktuelle_zeile) - 1) . "-";
						$aktuelle_zeile       = $curr_char;
						$aktuelle_zeile_count = 1;
					} else {
						$ueberhang = mb_substr($aktuelle_zeile, $letztes_leerzeichen + 1);
						if ($debug) echo "Überhang: \"" . htmlentities($ueberhang, ENT_COMPAT, "UTF-8") . "\"<br>";
						$zeilen[] = mb_substr($aktuelle_zeile, 0, $letztes_leerzeichen + 1); // Leerzeichen bleiben am Ende erhalten; wg. Bindestrichen nötig

						$aktuelle_zeile            = $ueberhang;
						$aktuelle_zeile_count      = $max_len - $letztes_leerzeichen_count;
						$letztes_leerzeichen       = -1;
						$letztes_leerzeichen_count = 0;
					}
					if ($debug) echo "Neue aktuelle Zeile: \"" . htmlentities($aktuelle_zeile, ENT_COMPAT, "UTF-8") . "\"<br>";
					if ($debug) echo "Count: \"" . $aktuelle_zeile_count . "\"<br><br>";
				}
			}
		}
		if (mb_strlen(trim($aktuelle_zeile)) > 0) $zeilen[] = $aktuelle_zeile;

		Cache::setObject($cache_key, $zeilen);

		return $zeilen;
	}


	/**
	 * @static
	 * @param string $text
	 * @return string
	 */
	static function bbcode2html($text)
	{

		//$text = preg_replace("/([^\n\r ?&\[\]\"]{80})/iu", "\\1[[CHAR=&#8203;]]", $text);


		$GLOBALS["bb_tags"] = array();
		global $bb_tags;
		$bb_tags[] = "B";
		$bb_tags[] = "I";
		$bb_tags[] = "U";
		$bb_tags[] = "S";
		$bb_tags[] = "URL";
		$bb_tags[] = "EMAIL";

		$bb_tags[] = "UL";
		$bb_tags[] = "OL";
		$bb_tags[] = "QUOTE";
		$text      = preg_replace("/\[\/li\][\\n\\r ]+\[li\]/siU", "[/li][li]", $text);
		$text      = preg_replace("/\[ul\][\\n\\r ]+\[li\]/siU", "[ul][li]", $text);
		$text      = preg_replace("/\[\/li\][\\n\\r ]+\[\/ul\]/siU", "[/li][/ul]", $text);
		$text      = preg_replace("/\[ol\][\\n\\r ]+\[li\]/siU", "[ol][li]", $text);
		$text      = preg_replace("/\[\/li\][\\n\\r ]+\[\/ol\]/siU", "[/li][/ol]", $text);

		$text      = str_replace("[/QUOTE]<br>", "[/QUOTE]", $text);
		$text      = str_replace("[/quote]<br>", "[/quote]", $text);
		$text      = str_ireplace(array("\n[right]", "[/right]\n"), array("[right]", "[/right]"), $text);
		$text      = str_ireplace(array("\n[left]", "[/left]\n"), array("[left]", "[/left]"), $text);
		$bb_tags[] = "RIGHT";
		$bb_tags[] = "LEFT";
		$text      = str_ireplace(array("\n[center]", "[/center]\n"), array("[center]", "[/center]"), $text);
		$bb_tags[] = "CENTER";
		$text      = str_ireplace(array("\n[justify]", "[/justify]\n"), array("[justify]", "[/justify]"), $text);
		$bb_tags[] = "JUSTIFY";

		$text = str_replace("[[CHAR=&amp;#8203;]]", "&#8203;", $text);


		if (count($bb_tags) > 0) {
			$parser = new HTML_BBCodeParser(array("filters" => "Antraege", "quotestyle" => "double"));
			$parser->setText($text);
			$parser->parse();
			$text = $parser->getParsed();
		}

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


}