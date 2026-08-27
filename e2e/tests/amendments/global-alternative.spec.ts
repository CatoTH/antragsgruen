import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';
import { setCkEditorContent } from '../../utils/dom';

test.describe('Amendments: GlobalAlternative', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create a global alternative amendment', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await page.locator("input[name='tags[]'][value='1']").first().check();
        await page.locator("[name='sections[1]']").first().fill('alternative motion');
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p>This is my new motion</p>');
        await setCkEditorContent(page, 'sections_4_wysiwyg', '<p>Part 2</p>');
        await page.locator('input[name=globalAlternative]').first().check();

        await page.locator('#amendmentEditForm [name="save"]').click();
        await expect(page.locator('body')).toContainText('This is my new motion');
        await expect(page.locator('body')).not.toContainText('Woibbadinga', { useInnerText: true });
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('ul.amendments')).toContainText('Ä8');
        await expect(page.locator('.bookmarks').getByText('Ä8').filter({ visible: true })).toHaveCount(0);
    });

    test('view the global alternative amendment', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator(`.amendments .amendment${FIRST_FREE_AMENDMENT_ID}`).click();
        await expect(page.locator('body')).toContainText('This is my new motion');
        await expect(page.locator('body')).toContainText('Part 2');
        await expect(page.locator('body')).not.toContainText('Woibbadinga', { useInnerText: true });
    });

    test('toggle global alternative off and on in admin view', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator(`.adminMotionTable .amendment${FIRST_FREE_AMENDMENT_ID} .titleCol a`)
            .first()
            .click();
        await expect(page.locator('body')).toContainText('This is my new motion');
        await expect(page.locator('body')).toContainText('Part 2');
        await expect(page.locator('body')).not.toContainText('Woibbadinga', { useInnerText: true });
        await expect(page.locator('#globalAlternative')).toBeChecked();
        await page.locator('#globalAlternative').first().uncheck();
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await expect(page.locator('#globalAlternative')).not.toBeChecked();
        await expect(page.locator('.inserted')).toContainText('This is my new motion');
        await expect(page.locator('.inserted')).toContainText('Part 2');
        await expect(page.locator('.deleted')).toContainText('Woibbadinga');

        await page.locator('.sidebarActions .view').click();
        await expect(page.locator('.inserted')).toContainText('This is my new motion');
        await expect(page.locator('.inserted')).toContainText('Part 2');
        await expect(page.locator('.deleted')).toContainText('Woibbadinga');

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('ul.amendments')).toContainText('Ä8');
        await expect(page.locator('.bookmarks')).toContainText('Ä8');

        await new ConsultationHomePage(page).gotoAmendmentView(FIRST_FREE_AMENDMENT_ID);
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('#globalAlternative').first().check();
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('#sidebar .mergeamendments a').click();
        await expect(page.locator('#markAmendment1')).toBeChecked();
        await expect(
            page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).not.toBeChecked();
    });

    test('merging into the motion with a global alternative sets REJECTED status', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).gotoAmendmentView(FIRST_FREE_AMENDMENT_ID);
        await page.locator('#sidebar .mergeIntoMotion a').click();
        const status = await page.evaluate(
            () => (document.getElementById('otherAmendmentsStatus1') as HTMLSelectElement).value,
        );
        expect(status).toEqual('5');
    });
});