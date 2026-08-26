import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
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
        await test.step('prepare a second consultation', async () => {
            await loginAsStdAdmin(page);
            await test.step('prepare the test case', async () => {
                await page.locator('.siteConsultationsLink').click();
                await page.locator('#newTitle').first().fill('Test3');
                await page.locator('#newShort').first().fill('test3');
                await page.locator('#newPath').first().fill('test3');
                await page
                    .locator('.consultationCreateForm [name="createConsultation"]')
                    .click();
            });
        });

        await test.step('move a motion to test3', async () => {
            await new MotionPage(page).open({ motionSlug: 'Testing_proposed_changes-630' });
            await test.step('move the motion to test3', async () => {
                await expect(page.locator('#section_4_0').filter({ visible: true })).toHaveCount(0);
                await page.locator('#sidebar .adminEdit a').click();
                await page.locator('.sidebarActions .move').click();

                await expect(page.locator('.moveToConsultationItem').filter({ visible: true })).toHaveCount(0);
                await page.locator("input[name='operation'][value='move']").first().check();
                await expect(page.locator("input[name='operation'][value='move']")).toBeChecked();
                await page.locator("input[name='target'][value='consultation']").first().check();
                await expect(page.locator('.moveToConsultationItem').first()).toBeVisible();

                await page.locator('.adminMoveForm [name="move"]').click();
                await page.locator('.alert-success a').click();
                await expect(page.locator('.breadcrumb')).toContainText('Test3');

                await page.goto('/stdparteitag/test3');
                await expect(page.locator('.motionRow118').first()).toBeVisible();
            });
        });

        await test.step('verify merging still works after moving', async () => {
            await page.goto('/stdparteitag/test3');
            await test.step('make sure the merging still works', async () => {
                await page.locator('.motionLink118').click();
                await page.locator('#sidebar .mergeamendments a').click();
                await dispatchClick(page, '.toMergeAmendments .selectAll');
                await page.locator('.mergeAllRow [type="submit"]').click();
                const sectionId = FIRST_FREE_MOTION_SECTION + 1;
                await expect(
                    page.locator(`#paragraphWrapper_${sectionId}_1 .collidingParagraph`),
                ).toContainText('A big replacement');
            });
        });

        await test.step('copy a motion (no reference) to test3', async () => {
            await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
            await test.step('make a pure copy of a motion', async () => {
                await page.locator('#sidebar .adminEdit a').click();
                await page.locator('.sidebarActions .move').click();

                await expect(page.locator('.moveToConsultationItem').filter({ visible: true })).toHaveCount(0);
                await page.locator("input[name='operation'][value='copynoref']").first().check();
                await expect(page.locator("input[name='operation'][value='copynoref']")).toBeChecked();
                await page.locator("input[name='target'][value='consultation']").first().check();
                await expect(page.locator('.moveToConsultationItem').first()).toBeVisible();

                await page.locator('.adminMoveForm [name="move"]').click();
                await page.locator('.alert-success a').click();
                await expect(page.locator('.breadcrumb')).toContainText('Test3');

                await page.goto('/stdparteitag/test3');
                await expect(page.locator(`.motionRow${FIRST_FREE_MOTION_ID}`).first()).toBeVisible();
                await page
                    .locator(
                        `.motionRow${FIRST_FREE_MOTION_ID} .motionLink${FIRST_FREE_MOTION_ID}`,
                    )
                    .click();
                await expect(page.locator('body')).toContainText('Bavaria ipsum dolor sit amet');
            });
        });

        await test.step('verify A2 is still at its old place after the copy', async () => {
            await test.step('Make sure A2 is still at its old place', async () => {
                await expect(page.locator('.motionRow2').first()).toBeVisible();
                await expect(page.locator('.motionRow2.moved').filter({ visible: true })).toHaveCount(0);
                await page.locator('.motionRow2 .motionLink2').click();
                await expect(page.locator('body')).toContainText('Bavaria ipsum dolor sit amet');
            });
        });

        await test.step('copy the motion back to test2 with a reference', async () => {
            await page.goto('/stdparteitag/test3');
            await page.locator('.motionLink118').click();
            await page.locator('#sidebar .adminEdit a').click();
            await page.locator('.sidebarActions .move').click();

            await expect(page.locator('.moveToConsultationItem').filter({ visible: true })).toHaveCount(0);
            await page.locator("input[name='operation'][value='copy']").first().check();
            await expect(page.locator("input[name='operation'][value='copy']")).toBeChecked();
            await page.locator("input[name='target'][value='consultation']").first().check();
            await expect(page.locator('.moveToConsultationItem').first()).toBeVisible();
            await page.locator('#motionTitlePrefix').first().fill('A8.1');

            await page.locator('.adminMoveForm [name="move"]').click();

            await page.goto('/stdparteitag/test3');
            await expect(page.locator('.motionRow118.moved').first()).toBeVisible();
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
});