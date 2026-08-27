import { test, expect } from '../../fixtures';
import { loginAsStdUser, logout } from '../../utils/auth';
import { getTotpCode } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { dispatchClick } from '../../utils/dom';

test.describe('User: second factor', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('register, login, and remove 2FA', async ({ page, request }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await page.locator('#myAccountLink').click();
        await expect(page.locator('.tfaNotActive').first()).toBeVisible();
        await expect(page.locator('.secondFactorAdderBody').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.btn2FaAdderOpen');

        await expect(page.locator('.secondFactorAdderBody').first()).toBeVisible();
        const src = await page.locator('.tfaqr').getAttribute('src');
        expect(src).toContain('data:image/png;base64,');

        const correct2fa = await getTotpCode(request);
        await page.locator("input[name='set2fa']").first().fill(correct2fa);
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.tfaActive').first()).toBeVisible();

        await logout(page);

        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('Login');
        await page.locator('#username').first().fill('testuser@example.org');
        await page.locator('#passwordInput').first().fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('.tfaForm').first()).toBeVisible();
        await page.locator('input[name="2fa"]').first().fill('1234');
        await page.locator('.tfaForm [type="submit"]').click();
        await expect(page.locator('.tfaError').first()).toBeVisible();

        await expect(page.locator('.tfaForm').first()).toBeVisible();
        const correct2faLogin = await getTotpCode(request);
        await page.locator('input[name="2fa"]').first().fill(correct2faLogin);
        await page.locator('.tfaForm [type="submit"]').click();
        await expect(page.locator('.alert-success').first()).toBeVisible();

        await page.locator('#myAccountLink').click();
        await expect(page.locator('.tfaActive').first()).toBeVisible();
        await dispatchClick(page, '.btn2FaRemoveOpen');

        const correct2faRemove = await getTotpCode(request);
        await page.locator("input[name='remove2fa']").first().fill(correct2faRemove);
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.tfaNotActive').first()).toBeVisible();
        await logout(page);

        await loginAsStdUser(page);
    });
});