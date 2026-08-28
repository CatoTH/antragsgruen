import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { EmailChangePage } from '../../pages/EmailChangePage';

test.describe('User: email change', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('set and change email', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await page.locator('#loginLink').click();
        await expect(page.locator('h1')).toContainText('Login');
        await page.locator('#username').fill('noemail@example.org');
        await page.locator('#passwordInput').fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('body')).toContainText('Willkommen!');

        await page.locator('#myAccountLink').click();
        await expect(page.locator('body')).toContainText('Neue E-Mail-Adresse:');
        await expect(page.locator('.emailExistingRow')).toHaveCount(0);
        await expect(page.locator('.emailChangeRow')).toBeVisible();
        await page.locator('#userEmail').fill('noemail@example.org');
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toContainText('an die angegebene Adresse geschickt');
        await expect(page.locator('body')).toContainText('E-mail sent to: noemail@example.org');
        await expect(page.locator('.changeRequested')).toBeVisible();

        const emailChangePage = new EmailChangePage(page);
        await emailChangePage.open({
            subdomain: 'parteitag',
            consultationPath: 'parteitag',
            email: 'kjkjh@example.org',
            code: 'bla',
        });
        await expect(page.locator('body')).toContainText(
            'Diese E-Mail-Änderung wurde nicht beantragt oder bereits durchgeführt.',
        );

        await emailChangePage.open({
            subdomain: 'parteitag',
            consultationPath: 'parteitag',
            email: 'noemail@example.org',
            code: 'bla',
        });
        await expect(page.locator('body')).toContainText('Der angegebene Code stimmt leider nicht.');

        await expect(page.locator('.resendButton')).toBeVisible();
        await page.locator('.userAccountForm [name="resendEmailChange"]').click();
        await expect(page.locator('.alert-danger')).toContainText('5 Minuten');

        await emailChangePage.open({
            subdomain: 'parteitag',
            consultationPath: 'parteitag',
            email: 'noemail@example.org',
            code: 'testCode',
        });
        await expect(page.locator('body')).toContainText('Die E-Mail-Adresse wurde wie gewünscht geändert.');
        await expect(page.locator('body')).not.toContainText('Neue E-Mail-Adresse:');
        await expect(page.locator('.emailExistingRow')).toBeVisible();
        await expect(page.locator('.emailChangeRow')).toHaveCount(0);

        await expect(page.locator('body')).not.toContainText('Neue E-Mail-Adresse:');
        await expect(page.locator('.emailExistingRow')).toBeVisible();
        await expect(page.locator('.changeRequested')).toHaveCount(0);
        await expect(page.locator('.emailChangeRow')).toHaveCount(0);

        await page.locator('.requestEmailChange').click();
        await expect(page.locator('.emailExistingRow')).toHaveCount(0);
        await expect(page.locator('.emailChangeRow')).toBeVisible();
        await page.locator('#userEmail').fill('noemail2@example.org');
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toContainText('an die angegebene Adresse geschickt');
        await expect(page.locator('body')).toContainText('E-mail sent to: noemail2@example.org');
        await expect(page.locator('.changeRequested')).toContainText('noemail2@example.org');

        await emailChangePage.open({
            subdomain: 'parteitag',
            consultationPath: 'parteitag',
            email: 'noemail2@example.org',
            code: 'testCode',
        });
        await expect(page.locator('body')).toContainText('Die E-Mail-Adresse wurde wie gewünscht geändert.');
        await expect(page.locator('body')).not.toContainText('Neue E-Mail-Adresse:');
        await expect(page.locator('.emailExistingRow')).toContainText('noemail2@example.org');
        await expect(page.locator('body')).not.toContainText('noemail@example.org');
        await expect(page.locator('.changeRequested')).toHaveCount(0);
        await expect(page.locator('.emailChangeRow')).toHaveCount(0);
    });
});