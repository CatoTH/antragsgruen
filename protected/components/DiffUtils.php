<?php

require_once 'Horde/String.php';
require_once 'Horde/Text/Diff.php';
require_once 'Horde/Text/Diff/Renderer.php';
require_once 'Horde/Text/Diff/Renderer/Unified.php';
require_once 'Horde/Text/Diff/Renderer/Inline.php';
require_once 'Horde/Text/Diff/Engine/Native.php';
require_once 'Horde/Text/Diff/Engine/Xdiff.php';
require_once 'Horde/Text/Diff/Op/Base.php';
require_once 'Horde/Text/Diff/Op/Add.php';
require_once 'Horde/Text/Diff/Op/Copy.php';
require_once 'Horde/Text/Diff/Op/Change.php';
require_once 'Horde/Text/Diff/Op/Delete.php';

class Horde_Text_Diff_Renderer_Inline_Antrag extends Horde_Text_Diff_Renderer_Inline
{
	protected $_leading_context_lines = 1;
	protected $_trailing_context_lines = 1;
}
class Horde_Text_Diff_Renderer_Inline_Antrag15 extends Horde_Text_Diff_Renderer_Inline
{

	/**
	 * Renders a diff.
	 *
	 * @param Horde_Text_Diff $diff  A Horde_Text_Diff object.
	 *
	 * @return string  The formatted output.
	 */
	public function render($diff)
	{
		$xi = $yi = 1;
		$block = false;
		$context = array();

		$nlead = 2;
		$ntrail = 2;

		$output = $this->_startDiff();

		$diffs = $diff->getDiff();
		foreach ($diffs as $i => $edit) {
			/* If these are unchanged (copied) lines, and we want to keep
			 * leading or trailing context lines, extract them from the copy
			 * block. */
			if ($edit instanceof Horde_Text_Diff_Op_Copy) {
				/* Do we have any diff blocks yet? */
				if (is_array($block)) {
					/* How many lines to keep as context from the copy
					 * block. */
					$keep = $i == count($diffs) - 1 ? $ntrail : $nlead + $ntrail;
					if (count($edit->orig) <= $keep) {
						/* We have less lines in the block than we want for
						 * context => keep the whole block. */
						$block[] = $edit;
					} else {
						if ($ntrail) {
							/* Create a new block with as many lines as we need
							 * for the trailing context. */
							$context = array_slice($edit->orig, 0, $ntrail);
							$block[] = new Horde_Text_Diff_Op_Copy($context);
						}
						/* @todo */
						$output .= $this->_block($x0, $ntrail + $xi - $x0,
							$y0, $ntrail + $yi - $y0,
							$block);
						$block = false;
					}
				}
				/* Keep the copy block as the context for the next block. */
				$context = $edit->orig;
			} else {
				/* Don't we have any diff blocks yet? */
				if (!is_array($block)) {
					/* Extract context lines from the preceding copy block. */
					$context = array_slice($context, count($context) - $nlead);
					$x0 = $xi - count($context);
					$y0 = $yi - count($context);
					$block = array();
					if ($context) {
						$block[] = new Horde_Text_Diff_Op_Copy($context);
					}
				}
				$block[] = $edit;
			}

			if ($edit->orig) {
				$xi += count($edit->orig);
			}
			if ($edit->final) {
				$yi += count($edit->final);
			}
		}

		if (is_array($block)) {
			$output .= $this->_block($x0, $xi - $x0,
				$y0, $yi - $y0,
				$block);
		}

		return $output . $this->_endDiff();
	}

	protected function _block($xbeg, $xlen, $ybeg, $ylen, &$edits)
	{
		$output = "";

		foreach ($edits as $edit) {
			switch (get_class($edit)) {
				case 'Horde_Text_Diff_Op_Add':
					$output .= "Neu hinzufügen:\n" . $this->_added($edit->final);
					break;

				case 'Horde_Text_Diff_Op_Delete':
					$output .= "Streichen:\n" . $this->_deleted($edit->orig);
					break;

				case 'Horde_Text_Diff_Op_Change':
					$output .= $this->_changed($edit->orig, $edit->final);
					break;
			}
		}

		return $output;
	}

}

