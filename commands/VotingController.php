<?php

namespace app\commands;

use app\models\db\{Motion, User, VotingBlock};
use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

class VotingController extends Controller
{
    public function actionExportUser()
    {
        /** @var User[] $users */
        $users = User::find()->orderBy('email')->all();

        ob_start();
        $fp = fopen('php://output', 'w');

        fputcsv($fp, ['Name', 'E-Mail', 'Organization', 'Pillar'], ';', '"');

        foreach ($users as $user) {
            $organisations = $user->getMyOrganizationIds();
            $arr = [
                $user->name,
                $user->email,
                $user->organization,
                (count($organisations) > 0 ? implode(',', $organisations) : ''),
            ];

            fputcsv($fp, $arr, ';', '"');
        }

        fclose($fp);
        echo ob_get_clean();
    }

    public function actionCompareEligibility(string $filelist) {
        $usersThatAreAllowed = [];
        $knownUsers = [];

        /** @var User[] $users */
        $users = User::find()->orderBy('email')->all();
        foreach ($users as $user) {
            $knownUsers[] = $user->email;
            if ($this->isAllowedToVote($user)) {
                $usersThatAreAllowed[] = $user->email;
            }
        }

        $usersThatShouldBeAllowed = [];
        $lines = explode("\n", file_get_contents($filelist));
        $knownButNoPermission = [];
        $unknownUsers = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (trim($line) === '') {
                continue;
            }
            $usersThatShouldBeAllowed[] = $line;
            if (in_array($line, $usersThatAreAllowed)) {
                continue;
            } elseif (in_array($line, $knownUsers)) {
                $knownButNoPermission[] = $line;
            } else {
                $unknownUsers[] = $line;
            }
        }

        $tooManyPermissions = array_diff($usersThatAreAllowed, $usersThatShouldBeAllowed);

        echo "Users on the official list that do not have an account:\n";
        foreach ($unknownUsers as $user) {
            echo "- " . $user . "\n";
        }
        if (count($unknownUsers) === 0) {
            echo "[None]\n";
        }
        echo "\n";

        echo "Users on the official list that do not have voting rights yet:\n";
        foreach ($knownButNoPermission as $user) {
            echo "- " . $user . "\n";
        }
        if (count($knownButNoPermission) === 0) {
            echo "[None]\n";
        }
        echo "\n";

        echo "Users that have voting rights but should not:\n";
        foreach ($tooManyPermissions as $user) {
            echo "- " . $user . "\n";
        }
        if (count($tooManyPermissions) === 0) {
            echo "[None]\n";
        }
        echo "\n";
    }
}
