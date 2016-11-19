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
    $userData[] = ''; // Gender
    $userData[] = ''; // E-Mail
    $userData[] = 3; // Group ID ?
    if ($user->personType == ISupporter::PERSON_ORGANIZATION) {
        $userData[] = ''; // Structure Level
    } else {
        $userData[] = $user->organization; // Structure Level
    }
    $userData[] = ''; // Committe
    $userData[] = ''; // About me
    if ($user->personType == ISupporter::PERSON_ORGANIZATION) {
        $userData[] = $user->name; // Comment
    } else {
        $userData[] = ''; // Comment
    }
    $userData[] = 0; // Is active
    $data[]     = $userData;
}


$fp = fopen('php://output', 'w');

fputcsv($fp, ['Title', 'First Name', 'Last Name', 'Gender', 'Email', 'Group id',
    'Structure Level', 'Committee', 'About me', 'Comment', 'Is active'], ';', '"');

foreach ($data as $arr) {
    fputcsv($fp, $arr, ';', '"');
}
fclose($fp);
