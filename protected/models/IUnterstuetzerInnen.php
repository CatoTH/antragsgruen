<?php

abstract class IUnterstuetzerInnen extends GxActiveRecord {

    public static $ROLLE_INITIATORIN = 'initiator';
    public static $ROLLE_UNTERSTUETZERIN = 'unterstuetzt';
    public static $ROLLE_MAG = 'mag';
    public static $ROLLE_MAG_NICHT = 'magnicht';
    public static $ROLLEN = array(
        'initiator' => 'InitiatorIn',
        'unterstuetzt' => 'UnterstützerIn',
        'mag' => 'Mag',
        'magnicht' => 'Mag nicht',
    );

}
