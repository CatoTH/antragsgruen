import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, logout } from '../../utils/auth';
import { getTotpCode } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: ForceSecondFactor', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('force second factor setup', async ({ page, request }) => {
        await new ConsultationHomePage(page).open();
        await loginAsGlobalAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin');
        await page.locator('.siteUsers').click();
        await page.locator('.user1').waitFor({ timeout: 10_000 });
        await page.locator('.user1 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.force2FaHolder input')).not.toBeChecked();
        await page.locator('.force2FaHolder input').check();
        await expect(page.locator('.force2FaHolder input')).toBeChecked();
        await page.locator('.editUserModal .btnSave').click();

        await logout(page);
        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('Login');
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