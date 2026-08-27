import { test, expect } from '../../fixtures';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { LoginPage } from '../../pages/LoginPage';

test.describe('User: account creation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create an account', async ({ page }) => {
        await test.step('Load the login page', async () => {
            const loginPage = new LoginPage(page);
            await loginPage.open({
                subdomain: 'stdparteitag',
                consultationPath: 'std-parteitag',
            });
            await expect(page.locator('h1')).toContainText('Login');

            await page.locator('#username').first().fill('non_existant@example.org');
            await page.locator('#passwordInput').first().fill('doesntmatter');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expect(page.locator('body')).toContainText('Benutzer*innenname nicht gefunden');

            await expect(page.locator('body')).not.toContainText('Passwort (Bestätigung):', { useInnerText: true });
            await page.locator('#createAccount').first().check();
            await expect(page.locator('body')).toContainText('Passwort (Bestätigung):');

            await page.locator('#username').first().fill('testaccount@example.org');
            await page.locator('#name').first().fill('Tester');

            await page.locator('#passwordInput').first().fill('n');
            await page.locator('#passwordConfirm').first().fill('n');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expectBootboxDialog(page, /Das Passwort muss mindestens 8 Zeichen lang sein/);
            await acceptBootbox(page);

            await page.locator('#passwordInput').first().fill('newuser1');
            await page.locator('#passwordConfirm').first().fill('newuser2');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expectBootboxDialog(page, /Die beiden Passwörter stimmen nicht überein/);
            await acceptBootbox(page);

            await page.locator('#passwordInput').first().fill('testpassword');
            await page.locator('#passwordConfirm').first().fill('testpassword');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expect(page.locator('h1')).toContainText(/zugang bestätigen/i);
        });

        await test.step('Confirm the account with a wrong code', async () => {
            await page.locator('#code').first().fill('somethingcompletelywrong');
            await page.locator('#confirmAccountForm [type="submit"]').click();

            await expect(page.locator('h1')).toContainText(/zugang bestätigen/i);
            await expect(page.locator('body')).toContainText('Der angegebene Code stimmt leider nicht.');
        });

        await test.step('Confirm the account with the correct code', async () => {
            await page.locator('#code').first().fill('testCode');
            await page.locator('#confirmAccountForm [type="submit"]').click();
            await expect(page.locator('h1')).toContainText(/zugang bestätigt/i);
        });
    });
});