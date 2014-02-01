<?php

class CInstanzAnlegenForm extends CFormModel {

	/** @var string */
	public $kontakt;
	public $name;
	public $subdomain;
	public $antragsschluss;
	public $admin_email;

	/** @var int */
	public $zahlung;
	public $typ = 1; // Veranstaltung::$TYP_PROGRAMM

	/** @var bool */
	public $aenderungsantraege_moeglich = true;
	public $kommentare_moeglich = true;
	public $sofort_offen = true;

	public function rules()
	{
		return array(
			array('kontakt, name, subdomain, zahlung, typ, aenderungsantraege_moeglich, kommentare_moeglich', 'required'),
			array('antragsschluss', 'date'),
			array('admin_email', 'email'),
			array('zahlung, typ', 'numerical'),
			array('aenderungsantraege_moeglich, kommentare_moeglich', 'boolean'),
			array('subdomain', 'unique', 'className' => 'Veranstaltungsreihe'),
			array('kontakt, name, typ', 'safe'),
		);
	}

}