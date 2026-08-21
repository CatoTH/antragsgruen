import { test, expect } from '../../fixtures';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { LoginPage } from '../../pages/LoginPage';

test.describe('User: account creation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create an account', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.open({
            subdomain: 'stdparteitag',
            consultationPath: 'std-parteitag',
        });
        await expect(page.locator('h1')).toContainText('Login');

        await page.locator('#username').fill('non_existant@example.org');
        await page.locator('#passwordInput').fill('doesntmatter');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('body')).toContainText('Benutzer*innenname nicht gefunden');

        await expect(page.locator('body')).not.toContainText('Passwort (Bestätigung):');
        await page.locator('#createAccount').check();
        await expect(page.locator('body')).toContainText('Passwort (Bestätigung):');

        await page.locator('#username').fill('testaccount@example.org');
        await page.locator('#name').fill('Tester');

        await page.locator('#passwordInput').fill('n');
        await page.locator('#passwordConfirm').fill('n');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expectBootboxDialog(page, /Das Passwort muss mindestens 8 Zeichen lang sein/);
        await acceptBootbox(page);

        await page.locator('#passwordInput').fill('newuser1');
        await page.locator('#passwordConfirm').fill('newuser2');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expectBootboxDialog(page, /Die beiden Passwörter stimmen nicht überein/);
        await acceptBootbox(page);

        await page.locator('#passwordInput').fill('testpassword');
        await page.locator('#passwordConfirm').fill('testpassword');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('h1')).toContainText(/zugang bestätigen/i);

        await page.locator('#code').fill('somethingcompletelywrong');
        await page.locator('#confirmAccountForm [type="submit"]').click();

        await expect(page.locator('h1')).toContainText(/zugang bestätigen/i);
        await expect(page.locator('body')).toContainText('Der angegebene Code stimmt leider nicht.');

        await page.locator('#code').fill('testCode');
        await page.locator('#confirmAccountForm [type="submit"]').click();
        await expect(page.locator('h1')).toContainText(/zugang bestätigt/i);
    });
});