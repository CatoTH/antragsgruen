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

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, string> $mapping
     * @return array<string, mixed>
     */
    private function extractUserData(array $attributes, array $mapping): array
    {
        $sso = new SsoLogin();
        $reflection = new \ReflectionClass($sso);
        $extractMethod = $reflection->getMethod('extractUserData');
        $extractMethod->setAccessible(true);

        return $extractMethod->invoke($sso, $attributes, $mapping);
    }

    public function testSingleGroupIsPreservedAsArray(): void
    {
        // A user who is a member of exactly one group must still end up with an array:
        // getOrCreateUser() only syncs groups when they are an array.
        $mapping = (new SsoLogin())->getEffectiveAttributeMapping('oidc');

        $userData = $this->extractUserData(['groups' => ['grp-admin']], $mapping);

        $this->assertSame(['grp-admin'], $userData['groups']);
    }

    public function testMultipleGroupsArePreservedAsArray(): void
    {
        $mapping = (new SsoLogin())->getEffectiveAttributeMapping('oidc');

        $userData = $this->extractUserData(['groups' => ['grp-user', 'grp-admin']], $mapping);

        $this->assertSame(['grp-user', 'grp-admin'], $userData['groups']);
    }

    public function testGroupsReportedAsScalarAreWrappedInArray(): void
    {
        $mapping = (new SsoLogin())->getEffectiveAttributeMapping('oidc');

        $userData = $this->extractUserData(['groups' => 'grp-user'], $mapping);

        $this->assertSame(['grp-user'], $userData['groups']);
    }

    public function testMissingGroupsClaimIsNotAdded(): void
    {
        $mapping = (new SsoLogin())->getEffectiveAttributeMapping('oidc');

        $userData = $this->extractUserData(['email' => 'user@example.com'], $mapping);

        $this->assertArrayNotHasKey('groups', $userData);
    }

    public function testSingleElementScalarAttributesAreStillFlattened(): void
    {
        // SAML providers commonly wrap scalar attributes in a one-element array. That flattening
        // is intentional and must not be affected by the list handling of the groups field.
        $mapping = (new SsoLogin())->getEffectiveAttributeMapping('saml');

        $userData = $this->extractUserData(['mail' => ['user@example.com'], 'givenName' => ['Jane']], $mapping);

        $this->assertSame('user@example.com', $userData['email']);
        $this->assertSame('Jane', $userData['givenName']);
    }
}
