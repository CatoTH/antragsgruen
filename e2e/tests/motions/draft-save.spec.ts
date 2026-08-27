import { test, expect } from '../../fixtures';
import { setCkEditorContent, expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Motion draft saving', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a draft is saved and can be restored', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();

        await test.step('fill in some text and leave the page', async () => {
            await page.locator('[name="sections[1]"]').first().fill('Draft title');
            await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Some text</strong></p>');
            await setCkEditorContent(
                page,
                'sections_3_wysiwyg',
                '<p><strong>Even more text</strong></p>',
            );

            await home.open();
            await home.gotoMotionCreatePage();
        });

        await test.step('see the saved draft', async () => {
            await expect(page.locator('#draftHint').first()).toBeVisible();
            await expect(page.locator('#draftHint')).toContainText('Entwurf vom:');
            await expect(page.locator('[name="sections[1]"]')).not.toHaveValue('Draft title');
            await expect(page.locator('body')).not.toContainText('Some text', { useInnerText: true });
            await expect(page.locator('body')).not.toContainText('Even more text', { useInnerText: true });
        });

        await test.step('restore the draft', async () => {
            await page.locator('#draftHint button.restore').click();
            await expectBootboxDialog(page, /Diesen Entwurf wiederherstellen\?/);
            await acceptBootbox(page);

            await expect(page.locator('[name="sections[1]"]')).toHaveValue('Draft title');
            await expect(page.locator('body')).toContainText('Some text');
            await expect(page.locator('body')).toContainText('Even more text');
            await expect(page.locator('#draftHint').filter({ visible: true })).toHaveCount(0);
        });
    });
});