class Horde_Text_Diff_Renderer_Inline_Antrag1000 extends Horde_Text_Diff_Renderer_Inline
{
	protected $_leading_context_lines = 1000;
	protected $_trailing_context_lines = 1000;
}

/**
 *
 */
class DiffUtils
{

	/**
	 * @static
	 * @param Horde_Text_Diff $diff
	 * @param int $first_line_no
	 * @return string
	 */
	public static function diff2text($diff, $first_line_no = 1)
	{
		$diff_text2 = "";
		$edits      = $diff->getDiff();
		$line       = $first_line_no - 1;

		foreach ($edits as $edit) {
			if (get_class($edit) == "Horde_Text_Diff_Op_Add") {
				$final = implode("\n", $edit->final);
				if (trim($final, " \t\n\r") != "") {
					if (mb_strpos($final, "#ZEILE#") === 0) {
						$diff_text2 .= "Nach Zeile " . $line . " einfügen: [QUOTE]" . $final . "[/QUOTE]\n\n";
					} else {
						$diff_text2 .= "Folgenden Absatz einfügen: [QUOTE]" . $final . "[/QUOTE]\n\n";
					}
				}
			}
			if (get_class($edit) == "Horde_Text_Diff_Op_Delete") {
				$orig = implode("\n", $edit->orig);
				if (trim($orig, " \t\n\r") != "") {
					$zeilen = substr_count($orig, "#ZEILE#");
					if (mb_strpos($orig, "#ZEILE#") === 0) {
						$diff_text2 .= "Streiche Zeile " . ($line + 1);
						if ($zeilen > 1) $diff_text2 .= " bis " . ($line + $zeilen);
						$diff_text2 .= ": [QUOTE]" . $orig . "[/QUOTE]\n\n";
					}
				} else {
					$diff_text2 .= "Folgenden Absatz löschen: [QUOTE]" . $orig . "[/QUOTE]\n\n";
				}
			}
			if (get_class($edit) == "Horde_Text_Diff_Op_Change") {
				$orig  = implode("\n", $edit->orig);
				$final = implode("\n", $edit->final);

				if (trim($orig, " \t\n\r") != "" || trim($final, " \t\n\r") != "") {
					$inab = (substr_count($orig, "#ZEILE#") > 1 ? "ab" : "in");
					$diff_text2 .= "Ersetze $inab Zeile " . ($line + 1) . ":\n[QUOTE]" . $orig . "[/QUOTE]durch:[QUOTE]" . $final . "[/QUOTE]\n\n";
				}
			}

			if (is_array($edit->orig)) {
				$line += substr_count(implode("\n", $edit->orig), "#ZEILE#");
			}
		}

		$diff_text2 = str_replace("\n#ZEILE#", " ", $diff_text2);
		$diff_text2 = str_replace("#ZEILE#", "", $diff_text2);
		$diff_text2 = str_replace("#ABSATZ#", "", $diff_text2);

		return $diff_text2;
	}

	/**
	 * @static
	 * @param Horde_Text_Diff $diff
	 * @param int $first_line_no
	 * @return int
	 */
	public static function getFistDiffLine($diff, $first_line_no = 1)
	{
		$edits      = $diff->getDiff();
		$line       = $first_line_no;

		foreach ($edits as $edit) {
			if (get_class($edit) == "Horde_Text_Diff_Op_Add") {
				return $line + 1;
			}
			if (get_class($edit) == "Horde_Text_Diff_Op_Delete") {
				return $line;
			}
			if (get_class($edit) == "Horde_Text_Diff_Op_Change") {
				return $line;
			}
			if (is_array($edit->orig)) {
				$line += substr_count(implode("\n", $edit->orig), "#ZEILE#");
			}
		}

		return 0;
	}

