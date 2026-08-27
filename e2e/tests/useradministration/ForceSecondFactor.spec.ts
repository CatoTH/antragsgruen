import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, logout } from '../../utils/auth';
import { getTotpCode } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { dispatchClick } from '../../utils/dom';

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
        await test.step('be able to enforce TOTP as super-admin', async () => {
            await dispatchClick(page, '.user1 .btnEdit');
            await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
            await expect(page.locator('.force2FaHolder input')).not.toBeChecked();
            await page.locator('.force2FaHolder input').first().check();
            await expect(page.locator('.force2FaHolder input')).toBeChecked();
            await dispatchClick(page, '.editUserModal .btnSave');

            await logout(page);
        });

        await test.step('be forced to set up a second factor', async () => {
            await page.locator('#loginLink').click();

            await expect(page.locator('h1')).toContainText('Login');
            await page.locator('#username').first().fill('testadmin@example.org');
            await page.locator('#passwordInput').first().fill('testadmin');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await expect(page.locator('.forcedTfaForm').first()).toBeVisible();
            const src = await page.locator('.tfaqr').getAttribute('src');
            expect(src).toContain('data:image/png;base64,');

            const correct2fa = await getTotpCode(request);
            await page.locator("input[name='set2fa']").first().fill(correct2fa);
            await page.locator('.forcedTfaForm [type="submit"]').click();

            await expect(page.locator('.alert-success').first()).toBeVisible();
        });

        await test.step('disable it again, but cannot', async () => {
            await page.locator('#myAccountLink').click();
            await expect(page.locator('.tfaRow .glyphicon-ok').first()).toBeVisible();
            await expect(page.locator('.btn2FaRemoveOpen').filter({ visible: true })).toHaveCount(0);
        });
    });
});