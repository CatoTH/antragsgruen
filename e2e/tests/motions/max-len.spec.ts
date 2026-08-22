import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

async function isSubmitDisabled(page: import('@playwright/test').Page): Promise<boolean> {
    return page.evaluate(() => {
        const w = window as any;
        return !!w.$('.motionEditForm button[type=submit]').prop('disabled');
    });
}

test.describe('Maximum section length', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('max length settings are saved', async ({ page }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });

        await expect(page.locator('.section1 .maxLenInput')).toHaveCount(0);
        await page.locator('.section1 .maxLenSet').check();
        await expect(page.locator('.section1 .maxLenInput')).toBeVisible();
        await page.locator('.section1 .maxLenInput input').fill('20');
        await page.locator('.section2 .maxLenSet').check();
        await page.locator('.section2 .maxLenInput input').fill('100');
        await page.locator('.section3 .maxLenSet').check();
        await page.locator('.section3 .maxLenInput input').fill('150');
        await page.locator('.section3 .maxLenSoftCheckbox').check();
        await motionType.saveForm();

        await expect(page.locator('body')).toContainText('Gespeichert.');
        await expect(page.locator('.section1 .maxLenInput')).toBeVisible();
        await expect(page.locator('.section1 .maxLenSet')).toBeChecked();
        await expect(page.locator('.section1 .maxLenSoftCheckbox')).not.toBeChecked();
        await expect(page.locator('.section1 .maxLenInput input')).toHaveValue('20');
        await expect(page.locator('.section2 .maxLenSet')).toBeChecked();
        await expect(page.locator('.section2 .maxLenSoftCheckbox')).not.toBeChecked();
        await expect(page.locator('.section2 .maxLenInput input')).toHaveValue('100');
        await expect(page.locator('.section3 .maxLenSet')).toBeChecked();
        await expect(page.locator('.section3 .maxLenSoftCheckbox')).toBeChecked();
        await expect(page.locator('.section3 .maxLenInput input')).toHaveValue('150');
    });

    test('too long texts block submitting unless the limit is soft', async ({ page }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });

        await page.locator('.section1 .maxLenSet').check();
        await page.locator('.section1 .maxLenInput input').fill('20');
        await page.locator('.section2 .maxLenSet').check();
        await page.locator('.section2 .maxLenInput input').fill('100');
        await page.locator('.section3 .maxLenSet').check();
        await page.locator('.section3 .maxLenInput input').fill('150');
        await page.locator('.section3 .maxLenSoftCheckbox').check();
        await motionType.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();

        await expect(page.locator('body')).not.toContainText('Der Text ist zu lang');
        await expect(page.locator('body')).toContainText('Max. 20 Zeichen (Aktuell: 0)');
        await expect(page.locator('body')).toContainText('Max. 100 Zeichen (Aktuell: 0)');
        await expect(page.locator('body')).toContainText('Max. 150 Zeichen (Aktuell: 0)');

        await page.locator('#sections_1').fill('12345');
        await expect(page.locator('body')).not.toContainText('Der Text ist zu lang');
        await expect(page.locator('body')).toContainText('Max. 20 Zeichen (Aktuell: 5)');

        await page.locator('#sections_1').fill('x'.repeat(21));
        await expect(page.locator('body')).toContainText('Der Text ist zu lang');
        await expect(page.locator('body')).toContainText('Max. 20 Zeichen (Aktuell: 21)');
        expect(await isSubmitDisabled(page)).toBe(true);

        await page.locator('#sections_1').fill('x'.repeat(20));
        await setCkEditorContent(page, 'sections_2_wysiwyg', 'x'.repeat(101));
        await expect(page.locator('body')).toContainText('Der Text ist zu lang');
        await expect(page.locator('body')).toContainText('Max. 100 Zeichen (Aktuell: 101)');
        expect(await isSubmitDisabled(page)).toBe(true);

        await setCkEditorContent(page, 'sections_2_wysiwyg', 'x'.repeat(100));
        await setCkEditorContent(page, 'sections_3_wysiwyg', 'x'.repeat(151));
        await expect(page.locator('body')).toContainText('Max. 150 Zeichen (Aktuell: 151)');
        await expect(page.locator('body')).toContainText('Der Text ist zu lang');
        expect(await isSubmitDisabled(page)).toBe(false);

        await setCkEditorContent(page, 'sections_3_wysiwyg', 'x'.repeat(150));
        await expect(page.locator('body')).not.toContainText('Der Text ist zu lang');
    });
});
