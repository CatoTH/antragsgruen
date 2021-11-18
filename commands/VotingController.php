<?php

namespace app\commands;

use app\models\db\{Motion, User, VotingBlock};
use app\models\settings\AntragsgruenApp;
use yii\console\Controller;

class VotingController extends Controller
{
    private function isAllowedToVote(User $user): bool {
        $votingBlock = new VotingBlock();
        $motion = new Motion();

        // In case a plugin provides eligibility check, we take its result. The first plugin providing the check wins.
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $allowed = $plugin::userIsAllowedToVoteFor($votingBlock, $user, $motion);
            if ($allowed !== null) {
                return $allowed;
            }
        }

        // If no plugin
        return true;
    }

    public function actionExportUser()
    {
        $users = User::find()->orderBy('email')->all();

        ob_start();
        $fp = fopen('php://output', 'w');

        fputcsv($fp, ['Name', 'E-Mail', 'Organization', 'Pillar', 'Voting rights'], ';', '"');

        foreach ($users as $user) {
            $votingRights = $this->isAllowedToVote($user);
            $organisations = $user->getMyOrganizationIds();
            $arr = [
                $user->name,
                $user->email,
                $user->organization,
                (count($organisations) > 0 ? implode(',', $organisations) : ''),
                ($votingRights ? 'YES' : ''),
            ];

            fputcsv($fp, $arr, ';', '"');
        }

        fclose($fp);
        echo ob_get_clean();
    }

    public function actionCompareEligibility(string $filelist) {
        $usersThatAreAllowed = [];
        $knownUsers = [];

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
