import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { acceptBootbox, dispatchClick, expectBootboxDialog } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { FIRST_FREE_USERGROUP_ID } from '../../utils/constants';

test.describe('Useradmin: Usergroups', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create user group and restrict amendment submission', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await expect(page.locator('.group4')).toContainText('Teilnehmer*in');
        await expect(page.locator('.addGroupForm').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.btnGroupCreate');
        await expect(page.locator('.addGroupForm').first()).toBeVisible();
        await page.locator('.addGroupForm .addGroupName input').first().fill('Special group');
        await dispatchClick(page, '.addGroupForm .btnSave');
        await page.waitForLoadState('networkidle');
        await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText('Special group');

        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await test.step('restrict creating amendments to the new user group', async () => {
            await expect(page.locator('.policyWidgetAmendments .userGroupSelect').filter({ visible: true })).toHaveCount(0);
            await page.locator('#typePolicyAmendments').first().selectOption('4');
            await expect(page.locator('.policyWidgetAmendments .userGroupSelect').first()).toBeVisible();
            const itemsLenBefore = await page.evaluate(() => {
                return (document.querySelector('#typePolicyAmendmentsGroups') as any).selectize.items.length;
            });
            expect(itemsLenBefore).toBe(0);
            await page.evaluate(
                (groupId: string) => {
                    (document.querySelector('#typePolicyAmendmentsGroups') as any).selectize.addItem(groupId);
                },
                String(FIRST_FREE_USERGROUP_ID),
            );
            const itemsLenAfter = await page.evaluate(() => {
                return (document.querySelector('#typePolicyAmendmentsGroups') as any).selectize.items.length;
            });
            expect(itemsLenAfter).toBe(1);
            await motionTypePage.saveForm();
            await expect(page.locator('.policyWidgetAmendments .selectize-input')).toContainText('Special group');

            await logout(page);
            await page.locator(`.motionLink2`).click();
            await loginAsStdUser(page);
        });

        await test.step('not being able to create amendments as a user', async () => {
            await expect(page.locator('#sidebar')).toContainText(
                'Nur zugelassene Gruppen können Änderungsanträge stellen',
            );

            await logout(page);

            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
        });

        await test.step('assign the group to a user (Testuser)', async () => {
            await expect(page.locator('.user2').filter({ visible: true })).toHaveCount(0);
            await page.locator('.addSingleInit .inputEmail').first().fill('testuser@example.org');
            await dispatchClick(page, '.addUsersOpener.singleuser');
            await expect(page.locator('.addUsersByLogin.singleuser .showIfExists').first()).toBeVisible();
            await expect(page.locator('.addUsersByLogin.singleuser .showIfNew').filter({ visible: true })).toHaveCount(0);
            await page.locator(`.addUsersByLogin.singleuser .userGroup${FIRST_FREE_USERGROUP_ID}`).first().check();
            await page.locator('.addUsersByLogin.singleuser .userGroup4').first().uncheck();
            await page.locator('.addUsersByLogin.singleuser [name="addUsers"]').click();

            await expect(page.locator('.user2').getByText('Veranstaltungs-Admin').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.user2').getByText('Teilnehmer*in').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.user2')).toContainText('Special group');

            await logout(page);
            await page.locator(`.motionLink2`).click();
            await loginAsStdUser(page);
        });

        await test.step('be able to create amendments now as that user', async () => {
            await expect(page.locator('#sidebar').getByText('Nur zugelassene Gruppen können Änderungsanträge stellen').filter({ visible: true })).toHaveCount(0);
            await page.locator('#sidebar .amendmentCreate a').click();
            await expect(page.locator('.breadcrumb')).toContainText('Änderungsantrag stellen');

            await logout(page);
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
        });

        await test.step('delete the group again', async () => {
            await page.locator(`.group${FIRST_FREE_USERGROUP_ID} .btnRemove`).click();

            await expectBootboxDialog(page, /Special group wirklich löschen/);
            await acceptBootbox(page);
            await expect(page.locator('.user2').getByText('Veranstaltungs-Admin').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.user2')).toContainText('Teilnehmer*in');
            await expect(page.locator('.user2').getByText('Special group').filter({ visible: true })).toHaveCount(0);
        });

    });
});