import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionPage } from '../../pages/AdminMotionPage';
import { AdminAmendmentPage } from '../../pages/AdminAmendmentPage';
import { FIRST_FREE_USERGROUP_ID } from '../../utils/constants';

test.describe('Useradmin: RestrictedPrivileges', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('restricted privileges with restricted group', async ({ page }) => {
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await page.evaluate(() => {
            const btn = document.querySelector('.btnGroupCreate') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.addGroupForm')).toBeVisible();
        await page.locator('.addGroupForm .addGroupName input').fill('General group');
        await page.evaluate(() => {
            const btn = document.querySelector('.addGroupForm .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText('General group');

        await page.evaluate(() => {
            const btn = document.querySelector(`.group${FIRST_FREE_USERGROUP_ID} .btnEdit`) as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editGroupModal header')).toContainText('General group');
        await expect(page.locator('.editGroupModal .inputGroupTitle')).toHaveValue('General group');
        await page.locator('.editGroupModal .inputGroupTitle').fill('Restricted Group');
        await expect(page.locator('.addRestrictedPermissionDialog')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.editGroupModal .btnAddRestrictedPermission') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.addRestrictedPermissionDialog')).toBeVisible();
        await expect(page.locator('.editGroupModal .inputGroupTitle')).toHaveCount(0);
        await expect(page.locator('.editGroupModal .restrictedTo .stdDropdown.tags')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.editGroupModal .restrictedPermissions .privilege4 input') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editGroupModal .restrictedTo .tag input') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editGroupModal .restrictedTo .stdDropdown.tags')).toBeVisible();
        await page.locator('.editGroupModal .restrictedTo .stdDropdown.tags').selectOption('1');
        await page.evaluate(() => {
            const btn = document.querySelector('.editGroupModal .btnAdd') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editGroupModal .restrictedPrivilegeList')).toContainText('Rahmendaten bearbeiten');
        await expect(page.locator('.editGroupModal .restrictedPrivilegeList')).toContainText('Umwelt');
        await page.evaluate(() => {
            const btn = document.querySelector('.editGroupModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText('Restricted Group');
        await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText(
            'Umwelt: Rahmendaten bearbeiten',
        );

        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await page.locator('.addSingleInit .inputEmail').fill('testuser@example.org');
        await page.evaluate(() => {
            const btn = document.querySelector('.addUsersOpener.singleuser') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.addUsersByLogin.singleuser .showIfExists')).toBeVisible();
        await expect(page.locator('.addUsersByLogin.singleuser .showIfNew')).toHaveCount(0);
        await page.locator(`.addUsersByLogin.singleuser .userGroup${FIRST_FREE_USERGROUP_ID}`).check();
        await page.locator('.addUsersByLogin.singleuser .userGroup4').uncheck();
        await page.locator('.addUsersByLogin.singleuser [name="addUsers"]').click();
        await expect(page.locator('.user2')).toContainText('Restricted Group');

        await page.evaluate(() => {
            const btn = document.querySelector(`.group${FIRST_FREE_USERGROUP_ID} .btnEdit`) as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.locator('.editGroupModal .changeLogLink').click();
        await expect(page.locator('body')).toContainText(
            'testuser@example.org wurde der Gruppe „Restricted Group” hinzugefügt.',
        );

        await new ConsultationHomePage(page).open();
        await logout(page);
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#username').fill('testuser@example.org');
        await page.locator('#passwordInput').fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await page.locator('#motionListLink').click();
        await expect(page.locator('.actionCol')).toHaveCount(0);
        await expect(page.locator('.motion2 .titleCol a')).toHaveCount(0);
        await expect(page.locator('.motion3 .titleCol a')).toBeVisible();
        await page.locator('.motion3 .titleCol a').click();
        await page.locator('#motionTitle').fill('Formatted text');
        await page.locator('#motionNoteInternal').fill('Some internal notes');
        await expect(page.locator('#motionTextEditCaller')).toHaveCount(0);
        await expect(page.locator('#motionTextEditHolder')).toHaveCount(0);
        await expect(page.locator('.supporterForm')).toHaveCount(0);
        await expect(page.locator('#motionSupporterHolder')).toHaveCount(0);
        await page.locator('#motionUpdateForm [name="save"]').click();
        await expect(page.locator('#motionTitle')).toHaveValue('Formatted text');
        await expect(page.locator('#motionNoteInternal')).toHaveValue('Some internal notes');
        await page.locator('#sidebar .view').click();
        await expect(page.locator('h1')).toContainText('Formatted text');
        await expect(page.locator('.motionDataTable')).toContainText('Testadmin');
        await expect(page.locator('span.underline')).toContainText('unterstrichen');

        await page.locator('#motionListLink').click();
        await expect(page.locator('.amendment1 .titleCol a')).toHaveCount(0);
        await expect(page.locator('.amendment2 .titleCol a')).toBeVisible();
        await page.locator('.amendment2 .titleCol a').click();
        await page.locator('#amendmentNoteInternal').fill('Some internal notes');
        await expect(page.locator('.amendmentTextEditCaller')).toHaveCount(0);
        await expect(page.locator('.amendmentTextEditHolder')).toHaveCount(0);
        await expect(page.locator('.supporterForm')).toHaveCount(0);
        await expect(page.locator('#motionSupporterHolder')).toHaveCount(0);
        await page.locator('#amendmentUpdateForm [name="save"]').click();
        await expect(page.locator('#amendmentNoteInternal')).toHaveValue('Some internal notes');
        await page.locator('#sidebar .view').click();
        await expect(page.locator('.motionDataTable')).toContainText('Testuser');
        await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile gq Q.');
    });
});