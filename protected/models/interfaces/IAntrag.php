<?php

abstract class IAntrag extends GxActiveRecord
{
	public static $STATUS_GELOESCHT = -2;
	public static $STATUS_ZURUECKGEZOGEN = -1;
	public static $STATUS_UNBESTAETIGT = 0;
	public static $STATUS_ENTWURF = 1;
	public static $STATUS_EINGEREICHT_UNGEPRUEFT = 2;
	public static $STATUS_EINGEREICHT_GEPRUEFT = 3;
	public static $STATUS_ANGENOMMEN = 4;
	public static $STATUS_ABGELEHNT = 5;
	public static $STATUS_MODIFIZIERT_ANGENOMMEN = 6;
	public static $STATUS_MODIFIZIERT = 7;
	public static $STATUS_UEBERNAHME = 8;
	public static $STATUS_ERLEDIGT = 9;
	public static $STATUS_UEBERWEISUNG = 10;
	public static $STATUS_ABSTIMMUNG = 11;
	public static $STATUS_PAUSIERT = 12;
	public static $STATUS_INFORMATIONEN_FEHLEN = 13;
	public static $STATUS_NICHT_ZUGELASSEN = 14;
	public static $STATI = array(
		-2 => "Gelöscht",
		-1 => "Zurückgezogen",
		0  => "Unbestätigt", // Noch nicht bestätigt
		1  => "Entwurf",
		2  => "Eingereicht (ungeprüft)",
		3  => "Eingereicht",
		4  => "Angenommen",
		5  => "Abgelehnt",
		6  => "Modifizierte Übernahme",
		7  => "Modifziert",
		8  => "Übernahme",
		9  => "Erledigt",
		10 => "Überweisung",
		11 => "Abstimmung",
		12 => "Pausiert",
		13 => "Informationen fehlen",
		14 => "Nicht zugelassen",
	);
	public static $STATI_UNSICHTBAR = array(0, 2, -2);


	/**
	 * @return Person[]
	 */
	abstract public function getAntragstellerInnen();

	/**
	 * @return Person[]
	 */
	abstract public function getUnterstuetzerInnen();

	/**
	 * @return Person[]
	 */
	abstract public function getZustimmungen();

	/**
	 * @return Person[]
	 */
	abstract public function getAblehnungen();
}
