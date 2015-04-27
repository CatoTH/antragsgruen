<?php

if (!function_exists("veranstaltungsspezifisch_ae_sortierung_zeilennummer")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @return bool
	 */
	function veranstaltungsspezifisch_ae_sortierung_zeilennummer($veranstaltung)
	{
		return false;
	}
}

if (!function_exists("veranstaltungsspezifisch_css_files")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @return array
	 */
	function veranstaltungsspezifisch_css_files($veranstaltung)
	{
		return array();
	}
}

if (!function_exists("veranstaltungsspezifisch_erzwinge_login")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @return bool
	 */
	function veranstaltungsspezifisch_erzwinge_login($veranstaltung)
	{
		return false;
	}
}

if (!function_exists("veranstaltungsspezifisch_antragsgruen_in_sidebar")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @return bool
	 */
	function veranstaltungsspezifisch_antragsgruen_in_sidebar($veranstaltung)
	{
		return true;
	}
}

if (!function_exists("veranstaltungsspezifisch_hinweis_namespaced_accounts")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @param string $text
	 * @return string
	 */
	function veranstaltungsspezifisch_hinweis_namespaced_accounts($veranstaltung, $text)
	{
		return $text;
	}
}

if (!function_exists("veranstaltungsspezifisch_antrag_einreichen_str")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @param string $link
	 * @return string
	 */
	function veranstaltungsspezifisch_antrag_einreichen_str($veranstaltung, $link)
	{
		return "";
	}
}

if (!function_exists("veranstaltungsspezifisch_antrag_typ_str")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @param int $typ
	 * @return string
	 */
	function veranstaltungsspezifisch_antrag_typ_str($veranstaltung, $typ)
	{
		return Antrag::$TYPEN[$typ];
	}
}


if (!function_exists("veranstaltungsspezifisch_antrag_max_len")) {
	/**
	 * @param Veranstaltung $veranstaltung
	 * @param null|int $antrag_typ
     * @param bool $text2
	 * @return int
	 */
	function veranstaltungsspezifisch_antrag_max_len($veranstaltung, $antrag_typ, $text2 = false)
	{
		return $veranstaltung->getEinstellungen()->antragstext_max_len;
	}
}


if (!function_exists("veranstaltungsspezifisch_antrag_pdf_header")) {
	/**
	 * @param Antrag $antrag
	 * @param Sprache $sprache
	 * @param string $initiatorInnen
	 * @return array
	 */
	function veranstaltungsspezifisch_antrag_pdf_header($antrag, $sprache, $initiatorInnen)
	{
		return array(Yii::app()->params['pdf_logo'], $initiatorInnen, $antrag->name, $sprache->get("Antragstext"), $antrag->revision_name, "Courier", 10);
	}
}

if (!function_exists("veranstaltungsspezifisch_email_from_name")) {
    /**
     * @param Veranstaltung|null $veranstaltung
     * @return string
     */
    function veranstaltungsspezifisch_email_from_name($veranstaltung = null)
    {
        if ($veranstaltung === null) return Yii::app()->params['mail_from_name'];
        return Yii::app()->params['mail_from_name'];
    }
}



if (!function_exists("veranstaltungsspezifisch_email_reply_to")) {
	/**
	 * @param Veranstaltung|null $veranstaltung
	 * @return string|null
	 */
	function veranstaltungsspezifisch_email_reply_to($veranstaltung = null)
	{
		return null;
	}
}


if (!function_exists("veranstaltungsspezifisch_text2_name")) {
    /**
     * @param Veranstaltung|null $veranstaltung
     * @param int $antrag_typ
     * @return string|null
     */
    function veranstaltungsspezifisch_text2_name($veranstaltung = null, $antrag_typ = 0)
    {
        return null;
    }
}


if (!function_exists("veranstaltungsspezifisch_text1_name")) {
    /**
     * @param Veranstaltung|null $veranstaltung
     * @param int $antrag_typ
     * @return string|null
     */
    function veranstaltungsspezifisch_text1_name($veranstaltung = null, $antrag_typ = 0)
    {
        return null;
    }
}


if (!function_exists("veranstaltungsspezifisch_begruendung_name")) {
    /**
     * @param Veranstaltung|null $veranstaltung
     * @param int $antrag_typ
     * @return string|null
     */
    function veranstaltungsspezifisch_begruendung_name($veranstaltung = null, $antrag_typ = 0)
    {
        return null;
    }
}

if (!function_exists("veranstaltungsspezifisch_begruendung_maxlen")) {
    /**
     * @param Veranstaltung|null $veranstaltung
     * @param int $antrag_typ
     * @return string|null
     */
    function veranstaltungsspezifisch_begruendung_maxlen($veranstaltung = null, $antrag_typ = 0)
    {
        return null;
    }
}



if (!function_exists("veranstaltungsspezifisch_antrag_sort")) {
    /**
     * @param null|Veranstaltung $veranstaltung
     * @return callable|null
     */
    function veranstaltungsspezifisch_antrag_sort($veranstaltung = null)
    {
        return null;
    }
}
