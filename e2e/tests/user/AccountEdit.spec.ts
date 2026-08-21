import { test, expect } from '../../fixtures';
import { logout } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/BasePage';

test.describe('User: account edit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change password and name', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('#loginLink')).toContainText('LOGIN');
        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('LOGIN');
        await page.locator('#username').fill('testuser@example.org');
        await page.locator('#passwordInput').fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await page.locator('#myAccountLink').click();

        await page.locator('#nameGiven').fill('My new');
        await page.locator('#nameFamily').fill('name');
        await page.locator('#userPwd').fill('123');
        await page.locator('.userAccountForm [name="save"]').click();
        await expectBootboxDialog(page, /Das Passwort muss mindestens 8 Zeichen/);
        await acceptBootbox(page);

        await page.locator('#userPwd').fill('12345678');
        await page.locator('.userAccountForm [name="save"]').click();
        await expectBootboxDialog(page, /Die beiden Passwörter stimmen nicht überein/);
        await acceptBootbox(page);

        await page.locator('#userPwd2').fill('12345678');
        await page.locator('input[name=emailBlocklist]').check();
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('body')).toContainText('Gespeichert.');

        await logout(page);

        await new ConsultationHomePage(page).open();
        await expect(page.locator('#loginLink')).toContainText('LOGIN');
        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('LOGIN');
        await page.locator('#username').fill('testuser@example.org');
        await page.locator('#passwordInput').fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('body')).toContainText('Falsches Passwort');
        await page.locator('#passwordInput').fill('12345678');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('body')).toContainText('Willkommen!');
        await page.locator('#myAccountLink').click();

        await expect(page.locator('#nameGiven')).toHaveValue('My new');
        await expect(page.locator('#nameFamily')).toHaveValue('name');
        await expect(page.locator('input[name=emailBlocklist]')).toBeChecked();

        await page.locator('input[name=emailBlocklist]').uncheck();

        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('body')).toContainText('Gespeichert.');

        await expect(page.locator('input[name=emailBlocklist]')).not.toBeChecked();
    });
});