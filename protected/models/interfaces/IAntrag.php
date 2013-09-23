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
	public static $STATI = array(
		-2 => "Gelöscht",
		-1 => "Zurückgezogen",
		0  => "Unbestätigt", // Noch nicht bestätigt
		1  => "Entwurf",
		2  => "Eingereicht (ungeprüft)",
		3  => "Eingereicht",
		4  => "Angenommen",
		5  => "Abgelehnt",
		6  => "Modifiziert angenommen",
		7 => "Modifziert",
	);
	public static $STATI_UNSICHTBAR = array(0, 2, -2);


}
