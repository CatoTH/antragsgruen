import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: ConsultationPassword', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create a second consultation and set a password for the first one', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.locator('.siteConsultationsLink').click();
        await page.locator('#newTitle').fill('Test3');
        await page.locator('#newShort').fill('test3');
        await page.locator('#newPath').fill('test3');
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();

        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();

        await expect(page.locator("input[name='pwdProtected']")).not.toBeChecked();
        await expect(page.locator('.setPasswordHolder')).not.toBeVisible();
        await dispatchClick(page, "input[name='pwdProtected']");
        await expect(page.locator('.setPasswordHolder')).toBeVisible();
        await page.locator("input[name='consultationPassword']").fill('stdParteitagPwd');
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator("input[name='pwdProtected']")).toBeChecked();
        await expect(page.locator('.setNewPassword')).toBeVisible();
    });

    test('confirm that both consultations have a password set', async ({ page, context }) => {
        await logout(page);
        await context.clearCookies();
        await page.goto('/stdparteitag/test3');
        await expect(page.locator('h1')).toContainText('Login');
        await expect(page.locator('.loginConPwd')).toBeVisible();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).toContainText('Login');
        await expect(page.locator('#conPwdForm')).toBeVisible();

        await page.locator('#conpwd').fill('stdParteitagWrong');
        await page.locator('#conPwdForm [name="loginconpwd"]').click();
        await expect(page.locator('#conPwdForm .alert-danger')).toBeVisible();
        await page.locator('#conpwd').fill('stdParteitagPwd');
        await page.locator('#conPwdForm [name="loginconpwd"]').click();
        await expect(page.locator('h1')).toContainText('Test2');
        const cookies = await context.cookies();
        expect(cookies.find((c) => c.name === 'consultationPwd')).toBeDefined();
    });

    test('change the password for the Test3 consultation', async ({ page, context }) => {
        await context.clearCookies();
        await page.goto('/stdparteitag/test3');
        await expect(page.locator('h1')).toContainText('Login');

        await expect(page.locator('.loginUsername')).not.toBeVisible();
        await expect(page.locator('.usernameLoginOpener')).toBeVisible();
        await dispatchClick(page, '.usernameLoginOpener button');
        await expect(page.locator('.loginUsername')).toBeVisible();
        await page.locator('#username').fill('testadmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await expect(page.locator('.setNewPassword')).toBeVisible();
        await dispatchClick(page, '.setNewPassword');
        await expect(page.locator('.setPasswordHolder')).toBeVisible();
        await expect(page.locator("input[name='otherConsultations'][value='1']")).toBeChecked();
        await page.locator("input[name='consultationPassword']").fill('Test3Pwd');
        await page.locator("input[name='otherConsultations'][value='0']").check();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await logout(page);
    });

    test('confirm both passwords work', async ({ page, context }) => {
        await context.clearCookies();
        await page.goto('/stdparteitag/test3');
        await expect(page.locator('h1')).toContainText('Login');
        await expect(page.locator('.loginConPwd')).toBeVisible();
        await page.locator('#conpwd').fill('Test3Pwd');
        await page.locator('#conPwdForm [name="loginconpwd"]').click();
        await expect(page.locator('h1')).toContainText('Test3');

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).toContainText('Login');
        await expect(page.locator('.loginConPwd')).toBeVisible();
        await page.locator('#conpwd').fill('stdParteitagPwd');
        await page.locator('#conPwdForm [name="loginconpwd"]').click();
        await expect(page.locator('h1')).toContainText('Test2');

        await page.goto('/stdparteitag/test3');
        await expect(page.locator('h1')).toContainText('Test3');
    });
});