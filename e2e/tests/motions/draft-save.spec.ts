import { test, expect } from '../../fixtures';
import { setCkEditorContent, expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/BasePage';

test.describe('Motion draft saving', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a draft is saved and can be restored', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();

        await page.locator('[name="sections[1]"]').fill('Draft title');
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Some text</strong></p>');
        await setCkEditorContent(
            page,
            'sections_3_wysiwyg',
            '<p><strong>Even more text</strong></p>',
        );

        await home.open();
        await home.gotoMotionCreatePage();

        await expect(page.locator('#draftHint')).toBeVisible();
        await expect(page.locator('#draftHint')).toContainText('Entwurf vom:');
        await expect(page.locator('[name="sections[1]"]')).not.toHaveValue('Draft title');
        await expect(page.locator('body')).not.toContainText('Some text');
        await expect(page.locator('body')).not.toContainText('Even more text');

        await page.locator('#draftHint button.restore').click();
        await expectBootboxDialog(page, /Diesen Entwurf wiederherstellen\?/);
        await acceptBootbox(page);

        await expect(page.locator('[name="sections[1]"]')).toHaveValue('Draft title');
        await expect(page.locator('body')).toContainText('Some text');
        await expect(page.locator('body')).toContainText('Even more text');
        await expect(page.locator('#draftHint')).toHaveCount(0);
    });
});
