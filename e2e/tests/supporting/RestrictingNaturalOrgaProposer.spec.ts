import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, loginAsProposalAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { dispatchClick } from '../../utils/dom';

test.describe('Supporting: RestrictingNaturalOrgaProposer', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('restrict submission as natural person / organization', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });

        await test.step('restrict submission as natural person / organization is some strange way', async () => {
            await dispatchClick(page, '#sameInitiatorSettingsForAmendments input');
            await expect(page.locator('#motionSupportersForm .policyWidgetPerson').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#motionSupportersForm .policyWidgetOrga').filter({ visible: true })).toHaveCount(0);

            await dispatchClick(page, '#motionSupportersForm .initiatorSetPermissions input');
            await expect(page.locator('#motionSupportersForm .policyWidgetPerson').first()).toBeVisible();
            await expect(page.locator('#motionSupportersForm .policyWidgetOrga').first()).toBeVisible();

            await dispatchClick(page, '#motionSupportersForm .initiatorCanBePerson input');
            await expect(page.locator('#motionSupportersForm .policyWidgetPerson').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#motionSupportersForm .policyWidgetOrga').first()).toBeVisible();
            await page.locator('#typeInitiatorOrgaPolicy').first().selectOption('3');

            await dispatchClick(page, '#amendmentSupportersForm .initiatorSetPermissions input');
            await page.locator('#typeAmendmentInitiatorOrgaPolicy').first().selectOption('4');
            await expect(page.locator('#amendmentSupportersForm .policyWidgetOrga .userGroupSelect').first()).toBeVisible();

            await page.evaluate(() => {
                (document.querySelector('#typeAmendmentInitiatorOrgaGroups') as any).selectize.addItem(3);
            });
            const itemsLen1 = await page.evaluate(() => {
                return (document.querySelector('#typeAmendmentInitiatorOrgaGroups') as any).selectize.items.length;
            });
            expect(itemsLen1).toBe(1);

            await page.locator('.adminTypeForm [name="save"]').first().click();
            const itemsLen2 = await page.evaluate(() => {
                return (document.querySelector('#typeAmendmentInitiatorOrgaGroups') as any).selectize.items.length;
            });
            expect(itemsLen2).toBe(1);

            await home.open();
            await home.gotoMotionCreatePage();
        });

        await test.step('only be able to submit motions as organization as admin', async () => {
            await expect(page.locator('.personTypeSelector').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.initiatorData .only-organization').first()).toBeVisible();
            await expect(page.locator('.initiatorData .only-person').filter({ visible: true })).toHaveCount(0);
            await logout(page);

            await loginAsStdUser(page);
            await home.gotoMotionCreatePage();
        });

        await test.step('not be able to submit motions as regular user, only as person for amendments', async () => {
            await expect(page.locator('.personTypeSelector').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.noProposerTypeFoundError').first()).toBeVisible();

            await home.gotoAmendmentCreatePage();
        });

        await test.step('as proposed procedure admin, both options are available for amendments', async () => {
            await expect(page.locator('.personTypeSelector').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.initiatorData .only-person').first()).toBeVisible();
            await expect(page.locator('.initiatorData .only-organization').filter({ visible: true })).toHaveCount(0);
            await logout(page);

            await loginAsProposalAdmin(page);
            await home.gotoAmendmentCreatePage();
            await expect(page.locator('.personTypeSelector').first()).toBeVisible();
            await expect(page.locator('.initiatorData .only-person').first()).toBeVisible();
            await expect(page.locator('.initiatorData .only-organization').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '#personTypeOrga');
            await expect(page.locator('.initiatorData .only-person').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.initiatorData .only-organization').first()).toBeVisible();
        });
    });
});