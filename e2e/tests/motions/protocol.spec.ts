import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/BasePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

const MOTION_SLUG = '321-o-zapft-is';

test.describe('Motion protocol', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a non-public protocol is hidden and becomes visible when published', async ({
        page,
    }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        await motionList.open();
        await motionList.gotoMotionEdit(2);

        await expect(page.locator('.protocolHolder')).toHaveCount(0);
        await page.locator('.contentProtocolCaller button').click();
        await expect(page.locator('.protocolHolder')).toBeVisible();
        await setCkEditorContent(
            page,
            'protocol_text_wysiwyg',
            '<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>',
        );
        await page.locator('#motionUpdateForm [name="save"]').click();

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('.motionProtocol .protocolOpener')).toHaveCount(0);

        await motionList.open();
        await motionList.gotoMotionEdit(2);
        await page.locator("input[name='protocol_public'][value='1']").check();
        await page.locator('#motionUpdateForm [name="save"]').click();

        await motion.open({ motionSlug: MOTION_SLUG });
        await page.locator('.motionProtocol .protocolOpener').click();
        await expect(page.locator('.protocolHolder')).toContainText(
            'So Long, and Thanks for All the Fish',
        );
    });
});
