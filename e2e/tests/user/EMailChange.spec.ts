import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { EmailChangePage } from '../../pages/EmailChangePage';

test.describe('User: email change', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('set and change email', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await test.step('login as a user without e-mail', async () => {
            await page.locator('#loginLink').click();
            await expect(page.locator('h1')).toContainText('Login');
            await page.locator('#username').first().fill('noemail@example.org');
            await page.locator('#passwordInput').first().fill('testuser');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expect(page.locator('body')).toContainText('Willkommen!');
        });

        await page.locator('#myAccountLink').click();
        await expect(page.locator('body')).toContainText('Neue E-Mail-Adresse:');
        await expect(page.locator('.emailExistingRow').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.emailChangeRow').first()).toBeVisible();
        await page.locator('#userEmail').first().fill('noemail@example.org');
        await page.locator('.userAccountForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toContainText('an die angegebene Adresse geschickt');
        await expect(page.locator('body')).toContainText('E-mail sent to: noemail@example.org');
        await expect(page.locator('.changeRequested').first()).toBeVisible();

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

        await test.step('resend the code', async () => {
            await expect(page.locator('.resendButton').first()).toBeVisible();
            await page.locator('.userAccountForm [name="resendEmailChange"]').click();
            await expect(page.locator('.alert-danger')).toContainText('5 Minuten');

            await emailChangePage.open({
                subdomain: 'parteitag',
                consultationPath: 'parteitag',
                email: 'noemail@example.org',
                code: 'testCode',
            });
        });

        await test.step('confirm the previous mail', async () => {
            await expect(page.locator('body')).toContainText('Die E-Mail-Adresse wurde wie gewünscht geändert.');
        });

        await test.step('change it again', async () => {
            await expect(page.locator('body')).not.toContainText('Neue E-Mail-Adresse:', { useInnerText: true });
            await expect(page.locator('.emailExistingRow').first()).toBeVisible();
            await expect(page.locator('.emailChangeRow').filter({ visible: true })).toHaveCount(0);

            await expect(page.locator('body')).not.toContainText('Neue E-Mail-Adresse:', { useInnerText: true });
            await expect(page.locator('.emailExistingRow').first()).toBeVisible();
            await expect(page.locator('.changeRequested').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.emailChangeRow').filter({ visible: true })).toHaveCount(0);

            await page.locator('.requestEmailChange').click();
            await expect(page.locator('.emailExistingRow').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.emailChangeRow').first()).toBeVisible();
            await page.locator('#userEmail').first().fill('noemail2@example.org');
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
            await expect(page.locator('body')).not.toContainText('Neue E-Mail-Adresse:', { useInnerText: true });
            await expect(page.locator('.emailExistingRow')).toContainText('noemail2@example.org');
            await expect(page.locator('body')).not.toContainText('noemail@example.org', { useInnerText: true });
            await expect(page.locator('.changeRequested').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.emailChangeRow').filter({ visible: true })).toHaveCount(0);
        });

    });
});