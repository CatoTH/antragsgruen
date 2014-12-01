<?php

/**
 * @param Veranstaltung $veranstaltung
 * @return bool
 */
if (!function_exists("veranstaltungsspezifisch_ae_sortierung_zeilennummer")) {
	function veranstaltungsspezifisch_ae_sortierung_zeilennummer($veranstaltung)
	{
		return false;
	}
}

/**
 * @param Veranstaltung $veranstaltung
 * @return array
 */
if (!function_exists("veranstaltungsspezifisch_css_files")) {
	function veranstaltungsspezifisch_css_files($veranstaltung)
	{
		return array();
	}
}

/**
 * @param Veranstaltung $veranstaltung
 * @return bool
 */
if (!function_exists("veranstaltungsspezifisch_erzwinge_login")) {
	function veranstaltungsspezifisch_erzwinge_login($veranstaltung)
	{
		return false;
	}
}

/**
 * @param Veranstaltung $veranstaltung
 * @return bool
 */
if (!function_exists("veranstaltungsspezifisch_antragsgruen_in_sidebar")) {
	function veranstaltungsspezifisch_antragsgruen_in_sidebar($veranstaltung)
	{
		return true;
	}
}

/**
 * @param Veranstaltung $veranstaltung
 * @param string $text
 * @return string
 */
if (!function_exists("veranstaltungsspezifisch_hinweis_namespaced_accounts")) {
	function veranstaltungsspezifisch_hinweis_namespaced_accounts($veranstaltung, $text)
	{
		return $text;
	}
}

/**
 * @param Veranstaltung $veranstaltung
 * @param string $link
 * @return string
 */
if (!function_exists("veranstaltungsspezifisch_antrag_einreichen_str")) {
	function veranstaltungsspezifisch_antrag_einreichen_str($veranstaltung, $link)
	{
		return "";
	}
}