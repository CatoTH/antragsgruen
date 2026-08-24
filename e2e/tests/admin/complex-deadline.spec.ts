import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: ComplexDeadline', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('complex deadline: 6 phases covering motions/amendments/comments/merging', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();

        await expect(page.locator('.stickyAdminDebugFooter')).not.toBeVisible();
        await expect(page.locator('.deadlineTypeComplex.motionDeadlines')).not.toBeVisible();
        await expect(page.locator('#typeDeadlineMotionsHolder')).toBeVisible();
        await page.locator('#deadlineFormTypeComplex').check();
        await expect(page.locator('.deadlineTypeComplex.motionDeadlines')).toBeVisible();
        await expect(page.locator('#typeDeadlineMotionsHolder')).not.toBeVisible();

        await dispatchClick(page, '.motionDeadlines .deadlineAdder');
        await dispatchClick(page, '.motionDeadlines .deadlineAdder');

        await page.evaluate(() => {
            const setVal = (selector: string, value: string) => {
                const el = document.querySelector(selector) as HTMLInputElement | null;
                if (el) {
                    el.value = value;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };
            setVal(
                '.motionDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input',
                '01.05.2017 00:00',
            );
            setVal(
                '.motionDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input',
                '15.05.2017 12:00',
            );
            setVal('.motionDeadlines .deadlineEntry:nth-child(1) .phaseTitle', 'Phase 2');
            setVal(
                '.motionDeadlines .deadlineEntry:nth-child(2) .datetimepickerFrom input',
                '01.07.2017 00:00',
            );
            setVal('.motionDeadlines .deadlineEntry:nth-child(1) .phaseTitle', 'Phase 3');
            setVal(
                '.motionDeadlines .deadlineEntry:nth-child(3) .datetimepickerTo input',
                '15.04.2017 12:00',
            );
            setVal('.motionDeadlines .deadlineEntry:nth-child(1) .phaseTitle', 'Phase 1');

            setVal(
                '.amendmentDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input',
                '01.07.2017 00:00',
            );
            setVal(
                '.amendmentDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input',
                '15.07.2017 12:00',
            );

            setVal(
                '.commentDeadlines .deadlineEntry:nth-child(1) .datetimepickerTo input',
                '01.08.2017 00:00',
            );
            setVal(
                '.mergingDeadlines .deadlineEntry:nth-child(1) .datetimepickerFrom input',
                '15.08.2017 00:00',
            );
        });

        await page.locator('#deadlineDebugMode').check();
        await page.locator('.adminTypeForm [name="save"].first()').click();
        await expect(page.locator('.stickyAdminDebugFooter')).toBeVisible();

        async function setTime(date: string) {
            await new ConsultationHomePage(page).open();
            await page.locator('#simulateAdminTimeInput').fill(date);
            await dispatchClick(page, '.stickyAdminDebugFooter .setTime');
        }

        await setTime('01.04.2017 01:00');
        await expect(page.locator('#sidebar .createMotion')).toBeVisible();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentForm')).toBeVisible();
        await expect(page.locator('.amendmentCreate .onlyAdmins')).toBeVisible();
        await expect(page.locator('#sidebar .mergeamendments')).not.toBeVisible();

        await setTime('17.04.2017 01:00');
        await expect(page.locator('#sidebar .createMotion')).not.toBeVisible();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentForm')).toBeVisible();
        await expect(page.locator('.amendmentCreate .onlyAdmins')).toBeVisible();
        await expect(page.locator('#sidebar .mergeamendments')).not.toBeVisible();

        await setTime('01.05.2017 01:00');
        await expect(page.locator('#sidebar .createMotion')).toBeVisible();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentForm')).toBeVisible();
        await expect(page.locator('.amendmentCreate .onlyAdmins')).toBeVisible();
        await expect(page.locator('#sidebar .mergeamendments')).not.toBeVisible();

        await setTime('01.06.2017 01:00');
        await expect(page.locator('#sidebar .createMotion')).not.toBeVisible();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentForm')).toBeVisible();
        await expect(page.locator('.amendmentCreate .onlyAdmins')).toBeVisible();
        await expect(page.locator('#sidebar .mergeamendments')).not.toBeVisible();

        await setTime('01.07.2017 01:00');
        await expect(page.locator('#sidebar .createMotion')).toBeVisible();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentForm')).toBeVisible();
        await expect(page.locator('.amendmentCreate .onlyAdmins')).not.toBeVisible();
        await expect(page.locator('.amendmentCreate')).toBeVisible();
        await expect(page.locator('#sidebar .mergeamendments')).not.toBeVisible();

        await setTime('01.09.2017 01:00');
        await expect(page.locator('#sidebar .createMotion')).toBeVisible();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentForm')).not.toBeVisible();
        await expect(page.locator('.amendmentCreate .onlyAdmins')).toBeVisible();
        await expect(page.locator('#sidebar .mergeamendments')).toBeVisible();
    });
});