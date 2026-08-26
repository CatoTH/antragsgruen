import { test, expect } from '../../fixtures';
import { LoginPage } from '../../pages/LoginPage';
import { PasswordRecoveryPage } from '../../pages/PasswordRecoveryPage';

test.describe('User: password recovery', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('recover password', async ({ page }) => {
        await test.step('go to the recovery page', async () => {
            const loginPage = new LoginPage(page);
            await loginPage.open({
                subdomain: 'stdparteitag',
                consultationPath: 'std-parteitag',
            });
        });

        await test.step('Load the login page', async () => {
            await expect(page.locator('h1')).toContainText('Login');
            await page.locator('#usernamePasswordForm .passwordRecovery a').click();
        });

        await test.step('recover the password for a non-existant account', async () => {
            await page.locator('#sendEmail').first().fill('invalid@example.org');
            await page.locator('.sendConfirmationForm [name="send"]').click();
            await expect(page.locator('body')).toContainText('Der Account invalid@example.org wurde nicht gefunden.');
        });

        await page.locator('#sendEmail').first().fill('testuser@example.org');
        await page.locator('.sendConfirmationForm [name="send"]').click();

        await expect(page.locator('body')).toContainText(
            'Dir wurde eine Passwort-Wiederherstellungs-Mail geschickt.',
        );

        const recoveryPage = new PasswordRecoveryPage(page);
        await recoveryPage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
            email: 'testuser@example.org',
            code: 'test',
        });
        await test.step('request another recovery', async () => {
            await page.locator('#sendEmail').first().fill('testuser@example.org');
            await page.locator('.sendConfirmationForm [name="send"]').click();

            await expect(page.locator('body')).toContainText(
                'Es wurde bereits eine Wiederherstellungs-E-Mail in den letzten 24 Stunden verschickt.',
            );

            await recoveryPage.open({
                subdomain: 'stdparteitag',
                consultationPath: 'std-parteitag',
                email: 'testuser@example.org',
                code: 'test',
            });
        });

        await test.step('confirm the e-mail', async () => {
            await expect(page.locator('#recoveryEmail')).toHaveValue('testuser@example.org');
            await expect(page.locator('#recoveryCode')).toHaveValue('test');
        });

        await test.step('confirm the e-mail again', async () => {
            await page.locator('#recoveryPassword').first().fill('testpwd2');
            await page.locator('.resetPasswortForm [name="recover"]').click();

            await expect(page.locator('body')).toContainText('Alles klar! Dein Passwort wurde geändert.');

            await recoveryPage.open({
                subdomain: 'stdparteitag',
                consultationPath: 'std-parteitag',
                email: 'testuser@example.org',
                code: 'test',
            });
            await page.locator('#recoveryPassword').first().fill('testpwd2');
            await page.locator('.resetPasswortForm [name="recover"]').click();
            await expect(page.locator('body')).toContainText(
                'Es wurde kein Wiederherstellungs-Antrag innerhalb der letzten 24 Stunden gestellt.',
            );
        });

    });
});