import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, loginAsProposalAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Supporting: RestrictingNaturalOrgaProposer', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('restrict submission as natural person / organization', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });

        await page.evaluate(() => {
            const chkbox = document.querySelector('#sameInitiatorSettingsForAmendments input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await expect(page.locator('#motionSupportersForm .policyWidgetPerson')).toHaveCount(0);
        await expect(page.locator('#motionSupportersForm .policyWidgetOrga')).toHaveCount(0);

        await page.evaluate(() => {
            const chkbox = document.querySelector('#motionSupportersForm .initiatorSetPermissions input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await expect(page.locator('#motionSupportersForm .policyWidgetPerson')).toBeVisible();
        await expect(page.locator('#motionSupportersForm .policyWidgetOrga')).toBeVisible();

        await page.evaluate(() => {
            const chkbox = document.querySelector('#motionSupportersForm .initiatorCanBePerson input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await expect(page.locator('#motionSupportersForm .policyWidgetPerson')).toHaveCount(0);
        await expect(page.locator('#motionSupportersForm .policyWidgetOrga')).toBeVisible();
        await page.locator('#typeInitiatorOrgaPolicy').selectOption('3');

        await page.evaluate(() => {
            const chkbox = document.querySelector('#amendmentSupportersForm .initiatorSetPermissions input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await page.locator('#typeAmendmentInitiatorOrgaPolicy').selectOption('4');
        await expect(page.locator('#amendmentSupportersForm .policyWidgetOrga .userGroupSelect')).toBeVisible();

        await page.evaluate(() => {
            (document.querySelector('#typeAmendmentInitiatorOrgaGroups') as any).selectize.addItem(3);
        });
        const itemsLen1 = await page.evaluate(() => {
            return (document.querySelector('#typeAmendmentInitiatorOrgaGroups') as any).selectize.items.length;
        });
        expect(itemsLen1).toBe(1);

        await motionTypePage.saveForm();
        const itemsLen2 = await page.evaluate(() => {
            return (document.querySelector('#typeAmendmentInitiatorOrgaGroups') as any).selectize.items.length;
        });
        expect(itemsLen2).toBe(1);

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await expect(page.locator('.personTypeSelector')).toHaveCount(0);
        await expect(page.locator('.initiatorData .only-organization')).toBeVisible();
        await expect(page.locator('.initiatorData .only-person')).toHaveCount(0);
        await logout(page);

        await loginAsStdUser(page);
        await new ConsultationHomePage(page).open();
        await home.gotoMotionCreatePage();
        await expect(page.locator('.personTypeSelector')).toHaveCount(0);
        await expect(page.locator('.noProposerTypeFoundError')).toBeVisible();

        await new ConsultationHomePage(page).open();
        await home.gotoAmendmentCreatePage();
        await expect(page.locator('.personTypeSelector')).toHaveCount(0);
        await expect(page.locator('.initiatorData .only-person')).toBeVisible();
        await expect(page.locator('.initiatorData .only-organization')).toHaveCount(0);
        await logout(page);

        await loginAsProposalAdmin(page);
        await new ConsultationHomePage(page).open();
        await home.gotoAmendmentCreatePage();
        await expect(page.locator('.personTypeSelector')).toBeVisible();
        await expect(page.locator('.initiatorData .only-person')).toBeVisible();
        await expect(page.locator('.initiatorData .only-organization')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('#personTypeOrga') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.initiatorData .only-person')).toHaveCount(0);
        await expect(page.locator('.initiatorData .only-organization')).toBeVisible();
    });
});