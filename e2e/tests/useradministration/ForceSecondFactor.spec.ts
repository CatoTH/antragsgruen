import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, logout } from '../../utils/auth';
import { getTotpCode } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/BasePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: ForceSecondFactor', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('force second factor setup', async ({ page, request }) => {
        await new ConsultationHomePage(page).open();

        await page.goto('/stdparteitag/std-parteitag/admin/index');
        await page.locator('#username').fill('globaladmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('.siteUsers').click();
        await page.evaluate(() => {
            const btn = document.querySelector('.user1 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.force2FaHolder input')).not.toBeChecked();
        await page.evaluate(() => {
            const chkbox = document.querySelector('.force2FaHolder input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await expect(page.locator('.force2FaHolder input')).toBeChecked();
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await logout(page);
        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('LOGIN');
        await page.locator('#username').fill('testadmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('.forcedTfaForm')).toBeVisible();
        const src = await page.locator('.tfaqr').getAttribute('src');
        expect(src).toContain('data:image/png;base64,');

        const correct2fa = await getTotpCode(request);
        await page.locator("input[name='set2fa']").fill(correct2fa);
        await page.locator('.forcedTfaForm [type="submit"]').click();

        await expect(page.locator('.alert-success')).toBeVisible();

        await page.locator('#myAccountLink').click();
        await expect(page.locator('.tfaRow .glyphicon-ok')).toBeVisible();
        await expect(page.locator('.btn2FaRemoveOpen')).toHaveCount(0);
    });
});