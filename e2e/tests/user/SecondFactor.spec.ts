import { test, expect } from '../../fixtures';
import { loginAsStdUser, logout } from '../../utils/auth';
import { getTotpCode } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('User: second factor', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('register, login, and remove 2FA', async ({ page, request }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await page.locator('#myAccountLink').click();
        await expect(page.locator('.tfaNotActive')).toBeVisible();
        await expect(page.locator('.secondFactorAdderBody')).toHaveCount(0);
        await page.locator('.btn2FaAdderOpen').click();

        await expect(page.locator('.secondFactorAdderBody')).toBeVisible();
        const src = await page.locator('.tfaqr').getAttribute('src');
        expect(src).toContain('data:image/png;base64,');

        const correct2fa = await getTotpCode(request);
        await page.locator("input[name='set2fa']").fill(correct2fa);
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.tfaActive')).toBeVisible();

        await logout(page);

        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('Login');
        await page.locator('#username').fill('testuser@example.org');
        await page.locator('#passwordInput').fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('.tfaForm')).toBeVisible();
        await page.locator('input[name="2fa"]').fill('1234');
        await page.locator('.tfaForm [type="submit"]').click();
        await expect(page.locator('.tfaError')).toBeVisible();

        await expect(page.locator('.tfaForm')).toBeVisible();
        const correct2faLogin = await getTotpCode(request);
        await page.locator('input[name="2fa"]').fill(correct2faLogin);
        await page.locator('.tfaForm [type="submit"]').click();
        await expect(page.locator('.alert-success')).toBeVisible();

        await page.locator('#myAccountLink').click();
        await expect(page.locator('.tfaActive')).toBeVisible();
        await page.locator('.btn2FaRemoveOpen').click();

        const correct2faRemove = await getTotpCode(request);
        await page.locator("input[name='remove2fa']").fill(correct2faRemove);
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.tfaNotActive')).toBeVisible();
        await logout(page);

        await loginAsStdUser(page);
    });
});