import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/BasePage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    FIRST_FREE_MOTION_ID,
    FIRST_FREE_MOTION_SECTION,
} from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: MoveMotionsToOtherConsultation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('prepare a second consultation', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('.siteConsultationsLink').click();
        await page.locator('#newTitle').fill('Test3');
        await page.locator('#newShort').fill('test3');
        await page.locator('#newPath').fill('test3');
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();
    });

    test('move a motion to test3', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new MotionPage(page).open({ motionSlug: 'Testing_proposed_changes-630' });
        await expect(page.locator('#section_4_0')).not.toBeVisible();
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('.sidebarActions .move').click();

        await expect(page.locator('.moveToConsultationItem')).not.toBeVisible();
        await page.locator("input[name='operation'][value='move']").check();
        await expect(page.locator("input[name='operation'][value='move']")).toBeChecked();
        await page.locator("input[name='target'][value='consultation']").check();
        await expect(page.locator('.moveToConsultationItem')).toBeVisible();

        await page.locator('.adminMoveForm [name="move"]').click();
        await page.locator('.alert-success a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Test3');

        await page.goto('/stdparteitag/test3');
        await expect(page.locator('.motionRow118')).toBeVisible();
    });

    test('verify merging still works after moving', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/test3');
        await page.locator('.motionLink118').click();
        await page.locator('#sidebar .mergeamendments a').click();
        await dispatchClick(page, '.toMergeAmendments .selectAll');
        await page.locator('.mergeAllRow [type="submit"]').click();
        const sectionId = FIRST_FREE_MOTION_SECTION + 1;
        await expect(
            page.locator(`#paragraphWrapper_${sectionId}_1 .collidingParagraph`),
        ).toContainText('A big replacement');
    });

    test('copy a motion (no reference) to test3', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('.sidebarActions .move').click();

        await expect(page.locator('.moveToConsultationItem')).not.toBeVisible();
        await page.locator("input[name='operation'][value='copynoref']").check();
        await expect(page.locator("input[name='operation'][value='copynoref']")).toBeChecked();
        await page.locator("input[name='target'][value='consultation']").check();
        await expect(page.locator('.moveToConsultationItem')).toBeVisible();

        await page.locator('.adminMoveForm [name="move"]').click();
        await page.locator('.alert-success a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Test3');

        await page.goto('/stdparteitag/test3');
        await expect(page.locator(`.motionRow${FIRST_FREE_MOTION_ID}`)).toBeVisible();
        await page
            .locator(
                `.motionRow${FIRST_FREE_MOTION_ID} .motionLink${FIRST_FREE_MOTION_ID}`,
            )
            .click();
        await expect(page.locator('body')).toContainText('Bavaria ipsum dolor sit amet');
    });

    test('verify A2 is still at its old place after the copy', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.motionRow2')).toBeVisible();
        await expect(page.locator('.motionRow2.moved')).not.toBeVisible();
        await page.locator('.motionRow2 .motionLink2').click();
        await expect(page.locator('body')).toContainText('Bavaria ipsum dolor sit amet');
    });

    test('copy the motion back to test2 with a reference', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/test3');
        await page.locator('.motionLink118').click();
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('.sidebarActions .move').click();

        await expect(page.locator('.moveToConsultationItem')).not.toBeVisible();
        await page.locator("input[name='operation'][value='copy']").check();
        await expect(page.locator("input[name='operation'][value='copy']")).toBeChecked();
        await page.locator("input[name='target'][value='consultation']").check();
        await expect(page.locator('.moveToConsultationItem')).toBeVisible();
        await page.locator('#motionTitlePrefix').fill('A8.1');

        await page.locator('.adminMoveForm [name="move"]').click();

        await page.goto('/stdparteitag/test3');
        await expect(page.locator('.motionRow118.moved')).toBeVisible();
        await page.locator('.motionLink118').click();
        await page.locator('.motionReplacedBy a').click();
        await expect(page.locator('h1')).toContainText('A8.1: Testing proposed changes');

        await page.locator('#sidebar .mergeamendments a').click();
        await dispatchClick(page, '.toMergeAmendments .selectAll');
        await page.locator('.mergeAllRow [type="submit"]').click();
        await expect(
            page.locator('#paragraphWrapper_2_1 .collidingParagraph'),
        ).toContainText('A big replacement');
    });
});