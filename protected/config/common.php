<?php

define("ANTRAGSGRUEN_VERSION", "2.5.0");

$url_rules = array(
	$domv . 'admin/'                                                                             => 'admin/index',
	$domv . 'admin/veranstaltungen/'                                                             => 'admin/veranstaltungen/update',
	$domv . 'admin/veranstaltungen/<_a:(index|create|update|update_extended|delete|view|admin)>' => 'admin/veranstaltungen/<_a>',
	$domv . 'admin/antraege'                                                                     => 'admin/antraege',
	$domv . 'admin/antraege/<_a:(index|create|update|delete|view|admin)>'                        => 'admin/antraege/<_a>',
	$domv . 'admin/aenderungsantraege'                                                           => 'admin/aenderungsantraege',
	$domv . 'admin/aenderungsantraege/<_a:(index|create|update|delete|view|admin)>'              => 'admin/aenderungsantraege/<_a>',
	$domv . 'admin/antraegeKommentare'                                                           => 'admin/antraegeKommentare',
	$domv . 'admin/antraegeKommentare/<_a:(index|create|update|delete|view|admin)>'              => 'admin/antraegeKommentare/<_a>',
	$domv . 'admin/aenderungsantraegeKommentare'                                                 => 'admin/aenderungsantraegeKommentare',
	$domv . 'admin/aenderungsantraegeKommentare/<_a:(index|create|update|delete|view|admin)>'    => 'admin/aenderungsantraegeKommentare/<_a>',
	$domv . 'admin/texte'                                                                        => 'admin/texte',
	$domv . 'admin/texte/<_a:(index|create|update|delete|view|admin)>'                           => 'admin/texte/<_a>',
	$domv . 'admin/kommentare_excel'                                                             => 'admin/index/kommentareexcel',
	$domv . 'admin/namespacedAccounts'                                                           => 'admin/index/namespacedAccounts',
	$domv . 'admin/ae_pdf_list'                                                                  => 'admin/index/aePDFList',
	$dom . 'admin/reihe_admins'                                                                  => 'admin/index/reiheAdmins',
	$dom . 'admin/reihe_veranstaltungen'                                                         => 'admin/index/reiheVeranstaltungen',
	$domv . 'hilfe'                                                                              => 'veranstaltung/hilfe',
	$domv . 'suche'                                                                              => 'veranstaltung/suche',
	$dom . 'impressum'                                                                           => 'veranstaltung/impressum',
	$domv . 'pdfs'                                                                               => 'veranstaltung/pdfs',
	$domv . 'aenderungsantrags_pdfs'                                                             => 'veranstaltung/aenderungsantragsPdfs',
	$domv . 'feedAlles'                                                                          => 'veranstaltung/feedAlles',
	$domv . 'feedAntraege'                                                                       => 'veranstaltung/feedAntraege',
	$domv . 'feedAenderungsantraege'                                                             => 'veranstaltung/feedAenderungsantraege',
	$domv . 'feedKommentare'                                                                     => 'veranstaltung/feedKommentare',
	$domv . 'wartungsmodus'                                                                      => 'veranstaltung/wartungsmodus',
	$dom . 'login'                                                                               => 'veranstaltung/login',
	$dom . 'logout'                                                                              => 'veranstaltung/logout',
	$dom . 'anmelden_bestaetigen'                                                                => 'veranstaltung/anmeldungBestaetigen',
	$dom . 'benachrichtigungen'                                                                  => 'veranstaltung/benachrichtigungen',
	$dom . 'benachrichtigungen/checkemail'                                                       => 'veranstaltung/ajaxEmailIstRegistriert',
	$dom . 'benachrichtigungen/abmelden'                                                         => 'veranstaltung/benachrichtigungenAbmelden',
	$domv . 'antrag/neu'                                                                         => 'antrag/neu',
	$domv . 'antrag/<antrag_id:\d+>?kommentar_id=<kommentar_id:\d+>'                             => 'antrag/anzeige',
	$domv . 'antrag/<antrag_id:\d+>'                                                             => 'antrag/anzeige',
	$domv . 'antrag/<antrag_id:\d+>/pdf'                                                         => 'antrag/pdf',
	$domv . 'antrag/<antrag_id:\d+>/plain_html'                                                  => 'antrag/plainHtml',
	$domv . 'antrag/<antrag_id:\d+>/odt'                                                         => 'antrag/odt',
	$domv . 'antrag/<antrag_id:\d+>/neuConfirm'                                                  => 'antrag/neuConfirm',
	$domv . 'antrag/<antrag_id:\d+>/aendern'                                                     => 'antrag/aendern',
	$domv . 'antrag/<antrag_id:\d+>/aes_einpflegen'                                              => 'antrag/aes_einpflegen',
	$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>'                  => 'aenderungsantrag/anzeige',
	$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/neuConfirm'       => 'aenderungsantrag/neuConfirm',
	$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/pdf'              => 'aenderungsantrag/pdf',
	$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/pdf_diff'         => 'aenderungsantrag/pdfDiff',
	$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/neu'                                        => 'aenderungsantrag/neu',
	$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/ajaxCalcDiff'                               => 'aenderungsantrag/ajaxCalcDiff',
	$domv                                                                                        => 'veranstaltung/index',
	$dom                                                                                         => 'veranstaltung/index',
);


if (MULTISITE_MODE) {
	$url_rules = array_merge(array(
		$dom_plain                         => 'infos/selbstEinsetzen',
		$dom_plain . 'selbst-einsetzen'    => 'infos/selbstEinsetzen',
		$dom_plain . 'neu-anlegen'         => 'infos/neuAnlegen',
		$dom_plain . 'impressum'           => 'infos/impressum',
		$dom_plain . 'passwort'            => 'infos/passwort',
		$dom_plain . 'rechnungsverwaltung' => 'infos/rechnungsverwaltung',
		$dom_plain . 'login'               => 'infos/login',
		$dom_plain . 'logout'              => 'infos/logout',
	), $url_rules);

	foreach ($url_rules as $key => $val) $url_rules[str_replace("http://", "http://www.", $key)] = $val;
}
