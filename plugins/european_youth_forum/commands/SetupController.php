<?php

declare(strict_types=1);

namespace app\plugins\european_youth_forum\commands;

use app\models\db\{Consultation, ConsultationUserGroup, User};
use yii\console\Controller;

class SetupController extends Controller
{
    private function createUserGroupIfNotExists(Consultation $consultation, string $title): void
    {
        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $title]);
        if (!$group) {
            $group = new ConsultationUserGroup();
            $group->consultationId = $consultation->id;
            $group->siteId = $consultation->siteId;
            $group->title = $title;
            $group->position = 0;
            $group->selectable = 1;
            $group->save();
        }
    }

    /**
     * Create necessary groups for a consultation
     */
    public function actionUserGroups(string $urlPath): void
    {
        $consultation = Consultation::findOne(['urlPath' => $urlPath]);
        if (!$consultation) {
            echo "Consultation not found\n";
            return;
        }

        $this->createUserGroupIfNotExists($consultation, 'INGYO Full member (OD) WITH Voting rights');
        $this->createUserGroupIfNotExists($consultation, 'NYC Full member (OD) WITH Voting rights');
        $this->createUserGroupIfNotExists($consultation, 'INGYO Full member (OD) NO Voting rights');
        $this->createUserGroupIfNotExists($consultation, 'NYC Full member (OD) NO Voting rights');
        $this->createUserGroupIfNotExists($consultation, 'INGYO Full member NOT participating');
        $this->createUserGroupIfNotExists($consultation, 'NYC Full member NOT participating');
        $this->createUserGroupIfNotExists($consultation, 'INGYO Substitute Delegate');
        $this->createUserGroupIfNotExists($consultation, 'NYC Substitute Delegate');
        $this->createUserGroupIfNotExists($consultation, 'INGYO Observer (OD)');
        $this->createUserGroupIfNotExists($consultation, 'NYC Observer (OD)');
        $this->createUserGroupIfNotExists($consultation, 'INGYO Candidate (OD)');
        $this->createUserGroupIfNotExists($consultation, 'NYC Candidate (OD)');
        $this->createUserGroupIfNotExists($consultation, 'Associates');
        $this->createUserGroupIfNotExists($consultation, 'YFJ Board');
        $this->createUserGroupIfNotExists($consultation, 'YFJ Staff');
        $this->createUserGroupIfNotExists($consultation, 'Remote user');

        echo "Created the necessary user groups.\n";
    }

    private function createOrGetUserAccount(string $email, string $password, string $givenName, string $familyName, string $organization): User
    {
        $user = User::findOne(['auth' => 'email:' . $email]);
        if ($user) {
            return $user;
        }

        $user = new User();
        $user->name = $givenName . ' ' . $familyName;
        $user->nameFamily = $familyName;
        $user->nameGiven = $givenName;
        $user->organization = $organization;
        $user->email = $email;
        $user->emailConfirmed = 1;
        $user->auth = 'email:' . $email;
        $user->dateCreation = date('Y-m-d H:i:s');
        $user->status = User::STATUS_CONFIRMED;
        $user->pwdEnc = (string)password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return $user;
    }

    private function createTestAccountsForGroup(Consultation $consultation, string $groupName, string $emailPrefix, string $orgaPrefix, int $count): void
    {
        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $groupName]);
        if (!$group) {
            throw new \Exception('Group not found: ' . $groupName);
        }
        for ($i = 0; $i < $count; $i++) {
            $user = $this->createOrGetUserAccount(
                $emailPrefix . '-' . $i . '@example.org',
                'Test',
                'Test',
                'User ' . $i,
                $orgaPrefix . '-' . $i
            );
            $group->addUser($user);
        }
    }

    /**
     * Create necessary groups for a consultation
     */
    public function actionDemoAccounts(string $urlPath): void
    {
        $consultation = Consultation::findOne(['urlPath' => $urlPath]);
        if (!$consultation) {
            echo "Consultation not found\n";
            return;
        }

        $this->createTestAccountsForGroup($consultation, 'INGYO Full member (OD) WITH Voting rights', 'ingyo-full', 'INGYO-F', 10);
        $this->createTestAccountsForGroup($consultation, 'NYC Full member (OD) WITH Voting rights', 'nyc-full', 'NYC-F', 8);
        $this->createTestAccountsForGroup($consultation, 'INGYO Full member (OD) NO Voting rights', 'ingyo-full-nov', 'INGYO-FNOV', 2);
        $this->createTestAccountsForGroup($consultation, 'NYC Full member (OD) NO Voting rights', 'nyc-full-nov', 'NYC-FNOV', 1);
        $this->createTestAccountsForGroup($consultation, 'INGYO Full member NOT participating', 'ingyo-full-nop', 'INGYO-FNOP', 4);
        $this->createTestAccountsForGroup($consultation, 'NYC Full member NOT participating', 'nyc-full-nop', 'NYC-FNOP', 4);
        $this->createTestAccountsForGroup($consultation, 'INGYO Substitute Delegate', 'ingyo-sub', 'INGYO-SUB', 4);
        $this->createTestAccountsForGroup($consultation, 'NYC Substitute Delegate', 'nyc-sub', 'NYC-SUB', 4);
        $this->createTestAccountsForGroup($consultation, 'INGYO Observer (OD)', 'ingyo-ob', 'INYGO-OB', 2);
        $this->createTestAccountsForGroup($consultation, 'NYC Observer (OD)', 'nyc-ob', 'NYC-OB', 2);
        $this->createTestAccountsForGroup($consultation, 'INGYO Candidate (OD)', 'ingyo-can', 'INYGO-CAN', 2);
        $this->createTestAccountsForGroup($consultation, 'NYC Candidate (OD)', 'nyc-can', 'NYC-CAN', 2);
        $this->createTestAccountsForGroup($consultation, 'Associates', 'assoc', 'ASSOC', 1);

        echo "Created the dummy accounts.\n";
    }
}
