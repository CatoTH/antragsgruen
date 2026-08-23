import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
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
        await expect(page.locator('.addGroupForm')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.btnGroupCreate') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.addGroupForm')).toBeVisible();
        await page.locator('.addGroupForm .addGroupName input').fill('Special group');
        await page.evaluate(() => {
            const btn = document.querySelector('.addGroupForm .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator(`.group${FIRST_FREE_USERGROUP_ID}`)).toContainText('Special group');

        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await expect(page.locator('.policyWidgetAmendments .userGroupSelect')).toHaveCount(0);
        await page.locator('#typePolicyAmendments').selectOption('4');
        await expect(page.locator('.policyWidgetAmendments .userGroupSelect')).toBeVisible();
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
        await expect(page.locator('#sidebar')).toContainText(
            'Nur zugelassene Gruppen können Änderungsanträge stellen',
        );

        await logout(page);

        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await expect(page.locator('.user2')).toHaveCount(0);
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

        await expect(page.locator('.user2')).not.toContainText('Veranstaltungs-Admin');
        await expect(page.locator('.user2')).not.toContainText('Teilnehmer*in');
        await expect(page.locator('.user2')).toContainText('Special group');

        await logout(page);
        await page.locator(`.motionLink2`).click();
        await loginAsStdUser(page);
        await expect(page.locator('#sidebar')).not.toContainText(
            'Nur zugelassene Gruppen können Änderungsanträge stellen',
        );
        await page.locator('#sidebar .amendmentCreate a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Änderungsantrag stellen');

        await logout(page);
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await page.evaluate(
            ([groupId]) => {
                const btn = document.querySelector(`.group${groupId} .btnRemove`) as HTMLElement;
                const evt = document.createEvent('HTMLEvents');
                evt.initEvent('click', false, true);
                btn.dispatchEvent(evt);
            },
            [String(FIRST_FREE_USERGROUP_ID)],
        );
        await expectBootboxDialog(page, /Special group wirklich löschen/);
        await acceptBootbox(page);
        await expect(page.locator('.user2')).not.toContainText('Veranstaltungs-Admin');
        await expect(page.locator('.user2')).toContainText('Teilnehmer*in');
        await expect(page.locator('.user2')).not.toContainText('Special group');
    });
});