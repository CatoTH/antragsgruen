import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { loginAsProposalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('AdminCanCreateMotions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('check that admins can always create motions', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('.managedUserAccounts input').first().check();
        await page.locator('#consultationSettingsForm [name="save"]').click();

        const adminTypePage = new AdminMotionTypePage(page);
        await new AdminIndexPage(page).open();
        await adminTypePage.open({ motionTypeId: 1 });

        await expect(page.locator('.policyWidgetMotions .userGroupSelect').filter({ visible: true })).toHaveCount(0);
        await page.locator('#typePolicyMotions').first().selectOption('3');
        await page.waitForTimeout(200);
        await expect(page.locator('.policyWidgetMotions .userGroupSelect').first()).toBeVisible();

        await page.evaluate(() => {
            const w = window as any;
            w.document.querySelector('#typePolicyMotionsGroups').selectize.addItem(3);
        });
        expect(
            await page.evaluate(() => {
                const w = window as any;
                return w.document.querySelector('#typePolicyMotionsGroups').selectize.items.length;
            }),
        ).toBe(1);

        await page.locator('#typePolicyAmendments').first().selectOption('2');
        await page.locator('#typePolicyAmendments').first().selectOption('2');
        await adminTypePage.saveForm();

        await page.waitForTimeout(100);
        await expect(page.locator('.policyWidgetMotions .userGroupSelect').first()).toBeVisible();
        expect(
            await page.evaluate(() => {
                const w = window as any;
                return w.document.querySelector('#typePolicyMotionsGroups').selectize.items.length;
            }),
        ).toBe(1);

        await page.locator('#motionListLink').click();
        await page.locator('#newMotionBtn').click();
        await expect(page.locator('.createMotion1').first()).toBeVisible();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.createMotion').filter({ visible: true })).toHaveCount(0);

        await logout(page);
        await loginAsProposalAdmin(page);
        await expect(page.locator('.createMotion').first()).toBeVisible();
    });
});