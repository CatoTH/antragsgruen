<?php

/**
 * @param Veranstaltung $veranstaltung
 * @return bool
 */
function veranstaltungsspezifisch_ae_sortierung_zeilennummer($veranstaltung) {
	return false;
}

/**
 * @param Veranstaltung $veranstaltung
 * @return array
 */
function veranstaltungsspezifisch_css_files($veranstaltung) {
	return array();
}


/**
 * @param Veranstaltung $veranstaltung
 * @return bool
 */
function veranstaltungsspezifisch_erzwinge_login($veranstaltung) {
	return false;
}


/**
 * @param Veranstaltung $veranstaltung
 * @return bool
 */
function veranstaltungsspezifisch_antragsgruen_in_sidebar($veranstaltung) {
	return true;
}

/**
 * @param Veranstaltung $veranstaltung
 * @param string $text
 * @return string
 */
function veranstaltungsspezifisch_hinweis_namespaced_accounts($veranstaltung, $text) {
	return $text;
}


/**
 * @param Veranstaltung $veranstaltung
 * @param string $link
 * @return string
 */
function veranstaltungsspezifisch_antrag_einreichen_str($veranstaltung, $link) {
	return "";
}
