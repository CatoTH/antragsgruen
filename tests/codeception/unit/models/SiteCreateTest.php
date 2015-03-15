<?php

namespace app\tests\codeception\unit\models;

use app\models\db\Site;
use app\models\db\User;
use app\models\forms\SiteCreateForm;
use Codeception\Specify;
use tests\codeception\unit\models\DBTestBase;

class SiteCreateTest extends DBTestBase
{
    public function testParteitagCreate()
    {
        $this->specify(
            'Creating a Parteitag',
            function () {
                $form                 = new SiteCreateForm();

                $success = $form->validate();
                expect('Form to complain about missing presets', $success)->false();

                $form->title          = 'Testveranstaltung 4';
                $form->subdomain      = 'test4';
                $form->hasComments    = 1;
                $form->hasAmendments  = 1;
                $form->openNow        = 1;
                $form->contact        = 'Myself';
                $form->isWillingToPay = 2;
                $form->preset         = 0;

                $success = $form->validate();
                expect('Form to validate', $success)->true();

                $user = new User();
                $user->name = 'Admin2';
                $user->auth = 'email:blabla@example.org';
                $user->email = 'blabla@example.org';
                $user->status = User::STATUS_CONFIRMED;
                $saved = $user->save();
                expect('Create Dummy User', $saved)->true();

                try {
                    $site = $form->createSiteFromForm($user);
                    expect('Site Created', $site)->notNull();
                    expect('Has Consultation', $site->currentConsultationId)->notNull();
                } catch (\app\models\exceptions\DB $e) {
                    $this->fail($e);
                }

                /** @var Site $site */
                $site = Site::findOne(['subdomain' => 'test4']);
                expect('Check if site exists', $site)->notNull();

                $consultation = $site->currentConsultation;
                expect('Check if consultation exists', $consultation)->notNull();

                expect('Has some sections', count($consultation->motionSections))->greaterThan(0);
                expect('Has some motion types', count($consultation->motionTypes))->greaterThan(0);
            }
        );
    }
}
