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
        await page.locator("input[name='initiatorCanBePerson']").uncheck();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await expect(page.locator("input[name='initiatorCanBePerson']")).not.toBeChecked();
        await expect(page.locator("input[name='initiatorCanBeOrganization']")).toBeChecked();

        await logout(page);
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('.personTypeSelector')).not.toBeVisible();
        await expect(page.locator('#initiatorOrga')).not.toBeAttached();
        await expect(page.locator('#resolutionDate')).toBeVisible();
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Orga-Test');
        await page.locator('#resolutionDate').fill('09.09.1999');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('#amendmentConfirmForm')).toBeVisible();
        await expect(page.locator('.motionTextHolder')).toContainText('09.09.1999');
    });

    test('test having only natural persons enabled', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();

        await page.locator("input[name='initiatorCanBePerson']").check();
        await page.locator("input[name='initiatorCanBeOrganization']").uncheck();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await logout(page);
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('.personTypeSelector')).not.toBeVisible();
        await expect(page.locator('#initiatorOrga')).toBeVisible();
        await expect(page.locator('#resolutionDate')).not.toBeVisible();
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Person-Test');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('#amendmentConfirmForm')).toBeVisible();
        await expect(page.locator('.motionTextHolder')).toContainText('Mein Name');
    });
});