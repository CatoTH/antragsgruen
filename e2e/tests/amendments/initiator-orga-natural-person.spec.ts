import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Amendments: InitiatorOrgaNaturalPerson', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('test having only organizations enabled', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();

        await expect(page.locator("input[name='initiatorCanBePerson']")).toBeChecked();
        await expect(page.locator("input[name='initiatorCanBeOrganization']")).toBeChecked();
        await page.locator("input[name='initiatorCanBePerson']").first().uncheck();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await expect(page.locator("input[name='initiatorCanBePerson']")).not.toBeChecked();
        await expect(page.locator("input[name='initiatorCanBeOrganization']")).toBeChecked();

        await logout(page);
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('.personTypeSelector').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#initiatorOrga')).not.toBeAttached();
        await expect(page.locator('#resolutionDate').first()).toBeVisible();
        await page.locator("input[name='tags[]'][value='1']").first().check();
        await page.locator("[name='sections[1]']").first().fill('Orga-Test');
        await page.locator('#resolutionDate').first().fill('09.09.1999');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('#amendmentConfirmForm').first()).toBeVisible();
        await expect(page.locator('.motionTextHolder')).toContainText('09.09.1999');
    });

    test('test having only natural persons enabled', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();

        await page.locator("input[name='initiatorCanBePerson']").first().check();
        await page.locator("input[name='initiatorCanBeOrganization']").first().uncheck();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await logout(page);
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('.personTypeSelector').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#initiatorOrga').first()).toBeVisible();
        await expect(page.locator('#resolutionDate').filter({ visible: true })).toHaveCount(0);
        await page.locator("input[name='tags[]'][value='1']").first().check();
        await page.locator("[name='sections[1]']").first().fill('Person-Test');
        await page.locator('#initiatorPrimaryName').first().fill('Mein Name');
        await page.locator('#initiatorEmail').first().fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('#amendmentConfirmForm').first()).toBeVisible();
        await expect(page.locator('.motionTextHolder')).toContainText('Mein Name');
    });
});