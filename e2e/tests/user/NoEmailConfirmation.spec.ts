import { test, expect } from '../../fixtures';
import { setConfig } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/BasePage';

test.describe('User: no email confirmation', () => {
    test.beforeEach(async ({ db, request }) => {
        await db.populate('dbdata1');
        await setConfig(request, { confirmEmailAddresses: false });
    });

    test('create account without email confirmation', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await page.locator('#loginLink').click();
        await expect(page.locator('h1')).toContainText('Login');

        await page.locator('#createAccount').check();
        await expect(page.locator('body')).toContainText('Passwort (Bestätigung):');
        await page.locator('#username').fill('testaccount@example.org');
        await page.locator('#name').fill('Tester');
        await page.locator('#passwordInput').fill('testpassword');
        await page.locator('#passwordConfirm').fill('testpassword');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('h1')).not.toContainText(/bestätige deinen zugang/i);
        await expect(page.locator('h1')).toContainText('Test2');
        await expect(page.locator('.alert-success')).toContainText('Willkommen!');

        await page.locator('#myAccountLink').click();
        await page.locator('.requestEmailChange').click();
        await page.locator('#userEmail').fill('newmail@example.org');
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.currentEmail')).toContainText('newmail@example.org');
        await expect(page.locator('body')).not.toContainText('unbestätigt');
    });
});