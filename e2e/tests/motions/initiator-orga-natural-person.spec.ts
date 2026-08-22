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
        await expect(page.locator('.formGroupResolutionDate')).toBeVisible();
        await expect(page.locator('.formGroupGender')).toBeVisible();

        await page.locator("input[name='initiatorCanBePerson']").uncheck();
        await expect(page.locator('.formGroupResolutionDate')).toBeVisible();
        await expect(page.locator('.formGroupGender')).toHaveCount(0);
        await motionType.saveForm();

        await expect(page.locator("input[name='initiatorCanBePerson']")).not.toBeChecked();
        await expect(page.locator("input[name='initiatorCanBeOrganization']")).toBeChecked();

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();

        await expect(page.locator('.personTypeSelector')).toHaveCount(0);
        await expect(page.locator('#initiatorOrga')).toHaveCount(0);
        await expect(page.locator('#resolutionDate')).toBeVisible();

        await createPage.fillInValidSampleData('Orga-Test');
        await page.locator('#resolutionDate').fill('09.09.1999');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('#motionConfirmForm')).toBeVisible();
        await expect(page.locator('.motionTextHolder')).toContainText('09.09.1999');
    });

    test('only natural persons may submit motions', async ({ page }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });
        await page.locator("input[name='initiatorCanBePerson']").uncheck();
        await motionType.saveForm();

        await motionType.open({ motionTypeId: 1 });
        await expect(page.locator("input[name='initiatorCanBePerson']")).not.toBeChecked();
        await page.locator("input[name='initiatorCanBeOrganization']").uncheck();
        await expect(page.locator("input[name='initiatorCanBePerson']")).toBeChecked();
        await expect(page.locator('.formGroupResolutionDate')).toHaveCount(0);
        await expect(page.locator('.formGroupGender')).toBeVisible();
        await motionType.saveForm();

        await logout(page);
        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();

        await expect(page.locator('.personTypeSelector')).toHaveCount(0);
        await expect(page.locator('#initiatorOrga')).toBeVisible();
        await expect(page.locator('#resolutionDate')).toHaveCount(0);

        await createPage.fillInValidSampleData('Person-Test');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('#motionConfirmForm')).toBeVisible();
        await expect(page.locator('.motionTextHolder')).toContainText('Mein Name');
    });
});
