import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Manager: create congress with applications', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('wizard: create congress with applications', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await loginAsStdAdmin(page);

        await expect(page.locator('.siteCreateForm')).toBeVisible();
        await page.locator('.siteCreateForm [type="submit"]').click();

        await expect(page.locator('#panelFunctionality')).toContainText(
            'Welche Bestandteile soll die Seite haben?',
        );
        await expect(page.locator('.checkbox-label.value-motion.active')).toBeVisible();
        await expect(page.locator('.checkbox-label.value-applications.active')).toHaveCount(0);
        await page.locator('.checkbox-label.value-motion').click();
        await page.locator('.checkbox-label.value-applications').click();
        await page.waitForTimeout(200);
        await expect(page.locator('.checkbox-label.value-motion.active')).toHaveCount(0);
        await expect(page.locator('.checkbox-label.value-applications.active')).toBeVisible();
        await page.locator('#panelFunctionality button.btn-next').click();

        await page.locator('#panelApplicationType .value-2').click();
        await page.locator('#panelApplicationType button.btn-next').click();

        await page.locator('#panelOpenNow .value-0').click();
        await page.locator('#panelOpenNow button.btn-next').click();

        await page.locator('#siteTitle').fill('Test-Congress');
        await page.locator('#siteOrganization').fill('My party');
        await expect(page.locator('.subdomainError')).toHaveCount(0);
        await page.locator('#siteSubdomain').fill('stdparteitag');
        await page.locator('#siteSubdomain').dispatchEvent('change');
        await page.waitForTimeout(500);
        await expect(page.locator('.subdomainError')).toBeVisible();
        await expect(page.locator('.subdomainError')).toContainText('stdparteitag');
        await page.locator('#siteSubdomain').fill('testcongress');
        await page.locator('#siteSubdomain').dispatchEvent('change');
        await page.waitForTimeout(500);
        await page.locator('#siteContact').fill('I myself\nMy address');

        await page.locator('form.siteCreate [name="create"]').click();

        await expect(page.locator('body')).toContainText('Die Veranstaltung wurde angelegt.');

        await page.locator('.createdForm [type="submit"]').click();

        await expect(page.locator('body')).toContainText('Hallo auf Antragsgrün');
        await expect(page.locator('h1')).toContainText('Test-Congress');

        await expect(page.locator('#sidebar .createMotion')).toContainText('Bewerben');
        await page.locator('#sidebar .createMotion').click();
        await expect(page.locator('.type5')).toBeVisible();

        await logout(page);

        await expect(page.locator('body')).not.toContainText('Hallo auf Antragsgrün');
        await expect(page.locator('h1')).not.toContainText('Test-Congress');
        await expect(page.locator('h1')).toContainText('Wartungsmodus');

        await page.goto('/testcongress/testcongress');
        await page.locator('#legalLink').click();
        await expect(page.locator('body')).toContainText('I myself');
        await expect(page.locator('body')).toContainText('My address');
    });
});
