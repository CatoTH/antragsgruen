import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';

test.describe('Motion text formattings', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('numbered lists continue across paragraphs', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(3);

        await expect(page.locator('body')).toContainText('Zeilenumbruch unterstrichen');
        await expect(page.locator('#section_2_2 .text ol')).toHaveAttribute('start', '1');
        await expect(page.locator('#section_2_3 .text ol')).toHaveAttribute('start', '2');
    });
});
