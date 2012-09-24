<?php

abstract class IKommentar extends GxActiveRecord {

	public static $STATUS_FREI = 0;
	public static $STATUS_GELOESCHT = -1;
	public static $STATI = array(
		-1 => "Gelöscht",
		0 => "Sichtbar",
	);

	public function kannLoeschen(CWebUser $c) {
		if ($c->getState("role") == "admin") return true;
		if ($c->getId() == $this->verfasser->auth) return true;
		return false;
	}

}
