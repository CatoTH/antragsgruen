import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: ConsultationPassword', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create a second consultation and set a password for the first one', async ({ page, context }) => {
        await test.step('create a second consultation and set a password for the first one', async () => {
            await page.goto('/stdparteitag/std-parteitag');
            await loginAsStdAdmin(page);
            await page.locator('.siteConsultationsLink').click();
            await page.locator('#newTitle').first().fill('Test3');
            await page.locator('#newShort').first().fill('test3');
            await page.locator('#newPath').first().fill('test3');
            await page
                .locator('.consultationCreateForm [name="createConsultation"]')
                .click();

            await page.locator('#adminLink').click();
            await page.locator('#consultationLink').click();

            await expect(page.locator("input[name='pwdProtected']")).not.toBeChecked();
            await test.step('set a password for the first consultation', async () => {
                await expect(page.locator('.setPasswordHolder').filter({ visible: true })).toHaveCount(0);
                await dispatchClick(page, "input[name='pwdProtected']");
                await expect(page.locator('.setPasswordHolder').first()).toBeVisible();
                await page.locator("input[name='consultationPassword']").first().fill('stdParteitagPwd');
                await page.locator('#consultationSettingsForm [name="save"]').click();
                await expect(page.locator("input[name='pwdProtected']")).toBeChecked();
                await expect(page.locator('.setNewPassword').first()).toBeVisible();
            });
        });

        await test.step('confirm that both consultations have a password set', async () => {
            await logout(page);
            await context.clearCookies();
            await page.goto('/stdparteitag/test3');
            await test.step('change the password for one consultation', async () => {
                await expect(page.locator('h1')).toContainText('Login');
                await expect(page.locator('.loginConPwd').first()).toBeVisible();

                await page.goto('/stdparteitag/std-parteitag');
                await expect(page.locator('h1')).toContainText('Login');
                await expect(page.locator('#conPwdForm').first()).toBeVisible();

                await page.locator('#conpwd').first().fill('stdParteitagWrong');
                await page.locator('#conPwdForm [name="loginconpwd"]').click();
                await expect(page.locator('#conPwdForm .alert-danger').first()).toBeVisible();
                await page.locator('#conpwd').first().fill('stdParteitagPwd');
                await page.locator('#conPwdForm [name="loginconpwd"]').click();
                await expect(page.locator('h1')).toContainText('Test2');
                const cookies = await context.cookies();
                expect(cookies.find((c) => c.name === 'consultationPwd')).toBeDefined();
            });
        });

        await test.step('change the password for the Test3 consultation', async () => {
            await context.clearCookies();
            await page.goto('/stdparteitag/test3');
            await expect(page.locator('h1')).toContainText('Login');

            await expect(page.locator('.loginUsername').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.usernameLoginOpener').first()).toBeVisible();
            await dispatchClick(page, '.usernameLoginOpener button');
            await expect(page.locator('.loginUsername').first()).toBeVisible();
            await page.locator('#username').first().fill('testadmin@example.org');
            await page.locator('#passwordInput').first().fill('testadmin');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await page.locator('#adminLink').click();
            await page.locator('#consultationLink').click();
            await expect(page.locator('.setNewPassword').first()).toBeVisible();
            await dispatchClick(page, '.setNewPassword');
            await expect(page.locator('.setPasswordHolder').first()).toBeVisible();
            await expect(page.locator("input[name='otherConsultations'][value='1']")).toBeChecked();
            await page.locator("input[name='consultationPassword']").first().fill('Test3Pwd');
            await page.locator("input[name='otherConsultations'][value='0']").first().check();
            await page.locator('#consultationSettingsForm [name="save"]').click();
            await logout(page);
        });

        await test.step('confirm both passwords work', async () => {
            await context.clearCookies();
            await page.goto('/stdparteitag/test3');
            await expect(page.locator('h1')).toContainText('Login');
            await expect(page.locator('.loginConPwd').first()).toBeVisible();
            await page.locator('#conpwd').first().fill('Test3Pwd');
            await page.locator('#conPwdForm [name="loginconpwd"]').click();
            await expect(page.locator('h1')).toContainText('Test3');

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('h1')).toContainText('Login');
            await expect(page.locator('.loginConPwd').first()).toBeVisible();
            await page.locator('#conpwd').first().fill('stdParteitagPwd');
            await page.locator('#conPwdForm [name="loginconpwd"]').click();
            await expect(page.locator('h1')).toContainText('Test2');

            await page.goto('/stdparteitag/test3');
            await expect(page.locator('h1')).toContainText('Test3');
        });
    });
});