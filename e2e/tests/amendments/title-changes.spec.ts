import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';

test.describe('Amendments: TitleChanges', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('see amendments affecting the title of a motion', async ({ page }) => {
        await new ConsultationHomePage(page).open();

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.sectionType0 .amendment1').first()).toBeVisible();
        await expect(page.locator('.sectionType0 .amendment274').first()).toBeVisible();
        await expect(page.locator('.sectionType0 .amendment276').filter({ visible: true })).toHaveCount(0);

        await new MotionPage(page).open({ motionSlug: '123-textformatierungen' });
        await expect(page.locator('.sectionType0').filter({ visible: true })).toHaveCount(0);
    });
});