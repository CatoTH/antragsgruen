import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: AmendmentDefaultViewMode', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('switch an amendment to full motion text mode', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('body')).not.toContainText('Bavaria ipsum dolor', { useInnerText: true });
        await expect(page.locator('.inserted')).toContainText('Oamoi a Maß');

        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page
            .locator('.adminMotionTable .amendment1 .titleCol a')
            .first()
            .click();
        await page.locator('#defaultViewModeFull').first().check();
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('body')).toContainText('Bavaria ipsum dolor');
        await expect(page.locator('.inserted')).toContainText('Oamoi a Maß');

        await dispatchClick(page, '#section_2 .dropdown-toggle');
        await expect(
            page.locator('#section_2 .dropdown-menu li.selected .showFullText'),
        ).toBeVisible();
    });
});