<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\api\imotion\{SupportRequest, SupporterType};
use app\models\db\{ConsultationMotionType, ISupporter};
use app\models\exceptions\FormError;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\{CollectBeforePublish, SupportBase};
use Tests\Support\Helper\TestBase;

class SupportRequestValidationTest extends TestBase
{
    private function getSupportType(callable $settingsModifier): SupportBase
    {
        $settings = new InitiatorForm(null);
        $settingsModifier($settings);

        return new CollectBeforePublish(new ConsultationMotionType(), $settings);
    }

    public function testDefaultSettingsOnlyAllowNaturalPersons(): void
    {
        $supportType = $this->getSupportType(function (InitiatorForm $settings): void {
            $settings->hasOrganizations = false;
        });

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::PERSON);
        $request->validate($supportType);
        $this->assertSame(ISupporter::PERSON_NATURAL, $request->getPersonTypeDb());

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::ORGANIZATION, organization: 'E Corp');
        $this->expectException(FormError::class);
        $request->validate($supportType);
    }

    public function testSupportingAsOrganization(): void
    {
        $supportType = $this->getSupportType(function (InitiatorForm $settings): void {
            $settings->hasOrganizations = false;
            $settings->supporterCanBeOrganization = true;
        });

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::ORGANIZATION, organization: 'E Corp');
        $request->validate($supportType);
        $this->assertSame(ISupporter::PERSON_ORGANIZATION, $request->getPersonTypeDb());

        // Supporting as a natural person is still possible, as it is not disabled
        $request = new SupportRequest(name: 'Ava', personType: SupporterType::PERSON);
        $request->validate($supportType);
        $this->assertSame(ISupporter::PERSON_NATURAL, $request->getPersonTypeDb());
    }

    public function testOrganizationIsRequiredWhenSupportingAsOrganization(): void
    {
        $supportType = $this->getSupportType(function (InitiatorForm $settings): void {
            $settings->hasOrganizations = false;
            $settings->supporterCanBeOrganization = true;
        });

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::ORGANIZATION, organization: '  ');
        $this->expectException(FormError::class);
        $request->validate($supportType);
    }

    public function testSupportingAsNaturalPersonCanBeDisabled(): void
    {
        $supportType = $this->getSupportType(function (InitiatorForm $settings): void {
            $settings->hasOrganizations = false;
            $settings->supporterCanBePerson = false;
            $settings->supporterCanBeOrganization = true;
        });

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::PERSON);
        $this->expectException(FormError::class);
        $request->validate($supportType);
    }

    public function testGenderIsNotRequiredForOrganizations(): void
    {
        $supportType = $this->getSupportType(function (InitiatorForm $settings): void {
            $settings->hasOrganizations = false;
            $settings->supporterCanBeOrganization = true;
            $settings->contactGender = InitiatorForm::CONTACT_REQUIRED;
        });

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::ORGANIZATION, organization: 'E Corp', gender: 'female');
        $request->validate($supportType);
        $this->assertNull($request->gender);

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::PERSON);
        $this->expectException(FormError::class);
        $request->validate($supportType);
    }

    public function testOrganizationIsRequiredForNaturalPersonsIfConfigured(): void
    {
        $supportType = $this->getSupportType(function (InitiatorForm $settings): void {
            $settings->hasOrganizations = true;
        });

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::PERSON, organization: 'E Corp');
        $request->validate($supportType);

        $request = new SupportRequest(name: 'Ava', personType: SupporterType::PERSON);
        $this->expectException(FormError::class);
        $request->validate($supportType);
    }

    public function testPersonTypeFromWebRequest(): void
    {
        $supportType = $this->getSupportType(function (InitiatorForm $settings): void {
            $settings->supporterCanBeOrganization = true;
        });

        $request = SupportRequest::fromWebRequest([
            'motionSupportName' => 'Ava',
            'motionSupportOrga' => 'E Corp',
            'motionSupportPersonType' => (string)ISupporter::PERSON_ORGANIZATION,
        ], null, $supportType);
        $this->assertSame(SupporterType::ORGANIZATION, $request->personType);

        $request = SupportRequest::fromWebRequest([
            'motionSupportName' => 'Ava',
            'motionSupportOrga' => 'E Corp',
            'motionSupportPersonType' => (string)ISupporter::PERSON_NATURAL,
        ], null, $supportType);
        $this->assertSame(SupporterType::PERSON, $request->personType);

        // Requests without an explicit person type are natural persons
        $request = SupportRequest::fromWebRequest(['motionSupportName' => 'Ava'], null, $supportType);
        $this->assertSame(SupporterType::PERSON, $request->personType);
    }
}
