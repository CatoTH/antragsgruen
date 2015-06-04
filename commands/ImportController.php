<?php
namespace app\commands;

use yii\console\Controller;
use yii\db\Connection;

/**
 * Functions to import data (e.g. from old versions of Antragsgrün)
 * @package app\commands
 */
class ImportController extends Controller
{
    /**
     * @param Connection $dbOld
     * @param array $siteRow
     */
    private function migrateSite(Connection $dbOld, $siteRow)
    {
        echo 'Migrating: ' . $siteRow['subdomain'] . "\n";
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionMigrateFromV2($server = '', $host = '', $username = '', $password = '', $subdomain = '')
    {

        if ($server == '') {
            $server = $this->prompt('DB-Server:');
        }
        if ($host == '') {
            $host = $this->prompt('DB Datenbank:');
        }
        if ($username == '') {
            $username = $this->prompt('DB-Username:');
        }
        if ($password == '') {
            $password = $this->prompt('DB-Passwort:');
        }

        $dbOld = new Connection([
            'dsn'      => 'mysql:host=' . $server . ';dbname=' . $host,
            'username' => $username,
            'password' => $password,
        ]);
        $dbOld->open();

        if ($subdomain != '') {
            $where   = 'subdomain = "' . addslashes($subdomain) . '"';
            $command = $dbOld->createCommand('SELECT * FROM veranstaltungsreihe WHERE ' . $where);
        } else {
            $command = $dbOld->createCommand('SELECT * FROM veranstaltungsreihe');
        }
        $sites = $command->queryAll();
        foreach ($sites as $site) {
            $this->migrateSite($dbOld, $site);
        }
    }
}
