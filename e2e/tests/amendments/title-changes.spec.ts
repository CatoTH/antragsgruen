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
        await expect(page.locator('.sectionType0 .amendment1')).toBeVisible();
        await expect(page.locator('.sectionType0 .amendment274')).toBeVisible();
        await expect(page.locator('.sectionType0 .amendment276')).not.toBeVisible();

        await new MotionPage(page).open({ motionSlug: '123-textformatierungen' });
        await expect(page.locator('.sectionType0')).not.toBeVisible();
    });
});