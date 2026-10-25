<?php

namespace Tests\Unit;

use app\plugins\generic_sso\SsoLogin;
use Tests\Support\Helper\TestBase;

class SsoLoginAttributeMappingTest extends TestBase
{
    public function testDefaultAttributeMappingUsedWhenNoOverridesConfigured(): void
    {
        $sso = new SsoLogin();
        $mapping = $sso->getEffectiveAttributeMapping('oidc');

        $this->assertEquals('email', $mapping['email']);
        $this->assertEquals('preferred_username', $mapping['username']);
        $this->assertEquals('given_name', $mapping['givenName']);
        $this->assertEquals('family_name', $mapping['familyName']);
        $this->assertEquals('organization', $mapping['organization']);
        $this->assertEquals('groups', $mapping['groups']);
    }

    public function testPartialAttributeMappingOverridesSpecificKeysAndPreservesDefaults(): void
    {
        $sso = new SsoLogin();
        $reflection = new \ReflectionClass($sso);
        $prop = $reflection->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue($sso, [
            'attributeMapping' => [
                'username' => 'email',
            ],
        ]);

        $mapping = $sso->getEffectiveAttributeMapping('oidc');

        // Overridden
        $this->assertEquals('email', $mapping['username']);

        // Preserved defaults
        $this->assertEquals('email', $mapping['email']);
        $this->assertEquals('given_name', $mapping['givenName']);
        $this->assertEquals('family_name', $mapping['familyName']);
        $this->assertEquals('organization', $mapping['organization']);
        $this->assertEquals('groups', $mapping['groups']);
    }

    public function testUserDataExtractionWithEffectiveMapping(): void
    {
        $sso = new SsoLogin();
        $reflection = new \ReflectionClass($sso);
        $prop = $reflection->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue($sso, [
            'attributeMapping' => [
                'username' => 'email',
            ],
        ]);

        $extractMethod = $reflection->getMethod('extractUserData');
        $extractMethod->setAccessible(true);

        $attributes = [
            'email' => 'user@example.com',
            'given_name' => 'Jane',
            'family_name' => 'Doe',
        ];

        $mapping = $sso->getEffectiveAttributeMapping('oidc');
        $userData = $extractMethod->invoke($sso, $attributes, $mapping);

        $this->assertEquals('user@example.com', $userData['email']);
        $this->assertEquals('user@example.com', $userData['username']);
        $this->assertEquals('Jane', $userData['givenName']);
        $this->assertEquals('Doe', $userData['familyName']);
    }
}
