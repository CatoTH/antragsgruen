import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Initiator organization and natural person settings', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('only organizations may submit motions', async ({ page }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });

        await expect(page.locator("input[name='initiatorCanBePerson']")).toBeChecked();
        await expect(page.locator("input[name='initiatorCanBeOrganization']")).toBeChecked();
        await test.step('test having only organizations enabled', async () => {
            await expect(page.locator('.formGroupResolutionDate').first()).toBeVisible();
            await expect(page.locator('.formGroupGender').first()).toBeVisible();

            await page.locator("input[name='initiatorCanBePerson']").first().uncheck();
        });

        await test.step('test having only natural persons enabled', async () => {
            await expect(page.locator('.formGroupResolutionDate').first()).toBeVisible();
            await expect(page.locator('.formGroupGender').filter({ visible: true })).toHaveCount(0);
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await expect(page.locator("input[name='initiatorCanBePerson']")).not.toBeChecked();
            await expect(page.locator("input[name='initiatorCanBeOrganization']")).toBeChecked();

            await logout(page);
            const home = new ConsultationHomePage(page);
            await home.open();
            const createPage = await home.gotoMotionCreatePage();

            await expect(page.locator('.personTypeSelector').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#initiatorOrga')).toHaveCount(0);
            await expect(page.locator('#resolutionDate').first()).toBeVisible();

            await createPage.fillInValidSampleData('Orga-Test');
            await page.locator('#resolutionDate').first().fill('09.09.1999');
            await page.locator('#motionEditForm [name="save"]').click();

            await expect(page.locator('#motionConfirmForm').first()).toBeVisible();
            await expect(page.locator('.motionTextHolder')).toContainText('09.09.1999');
        });
    });

    test('only natural persons may submit motions', async ({ page }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });
        await page.locator("input[name='initiatorCanBePerson']").first().uncheck();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await motionType.open({ motionTypeId: 1 });
        await expect(page.locator("input[name='initiatorCanBePerson']")).not.toBeChecked();
        await page.locator("input[name='initiatorCanBeOrganization']").first().uncheck();
        await expect(page.locator("input[name='initiatorCanBePerson']")).toBeChecked();
        await expect(page.locator('.formGroupResolutionDate').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.formGroupGender').first()).toBeVisible();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();

        await expect(page.locator('.personTypeSelector').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#initiatorOrga').first()).toBeVisible();
        await expect(page.locator('#resolutionDate').filter({ visible: true })).toHaveCount(0);

        await createPage.fillInValidSampleData('Person-Test');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('#motionConfirmForm').first()).toBeVisible();
        await expect(page.locator('.motionTextHolder')).toContainText('Mein Name');
    });
});
