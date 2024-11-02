<?php

use app\models\db\ISupporter;

/**
 * @var \yii\web\View $this
 * @var ISupporter[] $users
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;


$data          = [];
$usedUsernames = [];
foreach ($users as $user) {
    $userData   = [];
    $userData[] = ''; // Title
    if ($user->personType == ISupporter::PERSON_ORGANIZATION) {
        if (in_array($user->organization, $usedUsernames)) {
            continue;
        } else {
            $usedUsernames[] = $user->organization;
        }
        $userData[] = $user->organization;
        $userData[] = '';
    } else {
        if (in_array($user->name, $usedUsernames)) {
            continue;
        } else {
            $usedUsernames[] = $user->name;
        }
        $parts      = explode(' ', $user->name);
        $userData[] = array_shift($parts);
        $userData[] = implode(' ', $parts);
    }
    if ($user->personType == ISupporter::PERSON_ORGANIZATION) {
        $userData[] = ''; // Structure Level
    } else {
        $userData[] = $user->organization; // Structure Level
    }
    $userData[] = 0; // Group
    $userData[] = ''; // Comment
    $userData[] = 0; // Is active
    $data[]     = $userData;
}


$fp = fopen('php://output', 'w');

fputcsv($fp, ['title', 'first_name', 'last_name', 'structure_level', 'groups', 'comment', 'is_active'], ',', '"', "\\");

foreach ($data as $arr) {
    fputcsv($fp, $arr, ',', '"', "\\");
}
fclose($fp);
