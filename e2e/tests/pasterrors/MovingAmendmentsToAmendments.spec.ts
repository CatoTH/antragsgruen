import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/BasePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminAdminConsultationsPage } from '../../pages/AdminAdminConsultationsPage';
import { AmendmentPage } from '../../pages/AmendmentPage';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { FIRST_FREE_MOTION_ID, FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';

test.describe('MovingAmendmentsToAmendments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable amendments to amendments, create and move them to another consultation', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        const motionTypePage = new AdminMotionTypePage(page);
        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator('#allowAmendmentsToAmendments').check();
        await motionTypePage.saveForm();

        const adminIndex = new AdminIndexPage(page);
        await adminIndex.open();
        await page.locator('.siteConsultationsLink').click();
        await page.locator('#newTitle').fill('Test3');
        await page.locator('#newShort').fill('test3');
        await page.locator('#newPath').fill('test3');
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoAmendmentView(278);
        await page.locator('#sidebar .amendmentCreate a').click();
        await page.waitForTimeout(500);

        await setCkEditorContent(
            page,
            'sections_2_wysiwyg',
            '<p>Weit hinten, hinter den Wortbergen 123</p>',
        );
        await setCkEditorContent(page, 'sections_4_wysiwyg', '<p>Insert</p>');
        await page.locator('#initiatorPrimaryName').fill('Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('ins')).toContainText('123');
        await expect(page.locator('.inserted')).toContainText('Insert');
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        await page.locator('#motionConfirmedForm [type="submit"]').click();
        await expect(page.locator('#section_4')).toContainText('Ä2');

        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('#sidebar .move').click();
        await page.locator("input[name='operation'][value='copynoref']").check();
        await page.locator("input[name='target'][value='consultation']").check();
        await expect(page.locator('.moveToConsultationItem')).toBeVisible();
        await page.locator('.adminMoveForm [name="move"]').click();
        await page.locator('.alert-success a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Test3');
        await page.goto('/stdparteitag/test3');

        await expect(page.locator(`.motionRow${FIRST_FREE_MOTION_ID}`)).toBeVisible();
        await page
            .locator(`.motionRow${FIRST_FREE_MOTION_ID} .motionLink${FIRST_FREE_MOTION_ID}`)
            .click();
        await page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID + 2} a`).click();
        await expect(page.locator('ins')).toContainText('123');
        await page.locator('.amendingAmendmentRow a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Test3');

        const motionPage = new MotionPage(page);
        await motionPage.open({ motionSlug: '117' });
        await expect(page.locator('.breadcrumb')).toContainText('Test2');
        await page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID} a`).click();
        await expect(page.locator('ins')).toContainText('123');
        await expect(page.locator('.breadcrumb')).toContainText('Test2');
    });
});