import { test, expect } from '../../fixtures';
import { setConfig } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('User: no email confirmation', () => {
    test.beforeEach(async ({ db, request }) => {
        await db.populate('dbdata1');
        await setConfig(request, { confirmEmailAddresses: false });
    });

    test('create account without email confirmation', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await page.locator('#loginLink').click();
        await expect(page.locator('h1')).toContainText('Login');

        await test.step('Create an account', async () => {
            await page.locator('#createAccount').first().check();
            await expect(page.locator('body')).toContainText('Passwort (Bestätigung):');
            await page.locator('#username').first().fill('testaccount@example.org');
            await page.locator('#name').first().fill('Tester');
            await page.locator('#passwordInput').first().fill('testpassword');
            await page.locator('#passwordConfirm').first().fill('testpassword');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await expect(page.locator('h1').getByText(/bestätige deinen zugang/i).filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('h1')).toContainText('Test2');
            await expect(page.locator('.alert-success')).toContainText('Willkommen!');
        });

        await test.step('change the e-mail-address', async () => {
            await page.locator('#myAccountLink').click();
            await page.locator('.requestEmailChange').click();
            await page.locator('#userEmail').first().fill('newmail@example.org');
            await page.locator('.userAccountForm [name="save"]').click();
            await expect(page.locator('.currentEmail')).toContainText('newmail@example.org');
            await expect(page.locator('body')).not.toContainText('unbestätigt', { useInnerText: true });
        });
    });
});