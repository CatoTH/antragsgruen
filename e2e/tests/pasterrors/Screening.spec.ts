import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { MotionCreatePage } from '../../pages/MotionCreatePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import {
    FIRST_FREE_AMENDMENT_TITLE_PREFIX,
    FIRST_FREE_AMENDMENT_ID,
    FIRST_FREE_MOTION_TITLE_PREFIX,
    FIRST_FREE_MOTION_ID,
} from '../../utils/constants';

test.describe('Screening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('screen amendments and motions through the motion list', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        const motionTypePage = new AdminMotionTypePage(page);
        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator('#screeningMotions').first().check();
        await page.locator('#screeningAmendments').first().check();
        await motionTypePage.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoAmendmentCreatePage('2');
        await setCkEditorContent(
            page,
            'sections_2_wysiwyg',
            '<p>Saupreiß</p>',
        );
        await setCkEditorContent(
            page,
            'amendmentReason_wysiwyg',
            '<p>This is my reason</p>',
        );
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        const motionList = new AdminMotionListPage(page);
        await new AdminIndexPage(page).open();
        await motionList.open();
        await expect(page.locator('body')).not.toContainText(FIRST_FREE_AMENDMENT_TITLE_PREFIX, { useInnerText: true });
        await page
            .locator(`.adminMotionTable .amendment${FIRST_FREE_AMENDMENT_ID} .selectbox`)
            .check();
        await page.locator('.motionListForm [name="screen"]').click();
        await expect(page.locator('body')).toContainText(
            'Die ausgewählten Anträge wurden freigeschaltet.',
        );
        await expect(page.locator('body')).toContainText(FIRST_FREE_AMENDMENT_TITLE_PREFIX);

        await new ConsultationHomePage(page).open();
        const motionCreatePage = await home.gotoMotionCreatePage();
        await motionCreatePage.fillInValidSampleData();
        await test.step('check that prefixes are set on motions when screening using the motion-list', async () => {
            await page.locator('#motionEditForm [name="save"]').click();
            await page.locator('#motionConfirmForm [name="confirm"]').click();

            await new AdminIndexPage(page).open();
            await motionList.open();
            await expect(page.locator('body')).not.toContainText(FIRST_FREE_MOTION_TITLE_PREFIX, { useInnerText: true });
            await page
                .locator(`.adminMotionTable .motion${FIRST_FREE_MOTION_ID} .selectbox`)
                .check();
            await page.locator('.motionListForm [name="screen"]').click();
            await expect(page.locator('body')).toContainText(
                'Die ausgewählten Anträge wurden freigeschaltet.',
            );
            await expect(page.locator('body')).toContainText(FIRST_FREE_MOTION_TITLE_PREFIX);
        });

    });
});