	/**
	 * @param string $string1
	 * @param string $string2
	 * @param int $zeilenlaenge
	 * @return Horde_Text_Diff
	 */
	public static function getTextDiffMitZeilennummern($string1 = "", $string2 = "", $zeilenlaenge)
	{
		HtmlBBcodeUtils::initZeilenCounter();
		$string1 = trim(static::bbNormalizeForDiff($string1));
		$arr1  = HtmlBBcodeUtils::bbcode2zeilen_absaetze($string1, $zeilenlaenge);
		$text1 = implode("\n#ABSATZ#\n", $arr1);

		HtmlBBcodeUtils::initZeilenCounter();
		$string2 = trim(static::bbNormalizeForDiff($string2));
		$arr2  = HtmlBBcodeUtils::bbcode2zeilen_absaetze($string2, $zeilenlaenge);
		$text2 = implode("\n#ABSATZ#\n", $arr2);

		$diff = new Horde_Text_Diff('native', array(explode("\n", $text1), explode("\n", $text2)));
		return $diff;
	}


	/**
	 * @static
	 * @param string $string1
	 * @param string $string2
	 * @return Horde_Text_Diff
	 */
	public static function getTextDiff($string1 = "", $string2 = "")
	{
		$diff = new Horde_Text_Diff('native', array(explode("\n", $string1), explode("\n", $string2)));
		return $diff;
	}

	/**
	 * @static
	 * @param Horde_Text_Diff $diff
	 * @param bool $empty_comment
	 * @return string
	 */
	public static function renderDiff($diff, $empty_comment = false)
	{
		$renderer  = new Horde_Text_Diff_Renderer_Inline_Antrag();
		$diff_text = $renderer->render($diff);
		if ($diff_text == "" && $empty_comment) $diff_text = "<em>keine Änderung</em>";
		return $diff_text;
	}

	/**
	 * @static
	 * @param Horde_Text_Diff $diff
	 * @return string
	 */
	public static function renderAbsatzDiff($diff)
	{
		$renderer  = new Horde_Text_Diff_Renderer_Inline_Antrag1000();
		$diff_text = $renderer->render($diff);
		return $diff_text;
	}


	/**
	 * @static
	 * @param string $text
	 * @return string
	 */
	private static function bbNormalizeForDiff($text)
	{
		$text = str_replace("\r", "", $text);
		$text = str_replace(chr(194) . chr(160), " ", $text);
		$text = str_replace(chr(13), "", $text);
		$text = preg_replace("/ {2,}/siu", " ", $text);
		$text = trim($text);
		$text = preg_replace_callback("/(\[\/?(?:b|i|u|s|list|ulist|quote))([^a-z])/siu", function ($matches) {
			return mb_strtoupper($matches[1]) . $matches[2];
		}, $text);
		$text = preg_replace("/(\[list[^\]]*\])\\n*\[/siu", "\\1\n[", $text);
		$text = preg_replace("/([^\\n])\[\/list\]/siu", "\\1\n[/LIST]", $text);
		$text = preg_replace("/\n*\[\*/siu", "\n[*", $text);
		$text = str_replace("\r", "", $text);
		$text = str_replace(chr(13), "", $text);

		return $text;
	}

	/**
	 * @static
	 * @param string $text
	 * @return string
	 */
	private static function htmlNormalizeForDiff($text)
	{
		$text = str_replace("\r", "", $text);
		$text = str_replace(chr(13), "", $text);
		$text = preg_replace("/<\/li>[ \\n]*<\/ol>/siu", "</li>\n</ol>", $text);
		$text = preg_replace("/<\/li>[ \\n]*<\/ul>/siu", "</li>\n</ul>", $text);
		return $text;
	}


	public static $ins_mode_active = false;
	public static $del_mode_active = false;

