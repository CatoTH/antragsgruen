import { test, expect } from '../../fixtures';
import { logout } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('User: account edit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change password and name', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await test.step('change my password and name', async () => {
            await expect(page.locator('#loginLink')).toContainText('Login');
        });

        await test.step('check that the changes are saved', async () => {
            await page.locator('#loginLink').click();

            await expect(page.locator('h1')).toContainText('Login');
            await page.locator('#username').first().fill('testuser@example.org');
            await page.locator('#passwordInput').first().fill('testuser');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await page.locator('#myAccountLink').click();

            await page.locator('#nameGiven').first().fill('My new');
            await page.locator('#nameFamily').first().fill('name');
            await page.locator('#userPwd').first().fill('123');
            await page.locator('.userAccountForm [name="save"]').click();
            await expectBootboxDialog(page, /Das Passwort muss mindestens 8 Zeichen/);
            await acceptBootbox(page);

            await page.locator('#userPwd').first().fill('12345678');
            await page.locator('.userAccountForm [name="save"]').click();
            await expectBootboxDialog(page, /Die beiden Passwörter stimmen nicht überein/);
            await acceptBootbox(page);

            await page.locator('#userPwd2').first().fill('12345678');
            await page.locator('input[name=emailBlocklist]').first().check();
            await page.locator('.userAccountForm [name="save"]').click();
            await expect(page.locator('body')).toContainText('Gespeichert.');

            await logout(page);

            await new ConsultationHomePage(page).open();
            await expect(page.locator('#loginLink')).toContainText('Login');
            await page.locator('#loginLink').click();

            await expect(page.locator('h1')).toContainText('Login');
            await page.locator('#username').first().fill('testuser@example.org');
            await page.locator('#passwordInput').first().fill('testuser');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await expect(page.locator('body')).toContainText('Falsches Passwort');
            await page.locator('#passwordInput').first().fill('12345678');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await expect(page.locator('body')).toContainText('Willkommen!');
            await page.locator('#myAccountLink').click();

            await expect(page.locator('#nameGiven')).toHaveValue('My new');
            await expect(page.locator('#nameFamily')).toHaveValue('name');
            await expect(page.locator('input[name=emailBlocklist]')).toBeChecked();

            await page.locator('input[name=emailBlocklist]').first().uncheck();

            await page.locator('.userAccountForm [name="save"]').click();
            await expect(page.locator('body')).toContainText('Gespeichert.');

            await expect(page.locator('input[name=emailBlocklist]')).not.toBeChecked();
        });
    });
});