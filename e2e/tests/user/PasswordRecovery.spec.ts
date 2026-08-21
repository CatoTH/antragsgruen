import { test, expect } from '../../fixtures';
import { LoginPage } from '../../pages/LoginPage';
import { PasswordRecoveryPage } from '../../pages/PasswordRecoveryPage';

test.describe('User: password recovery', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('recover password', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
        });
        await expect(page.locator('h1')).toContainText('Login');
        await page.locator('#usernamePasswordForm .passwordRecovery a').click();

        await page.locator('#sendEmail').fill('invalid@example.org');
        await page.locator('.sendConfirmationForm [name="send"]').click();
        await expect(page.locator('body')).toContainText('Der Account invalid@example.org wurde nicht gefunden.');

        await page.locator('#sendEmail').fill('testuser@example.org');
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
        await page.locator('#sendEmail').fill('testuser@example.org');
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

        await expect(page.locator('#recoveryEmail')).toHaveValue('testuser@example.org');
        await expect(page.locator('#recoveryCode')).toHaveValue('test');
        await page.locator('#recoveryPassword').fill('testpwd2');
        await page.locator('.resetPasswortForm [name="recover"]').click();

        await expect(page.locator('body')).toContainText('Alles klar! Dein Passwort wurde geändert.');

        await recoveryPage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
            email: 'testuser@example.org',
            code: 'test',
        });
        await page.locator('#recoveryPassword').fill('testpwd2');
        await page.locator('.resetPasswortForm [name="recover"]').click();
        await expect(page.locator('body')).toContainText(
            'Es wurde kein Wiederherstellungs-Antrag innerhalb der letzten 24 Stunden gestellt.',
        );
    });
});