	/**
	 * @static
	 * @param string $text_alt
	 * @param string $text_neu
	 * @param bool $compact
	 * @param int $css_width_hack
	 * @param string $pre_str_html
	 * @param bool $debug
	 * @return string
	 */
	public static function renderBBCodeDiff2HTML($text_alt, $text_neu, $compact = false, $css_width_hack = 0, $pre_str_html = "", $debug = false)
	{
		$text_alt = static::bbNormalizeForDiff($text_alt);
		$text_neu = static::bbNormalizeForDiff($text_neu);

		$diff   = DiffUtils::getTextDiff($text_alt, $text_neu);
		if ($compact) {
			$renderer  = new Horde_Text_Diff_Renderer_Inline_Antrag15();
			$absatz = $renderer->render($diff);
		} else {
			$absatz = DiffUtils::renderAbsatzDiff($diff);
		}

		if ($debug) {
			echo "\n\n============== Nach DIFF ===============\n\n";
			var_dump($absatz);
		}

		$split_lists = function($matches) use ($debug) {
			$lis = explode("[*]", $matches["inhalt"]);
			if (count($lis) == 1) return $matches[0];

			$output = "";
			for ($i = 0; $i < count($lis); $i++) {
				if ($i == 0) {
					if (trim($lis[$i]) == "") $output .= $lis[$i];
					else $output .= $matches["anfang"] . $lis[$i] . $matches["ende"];
				} elseif ($i == (count($lis) - 1)) {
					if (trim($lis[$i]) == "") $output .= "[*]" . $lis[$i];
					else $output .= "[*]" . $matches["anfang"] . $lis[$i] . $matches["ende"];
				} else {
					$output .= "[*]" . $matches["anfang"] . $lis[$i] . $matches["ende"];
				}
			}

			if ($debug) {
				echo "-----------------\n";
				var_dump($matches);
				var_dump($lis);
				var_dump($output);
			}

			return $output;
		};

		$absatz = preg_replace_callback("/(?<anfang><del>)(?<inhalt>.*)(?<ende><\/del>)/siU", $split_lists, $absatz);
		$absatz = preg_replace_callback("/(?<anfang><ins>)(?<inhalt>.*)(?<ende><\/ins>)/siU", $split_lists, $absatz);

		$diffstr = HtmlBBcodeUtils::bbcode2html($absatz);

		$diffstr = str_ireplace(
			array("&lt;ins&gt;", "&lt;/ins&gt;", "&lt;del&gt;", "&lt;/del&gt;"),
			array("<ins>", "</ins>", "<del>", "</del>"),
			$diffstr);

		if ($debug) {
			echo "\n\n============== In HTML ===============\n\n";
			var_dump($diffstr);
		}

		static::$ins_mode_active = false;
		static::$del_mode_active = false;
		$diffstr                 = preg_replace_callback("/(<li>)(.*)(<\/li>)/siuU", function ($matches) {
			$pos_del_open  = mb_stripos($matches[2], "<del>");
			$pos_del_close = mb_stripos($matches[2], "</del>");
			$pos_ins_open  = mb_stripos($matches[2], "<ins>");
			$pos_ins_close = mb_stripos($matches[2], "</ins>");
			$middle        = $matches[2];
			if ($pos_del_close !== false && ($pos_del_open === false || $pos_del_open > $pos_del_close)) {
				$middle                  = "<del>" . $middle;
				static::$del_mode_active = false;
			}
			if ($pos_del_open !== false && ($pos_del_close === false || $pos_del_open > $pos_del_close)) {
				$middle .= "</del>";
				static::$del_mode_active = true;
			}

			if ($pos_del_close === false && $pos_del_open === false && static::$del_mode_active) $middle = "<del>$middle</del>";


			if ($pos_ins_close !== false && ($pos_ins_open === false || $pos_ins_open > $pos_ins_close)) {
				$middle                  = "<ins>" . $middle;
				static::$ins_mode_active = false;
			}
			if ($pos_ins_open !== false && ($pos_ins_close === false || $pos_ins_open > $pos_ins_close)) {
				$middle .= "</ins>";
				static::$ins_mode_active = true;
			}

			if ($pos_ins_close === false && $pos_ins_open === false && static::$ins_mode_active) $middle = "<ins>$middle</ins>";

			return $matches[1] . $middle . $matches[3];
		}, $diffstr);

		if ($diffstr == "") $diffstr = HtmlBBcodeUtils::bbcode2html($text_alt);

		$diffstr = HtmlBBcodeUtils::wrapWithTextClass($pre_str_html . $diffstr, $css_width_hack);
		return $diffstr;
	}

}