import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { setUserFixedData } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { MotionCreatePage } from '../../pages/MotionCreatePage';

test.describe('Supporting: OrganisationListsWithNoOrganisationField', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('organisation list with no organisation field', async ({ page, request }) => {
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await expect(page.locator('.editOrganisationModal')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.orgaOpenerHolder .orgaOpener') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editOrganisationModal')).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector('.editOrganisationModal .btnAdd') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editOrganisationModal .btnAdd') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await page.evaluate(() => {
            const btn = document.querySelector('.editOrganisationModal .btnAdd') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await page.evaluate(() => {
            (document.querySelectorAll('.editOrganisationModal input.form-control').item(0) as HTMLInputElement).value = 'Working group: environment';
            (document.querySelectorAll('.editOrganisationModal input.form-control').item(1) as HTMLInputElement).value = 'Working group: infrastructure';
            (document.querySelectorAll('.editOrganisationModal input.form-control').item(2) as HTMLInputElement).value = 'Working group: education';
        });

        await page.evaluate(() => {
            const btn = document.querySelector('.editOrganisationModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await page.evaluate(() => {
            const btn = document.querySelector('.orgaOpenerHolder .orgaOpener') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editOrganisationModal input').first()).toHaveValue('Working group: environment');
        await expect(page.locator('.editOrganisationModal input').nth(1)).toHaveValue('Working group: infrastructure');
        await expect(page.locator('.editOrganisationModal input').nth(2)).toHaveValue('Working group: education');

        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator("input[name='initiatorCanBeOrganization']").uncheck();
        await page.locator("input[name='motionInitiatorSettings[hasOrganizations]']").uncheck();
        await motionTypePage.saveForm();

        await logout(page);

        await setUserFixedData(request, {
            email: 'testuser@example.org',
            nameGiven: 'Test',
            nameFamily: 'User2',
            organisation: 'Orga',
            fixed: true,
        });

        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);

        const createPage = new MotionCreatePage(page);
        await createPage.open({ motionTypeId: 1 });

        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Test User2');
        await page.locator("input[name='tags[]'][value='1']").check();
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Test</strong></p>');
        await createPage.saveForm();

        await expect(page.locator('body')).toContainText('Keine Daten angegeben (Feld: Überschrift)');
        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Test User2');
    });
});