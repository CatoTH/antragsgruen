import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionPage } from '../../pages/AdminMotionPage';
import { AdminAmendmentPage } from '../../pages/AdminAmendmentPage';
import { FIRST_FREE_USERGROUP_ID } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Useradmin: RestrictedPrivileges', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('restricted privileges with restricted group', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await test.step('Create a generic user group', async () => {
            await dispatchClick(page, '.btnGroupCreate');
            await expect(page.locator('.addGroupForm').first()).toBeVisible();
            await page.locator('.addGroupForm .addGroupName input').first().fill('General group');
            await dispatchClick(page, '.addGroupForm .btnSave');
            await page.waitForLoadState('networkidle');
        });

        await test.step('Grant restricted permissions to this group: motion status edit permissions on "Umwelt" tag', async () => {
            await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText('General group');

            await page.locator(`.group${FIRST_FREE_USERGROUP_ID} .btnEdit`).click();
            await expect(page.locator('.editGroupModal header')).toContainText('General group');
            await expect(page.locator('.editGroupModal .inputGroupTitle')).toHaveValue('General group');
            await page.locator('.editGroupModal .inputGroupTitle').first().fill('Restricted Group');
            await expect(page.locator('.addRestrictedPermissionDialog').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.editGroupModal .btnAddRestrictedPermission');
            await expect(page.locator('.addRestrictedPermissionDialog').first()).toBeVisible();
            await expect(page.locator('.editGroupModal .inputGroupTitle').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.editGroupModal .restrictedTo .stdDropdown.tags').filter({ visible: true })).toHaveCount(0);
            await page.locator('.editGroupModal .restrictedPermissions .privilege4 input').click();
            await dispatchClick(page, '.editGroupModal .restrictedTo .tag input');
            await expect(page.locator('.editGroupModal .restrictedTo .stdDropdown.tags').first()).toBeVisible();
            await page.locator('.editGroupModal .restrictedTo .stdDropdown.tags').first().selectOption('1');
            await dispatchClick(page, '.editGroupModal .btnAdd');
            await expect(page.locator('.editGroupModal .restrictedPrivilegeList')).toContainText('Rahmendaten bearbeiten');
            await expect(page.locator('.editGroupModal .restrictedPrivilegeList')).toContainText('Umwelt');
            await dispatchClick(page, '.editGroupModal .btnSave');
            await page.waitForLoadState('networkidle');
            await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText('Restricted Group');
            await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText(
                'Umwelt: Rahmendaten bearbeiten',
            );

            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
        });

        await test.step('Add the testuser to the newly created group', async () => {
            await page.locator('.addSingleInit .inputEmail').first().fill('testuser@example.org');
            await dispatchClick(page, '.addUsersOpener.singleuser');
            await expect(page.locator('.addUsersByLogin.singleuser .showIfExists').first()).toBeVisible();
            await expect(page.locator('.addUsersByLogin.singleuser .showIfNew').filter({ visible: true })).toHaveCount(0);
            await page.locator(`.addUsersByLogin.singleuser .userGroup${FIRST_FREE_USERGROUP_ID}`).first().check();
            await page.locator('.addUsersByLogin.singleuser .userGroup4').first().uncheck();
            await page.locator('.addUsersByLogin.singleuser [name="addUsers"]').click();
            await expect(page.locator('.user2')).toContainText('Restricted Group');
        });

        await test.step('Check the activity log for the user group', async () => {
            await page.locator(`.group${FIRST_FREE_USERGROUP_ID} .btnEdit`).click();
            await page.locator('.editGroupModal .changeLogLink').click();
            await expect(page.locator('body')).toContainText(
                'testuser@example.org wurde der Gruppe „Restricted Group” hinzugefügt.',
            );

            await new ConsultationHomePage(page).open();
            await logout(page);
            await loginAsStdUser(page);
        });

        await test.step('Test the motion functionality as stduser', async () => {
            await page.locator('#motionListLink').click();
            await expect(page.locator('.actionCol').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motion2 .titleCol a').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motion3 .titleCol a').first()).toBeVisible();
            await page.locator('.motion3 .titleCol a').click();
            await page.locator('#motionTitle').first().fill('Formatted text');
            await page.locator('#motionNoteInternal').first().fill('Some internal notes');
            await expect(page.locator('#motionTextEditCaller').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#motionTextEditHolder').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.supporterForm').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#motionSupporterHolder').filter({ visible: true })).toHaveCount(0);
            await page.locator('#motionUpdateForm [name="save"]').click();
            await expect(page.locator('#motionTitle')).toHaveValue('Formatted text');
            await expect(page.locator('#motionNoteInternal')).toHaveValue('Some internal notes');
            await page.locator('#sidebar .view').click();
            await expect(page.locator('h1')).toContainText('Formatted text');
            await expect(page.locator('.motionDataTable')).toContainText('Testadmin');
            await expect(page.locator('span.underline')).toContainText('unterstrichen');
        });

        await test.step('Test the amendment functionality as stduser', async () => {
            await page.locator('#motionListLink').click();
            await expect(page.locator('.amendment1 .titleCol a').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment2 .titleCol a').first()).toBeVisible();
            await page.locator('.amendment2 .titleCol a').click();
            await page.locator('#amendmentNoteInternal').first().fill('Some internal notes');
            await expect(page.locator('.amendmentTextEditCaller').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendmentTextEditHolder').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.supporterForm').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#motionSupporterHolder').filter({ visible: true })).toHaveCount(0);
            await page.locator('#amendmentUpdateForm [name="save"]').click();
            await expect(page.locator('#amendmentNoteInternal')).toHaveValue('Some internal notes');
            await page.locator('#sidebar .view').click();
            await expect(page.locator('.motionDataTable')).toContainText('Testuser');
            await expect(page.locator('ins')).toContainText('Und noch eine neue Zeile gq Q.');
        });
    });
});