<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tobias
 * Date: 09.04.12
 * Time: 19:24
 * To change this template use File | Settings | File Templates.
 */
abstract class IUnterstuetzer extends GxActiveRecord {

    public static $ROLLE_INITIATOR = 'initiator';
    public static $ROLLE_UNTERSTUETZER = 'unterstuetzt';
    public static $ROLLE_MAG = 'mag';
    public static $ROLLE_MAG_NICHT = 'magnicht';
    public static $ROLLEN = array(
        'initiator' => 'InitiatorIn',
        'unterstuetzt' => 'UnterstützerIn',
        'mag' => 'Mag',
        'magnicht' => 'Mag nicht',
    );

}
